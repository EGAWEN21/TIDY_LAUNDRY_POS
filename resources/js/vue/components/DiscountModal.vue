<template>
  <div class="modal fade" id="discount" tabindex="-1" role="dialog" aria-labelledby="discount" aria-hidden="true" key="modal-discount">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content radius-16 bg-base">
              <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                  <h1 class="modal-title text-md" id="exampleModalLabel">Discount</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-24">
                  <div class="tw-flex tw-gap-2 tw-flex-col">
                      <div class="">Discount</div>
                      <input type="number" v-model="pos.discount" class="form-control" placeholder="Enter Amount">
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
</script>
