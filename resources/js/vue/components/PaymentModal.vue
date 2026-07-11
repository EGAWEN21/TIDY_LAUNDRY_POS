<template>
    <div class="modal fade " id="payment" tabindex="-1" role="dialog" aria-labelledby="payment"
        aria-hidden="true"  key="payment-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modal-content-lg radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <h1 class="modal-title text-md" id="exampleModalLabel">
                        Payments
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-24">
                    <div class="">
                        <ul>
                            <li class="d-flex align-items-center gap-1 tw-justify-between text-sm">
                                <span class="text-md fw-semibold text-primary-light">
                                    Balance :</span>
                                <span class="text-secondary-light fw-medium"> {{ formatCurrency(pos.currentBalance || 0) }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 tw-mb-6 tw-mt-4">
                        <hr>
                    </div>
                    <div class="col-12 tw-my-6">
                        
                        <table class="table basic-border-table mb-0 tw-w-full tw-text-xs">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Payment Type </th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, key) in pos.payments" :key="key">
                                    <tr>
                                        <td>
                                            {{ key + 1 }}
                                        </td>
                                        <td class="text-primary">{{ formatCurrency(item.amount) }}</td>
                                        <td> {{ item.payment_type_name }}</td>
                                        <td>
                                            <button @click="removePayment(key)" type="button" class="remove-item-button bg-danger-focus bg-hover-danger-200 text-danger-600 fw-medium tw-size-6 d-flex justify-content-center align-items-center rounded-circle"> 
                                                <iconify-icon icon="fluent:delete-24-regular" class="menu-icon"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                        <div class="tw-py-16" v-if="pos.payments.length === 0">
                            <div class="text-center tw-text-xs">No payments were added, Add a payment to show it here.</div>
                        </div>
                        
                    </div>
                    <div class="col-12 tw-my-6">
                        <hr>
                    </div>
                    <div class="row mb-20 ">
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Paid Amount
                                <span class="text-danger">*</span></label>
                            <input type="text" class="form-control radius-8"
                                placeholder="Enter Amount"
                                v-model="payment_amount">
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Payment Type
                                <span class="text-danger">*</span></label>
                            <select class="form-select radius-8" v-model="payment_type">
                                <option value="">
                                    Choose Payment Type
                                </option>
                                <option class="select-box" value="1">
                                    Cash
                                </option>
                                <option class="select-box" value="2">
                                    UPI
                                </option>
                                <option class="select-box" value="3">
                                    Card
                                </option>
                                <option class="select-box" value="4">
                                    Cheque
                                </option>
                                <option class="select-box" value="5">
                                    Bank Transfer
                                </option>
                            </select>
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                    </div>
                    <div class="row mb-20 ">
                        <div class="col-6 ">
                            <label for="name"
                                class="form-label fw-semibold text-primary-light text-sm mb-8">Notes
                                </label>
                            <input type="text" class="form-control radius-8"
                                placeholder="Notes"
                                v-model="notes">
                            
                                <span class="error text-danger tw-text-xs"></span>
                            
                        </div>
                        <div class="col-6">
                            <button
                                class="tw-px-2 col-6 tw-text-xs tw-justify-center tw-font-semibold tw-py-3 tw-mt-[30px]  bg-success-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-w-full tw-border-0 tw-shadow-md "
                                @click="add_payment">
                                <span>Add Payment</span>
                            </button>
                        </div>
                        
                    </div>
                  
                    </div>
                    <div class="modal-footer tw-mt-12 tw-flex tw-gap-2">
                        <button
                            class="tw-justify-center tw-font-semibold tw-py-2 tw-h-full tw-bg-orange-500 hover:tw-bg-orange-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-px-8 tw-border-0 tw-shadow-md"
                            @click.prevent="$emit('saveOffline')">
                            <span>Save</span>
                        </button>
                        <button
                            :disabled="isSyncing"
                            class="tw-justify-center tw-font-semibold tw-py-2 tw-h-full bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-px-8 tw-border-0 tw-shadow-md disabled:tw-opacity-50"
                            @click.prevent="$emit('syncAndPrint')">
                            <svg v-if="isSyncing" class="tw-animate-spin -tw-ml-1 tw-mr-2 tw-h-4 tw-w-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ isSyncing ? 'Syncing...' : 'Sync & Print' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePosStore } from '../../stores/posStore';

const pos = usePosStore();
const props = defineProps({
  isSyncing: Boolean
});
const emit = defineEmits(['saveOffline', 'syncAndPrint']);

const payment_type = ref('');
const payment_amount = ref('');
const notes = ref('');

const formatCurrency = (val) => {
  const amount = Number(val).toFixed(2);
  return `${pos.settings.currency || '$'} ${amount}`;
};

const add_payment = () => {
    if (!payment_type.value || !payment_amount.value) {
        alert("Please enter both payment type and amount");
        return;
    }
    const typeLabel = {
        1: 'Cash', 2: 'UPI', 3: 'Card', 4: 'Cheque', 5: 'Bank Transfer'
    }[payment_type.value] || 'Unknown';

    pos.payments.push({
        payment_type: payment_type.value,
        payment_type_name: typeLabel,
        amount: parseFloat(payment_amount.value),
        notes: notes.value
    });
    
    payment_type.value = '';
    payment_amount.value = '';
    notes.value = '';
};

const removePayment = (index) => {
    pos.payments.splice(index, 1);
};
</script>
