<template>
  <div class="modal fade" id="notesModal" tabindex="-1" role="dialog" aria-labelledby="notesModal" aria-hidden="true" key="modal-notesModal">
      <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content radius-16 bg-base">
              <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                  <h1 class="modal-title text-md" id="exampleModalLabel">Notes / Remarks</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-24">
                  <div class="tw-flex tw-gap-2 tw-flex-col">
                      <div class="">Notes / Remarks</div>
                      <textarea rows="3" v-model="pos.paymentNotes" class="form-control" placeholder="Enter Notes"></textarea>
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
