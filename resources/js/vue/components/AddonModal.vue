<template>
  <div class="modal fade" id="addons" tabindex="-1" role="dialog" aria-labelledby="addons" aria-hidden="true" key="modal-addons">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content radius-16 bg-base">
              <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                  <h1 class="modal-title text-md" id="exampleModalLabel">Addons</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-24">
                  <template v-for="row in pos.addons" :key="row.id">
                      <div class="col-12 mb-20 tw-flex tw-justify-between tw-items-center">
                          <div class="d-flex align-items-center gap-10 fw-medium text-lg">
                              <div class="form-check style-check d-flex align-items-center">
                                  <input class="form-check-input radius-4 border border-neutral-500" type="checkbox"
                                      name="addon" :id="'addon' + row.id"
                                      v-model="pos.cartAddonIds" :value="row.id">
                              </div>
                              <label :for="'addon' + row.id" class="form-label fw-medium text-primary-light mb-0">{{ row.addon_name }}</label>
                          </div>
                          <div class="text-primary">{{ formatCurrency(row.addon_price) }}</div>
                      </div>
                  </template>
                  
                  <div v-if="pos.addons.length === 0" class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
                      <div class="">No addons were found!</div>
                  </div>
                  
                  <div class="d-flex align-items-start justify-content-end gap-3 mt-24">
                      <button data-bs-dismiss="modal" class="border border-danger-600 bg-hover-danger-200 text-danger-600 text-md px-40 py-11 radius-8">
                          Close
                      </button>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { usePosStore } from '../../stores/posStore';

const pos = usePosStore();

const formatCurrency = (val) => {
  const amount = Number(val).toFixed(2);
  return `${pos.settings.currency} ${amount}`;
};
</script>
