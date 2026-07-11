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
                                              type="radio" :id="'test' + item.id" name="test"
                                              v-model="selected_type" :value="item.id">
                                      </div>
                                      <label :for="'test' + item.id" class="form-label fw-medium text-primary-light mb-0">{{ item.service_type_name }}</label>
                                  </div>
                                  <div class="">{{ item.price }}</div>
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
import { ref } from 'vue';

const props = defineProps({
  availableServiceTypes: Array
});

const emit = defineEmits(['add-item']);
const selected_type = ref(null);

const addItem = () => {
  if (selected_type.value) {
    emit('add-item', selected_type.value);
    selected_type.value = null;
  }
};
</script>
