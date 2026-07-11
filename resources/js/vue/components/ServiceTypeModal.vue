<template>
  <div class="modal fade" id="servicetype" tabindex="-1" role="dialog" aria-labelledby="servicetype" aria-hidden="true" key="modal-servicetype">
      <div class="modal-dialog modal-md modal-dialog-centered" role="document">
          <div class="modal-content radius-16 bg-base">
              <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                  <h1 class="modal-title text-md" id="exampleModalLabel">Select Service Type</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-24">
                  <div class="row">
                      <template v-for="item in availableServiceTypes" :key="item.id">
                          <div class="col-12 mb-20">
                              <div class="tw-flex tw-items-center tw-justify-between">
                                  <div class="d-flex align-items-center gap-10 fw-medium text-lg">
                                      <div class="form-check style-check d-flex align-items-center">
                                          <input class="form-check-input radius-4 border border-neutral-500"
                                              type="checkbox" :id="'test' + item.id" name="test"
                                              :value="item.id"
                                              v-model="selected_types[item.id]">
                                      </div>
                                      <label :for="'test' + item.id" class="form-label fw-medium text-primary-light mb-0">{{ item.service_type_name }}</label>
                                  </div>
                                  <div class="">{{ formatPrice(item.price) }}</div>
                              </div>
                          </div>
                      </template>
                  </div>
                  <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                      <button type="button" data-bs-dismiss="modal" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                          <span>Cancel</span>
                      </button>
                      <button type="submit" @click.prevent="addItem" class="btn btn-primary border border-primary-600 text-md px-24 py-12 radius-8">
                          <span>Save</span>
                      </button>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';

const props = defineProps({
  availableServiceTypes: Array,
  currency: { type: String, default: '' }
});

const emit = defineEmits(['add-items']);

// Reactive object mirroring Livewire's selected_type = { id: true/false }
const selected_types = reactive({});

// Watch for when the modal gets new service types (i.e. user clicked a new product)
// and auto-select the first one, matching the online POS behavior
watch(() => props.availableServiceTypes, (newTypes) => {
  // Clear previous selections
  Object.keys(selected_types).forEach(key => delete selected_types[key]);
  
  // Auto-select the first service type
  if (newTypes && newTypes.length > 0) {
    selected_types[newTypes[0].id] = true;
  }
}, { immediate: true });

const formatPrice = (price) => {
  const num = parseFloat(price);
  return isNaN(num) ? price : num.toLocaleString();
};

const addItem = () => {
  // Collect all checked type IDs
  const checkedIds = Object.keys(selected_types).filter(id => selected_types[id] === true);
  if (checkedIds.length > 0) {
    emit('add-items', checkedIds.map(id => parseInt(id)));
  }
};
</script>
