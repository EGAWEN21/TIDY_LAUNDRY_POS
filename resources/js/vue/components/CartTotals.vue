<template>
  <div class="tw-mt-2 tw-flex tw-justify-between tw-text-sm tw-p-4 tw-bg-slate-50/50 dark:tw-bg-slate-800/80 tw-border-t tw-border-white/40 dark:tw-border-white/10 tw-rounded-b-2xl dark:tw-text-slate-200">
      <div class="tw-flex tw-flex-col tw-gap-2">
          <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
              <div class="tw-flex tw-items-center tw-gap-2">
                  Addon <button data-bs-toggle="modal" data-bs-target="#addons" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400" aria-label="Add Addon"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-box-fill" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15.528 2.973a.75.75 0 0 1 .472.696v8.662a.75.75 0 0 1-.472.696l-7.25 2.9a.75.75 0 0 1-.557 0l-7.25-2.9A.75.75 0 0 1 0 12.331V3.669a.75.75 0 0 1 .471-.696L7.443.184l.004-.001.274-.11a.75.75 0 0 1 .558 0l.274.11.004.001zm-1.374.527L8 5.962 1.846 3.5 1 3.839v.4l6.5 2.6v7.922l.5.2.5-.2V6.84l6.5-2.6v-.4l-.846-.339Z" /></svg></button> :
              </div>
              <div class="tw-font-bold">{{ formatCurrency(pos.cartAddonsTotal) }}</div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
              <div class="">Total Items :</div>
              <div class="tw-font-bold">{{ pos.cartTotalItems }}</div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
              <div class="">Sub Total :</div>
              <div class="tw-font-bold">{{ formatCurrency(pos.cartSubTotal) }}</div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2">
              <div class="tw-flex tw-items-center tw-gap-2">
                  Notes : <button data-bs-toggle="modal" data-bs-target="#notesModal" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400" aria-label="Edit Notes"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" /><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z" /></svg></button>
              </div>
          </div>
      </div>
      <div class="tw-flex tw-flex-col tw-gap-2">
          <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
              <div class="">Tax ({{ pos.settings.tax_percentage }}%) :</div>
              <div class="tw-font-bold">{{ formatCurrency(pos.cartTax) }}</div>
          </div>
          <div class="tw-flex tw-items-end tw-justify-end tw-gap-2">
              <div class="tw-flex tw-items-center tw-gap-2">
                  Discount <button v-if="hasPermission('order_discount_apply')" data-bs-toggle="modal" data-bs-target="#discount" class="tw-px-1 tw-py-1 tw-rounded-md tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md bg-primary-600 tw-text-white tw-border tw-border-solid tw-bg-transparent tw-border-neutral-400" aria-label="Apply Discount"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0" /></svg></button> :
              </div>
              <div class="tw-font-bold">{{ formatCurrency(pos.cartDiscount) }}</div>
          </div>
          <div class="tw-flex tw-items-center tw-justify-end tw-gap-2">
              <div class="">Gross Total :</div>
              <div class="tw-font-extrabold">{{ formatCurrency(pos.cartTotal) }}</div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { usePosStore } from '../../stores/posStore';

const pos = usePosStore();

const hasPermission = (perm) => {
    const perms = window.PosConfig?.permissions || [];
    return perms.includes('all') || perms.includes(perm);
};

const formatCurrency = (val) => {
    const amount = Number(val).toFixed(2);
    return `${pos.settings.currency} ${amount}`;
};
</script>
