<template>
    <Teleport to="body">
        <div class="modal fade" id="addcustomer" tabindex="-1" role="dialog" aria-labelledby="addcustomer"
            aria-hidden="true" key="modal-addcustomer">
            <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                <div class="modal-content radius-16 bg-base">
                    <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                        <h1 class="modal-title text-md" id="exampleModalLabel">
                            Add Customer</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="" @submit.prevent="createCustomer">
                        <div class="modal-body p-24">
                            <div class="row">
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Name"
                                        v-model="customer_name" required>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" placeholder="Enter Phone"
                                        v-model="customer_phone" required>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" placeholder="Enter Email"
                                        v-model="email">
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label class="form-label">Tax Number</label>
                                    <input type="text" class="form-control" placeholder="Enter Tax Number"
                                        v-model="tax_no">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea type="text" class="form-control" placeholder="Enter Address"
                                        v-model="address"></textarea>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="employee" checked
                                            v-model="is_active">
                                        <label class="form-check-label" for="employee">Is Active ?</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" @click.prevent="createCustomer()">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { usePosStore } from '../../stores/posStore';
import { db } from '../../db';

const pos = usePosStore();

const emit = defineEmits(['customerCreated']);

// Customer Modal Refs
const customer_name = ref('');
const customer_phone = ref('');
const email = ref('');
const tax_no = ref('');
const address = ref('');
const is_active = ref(true);

const generateUUID = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

const createCustomer = async () => {
  if(!customer_name.value || !customer_phone.value) {
    alert("Name and Phone required");
    return;
  }
  
  const cust = {
    id: Date.now(), // Fallback ID for offline customers
    uuid: generateUUID(),
    name: customer_name.value,
    phone: customer_phone.value,
    email: email.value,
    tax_number: tax_no.value,
    address: address.value,
    is_active: is_active.value ? 1 : 0,
    sync_status: 'pending'
  };
  
  await db.customers.add(cust);
  pos.customers.push(cust);
  
  // Add to sync queue
  await db.syncQueue.add({
    type: 'customer',
    data: cust,
    timestamp: Date.now(),
    status: 'pending',
    retry_count: 0
  });
  
  emit('customerCreated', cust);
  
  // Reset form
  customer_name.value = '';
  customer_phone.value = '';
  email.value = '';
  tax_no.value = '';
  address.value = '';
  
  // Close modal via Bootstrap 5 native API if possible, or fallback to vanilla DOM
  const modalEl = document.getElementById('addcustomer');
  if (modalEl && modalEl.classList.contains('show')) {
      if (typeof window.bootstrap !== 'undefined') {
          const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
          if (modalInstance) {
              modalInstance.hide();
          }
      } else if (typeof window.$ !== 'undefined') {
          // Fallback
          window.$('#addcustomer').modal('hide');
      }
  }
  
  // Try sync
  if(pos.isOnline) {
    pos.syncOfflineData();
  }
};
</script>
