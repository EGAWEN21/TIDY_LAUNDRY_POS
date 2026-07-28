<template>
  <tr class="tw-border-b tw-border-neutral-200 dark:tw-border-neutral-800/50 tw-border-solid tw-transition-all tw-duration-200">
      <td class="tw-py-2 tw-px-2 lg:tw-w-[10%] tw-w-[10rem] tw-text-left tw-sticky tw-left-0 tw-bg-white dark:tw-bg-slate-800 tw-z-10 tw-shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
          <div class="tw-flex tw-flex-col">
              <div class="tw-text-xs tw-font-semibold">{{ item.service_name }}</div>
              <div class="tw-text-xs tw-font-normal text-primary-600">[{{ item.service_type_name }}]</div>
          </div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
          <div class="tw-flex tw-items-center tw-justify-center tw-gap-1 tw-w-full">
              <div class="tw-relative">
                  <button type="button" @click="showSwatches = !showSwatches" class="tw-flex tw-items-center tw-justify-center tw-w-7 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-border tw-border-gray-300 tw-rounded tw-transition-colors" title="Choose from quick swatches">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="tw-text-gray-600" viewBox="0 0 16 16">
                          <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" />
                          <path d="M16 8c0 3.15-1.866 2.585-3.567 2.07C11.42 9.763 10.465 9.473 10 10c-.603.683-.475 1.819-.351 2.92C9.826 14.495 9.996 16 8 16a8 8 0 1 1 8-8zm-8 7c.611 0 .654-.171.655-.176.078-.146.124-.464.07-1.119-.014-.168-.037-.37-.061-.591-.052-.464-.112-1.005-.118-1.462-.01-.707.083-1.61.704-2.314.369-.417.845-.578 1.272-.618.404-.038.812.026 1.16.104.343.077.702.186 1.025.284 1.064.326 1.756.54 1.886.541.01 0 .013 0 .013-.001C15.823 13.064 16 11.233 16 8A7 7 0 1 0 8 15z" />
                      </svg>
                  </button>
                  <div v-show="showSwatches" class="tw-absolute tw-z-50 tw-bg-white tw-shadow-lg tw-border tw-border-gray-200 tw-top-8 tw-left-0 tw-w-[170px] tw-rounded-xl tw-p-3">
                      <div class="tw-flex tw-justify-between tw-items-center tw-mb-2.5">
                          <span class="tw-font-semibold tw-uppercase tw-tracking-wider tw-text-[10px] tw-text-gray-600">Quick Colors</span>
                          <button @click="showSwatches = false" class="tw-text-gray-400 hover:tw-text-gray-600" aria-label="Close swatches">&times;</button>
                      </div>
                      <div class="tw-flex tw-flex-wrap tw-gap-2">
                          <div v-for="(swatch, sIndex) in quickSwatches" :key="sIndex" class="tw-relative tw-group tw-flex tw-items-center tw-justify-center">
                              <button type="button" @click="applySwatch(swatch)" class="tw-rounded-full hover:tw-scale-110 tw-transition-transform tw-cursor-pointer tw-p-0 tw-border tw-border-gray-200 tw-shadow-sm tw-w-[22px] tw-h-[22px]" :style="`background-color: ${swatch};`" :title="swatch" :aria-label="'Apply color ' + swatch"></button>
                              <button type="button" @click.stop="removeSwatch(sIndex)" class="tw-hidden group-hover:tw-flex tw-items-center tw-justify-center tw-absolute -tw-top-1 -tw-right-1 tw-z-10 tw-w-[14px] tw-h-[14px] tw-bg-red-500 tw-text-white tw-rounded-full tw-text-[10px] tw-leading-none tw-border-none tw-p-0 tw-cursor-pointer tw-shadow-sm" title="Remove" aria-label="Remove swatch">&times;</button>
                          </div>
                      </div>
                  </div>
              </div>
              <input type="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" class="tw-w-10 tw-h-6 tw-p-0 tw-border-0" v-model="item.color_code">
              <button type="button" @click="addSwatch(item.color_code)" class="tw-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-gray-100 hover:tw-bg-gray-200 tw-rounded tw-border tw-border-gray-300 tw-transition-colors tw-text-gray-700 tw-text-xs tw-font-bold" title="Save current color to quick swatches" aria-label="Save current color to quick swatches">+</button>
          </div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
              <input type="number" step="0.01" v-model.number="item.price" 
                     :readonly="!hasPermission('order_price_override')"
                     :class="{'tw-bg-neutral-100 tw-text-neutral-500': !hasPermission('order_price_override')}"
                     class="tw-ring-1 tw-px-1 tw-py-0.5 tw-rounded-md tw-w-[4.5rem]">
          </div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(item.price) }}</div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[15%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">
              <div class="tw-flex tw-items-center tw-gap-2 tw-justify-center tw-text-sm">
                  <button @click="pos.decreaseQty(index)" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md" aria-label="Decrease quantity">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash" viewBox="0 0 16 16"><path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8" /></svg>
                  </button>
                  {{ item.quantity }}
                  <button @click="pos.increaseQty(index)" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md" aria-label="Increase quantity">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" /></svg>
                  </button>
              </div>
          </div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(pos.calculateItemTax(item)) }}</div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center">{{ formatCurrency(pos.calculateItemTotal(item)) }}</div>
      </td>
      <td class="tw-py-2 tw-px-1 lg:tw-w-[10%] tw-w-[10rem] tw-text-center">
          <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center tw-gap-2">
              <button @click.prevent="pos.duplicateItem(index)" title="Duplicate" aria-label="Duplicate item" class="tw-px-2 tw-py-1 bg-info-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/></svg>
              </button>
              <button @click="pos.removeItem(index)" title="Remove item" aria-label="Remove item" class="tw-px-2 tw-py-1 tw-bg-red-500 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" /><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" /></svg>
              </button>
          </div>
      </td>
  </tr>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePosStore } from '../../stores/posStore';

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    }
});

const pos = usePosStore();

const hasPermission = (perm) => {
    const perms = window.PosConfig?.permissions || [];
    return perms.includes('all') || perms.includes(perm);
};

const formatCurrency = (val) => {
    const amount = Number(val).toFixed(2);
    return `${pos.settings.currency} ${amount}`;
};

const showSwatches = ref(false);

const quickSwatches = ref(JSON.parse(localStorage.getItem('pos-quick-swatches') || '["#ffffff", "#000000", "#ff0000", "#00ff00", "#0000ff", "#ffff00", "#ff00ff", "#00ffff"]'));

watch(quickSwatches, (newVal) => {
    localStorage.setItem('pos-quick-swatches', JSON.stringify(newVal));
}, { deep: true });

const addSwatch = (color) => {
    if (!quickSwatches.value.includes(color) && color) {
        if (quickSwatches.value.length >= 10) quickSwatches.value.shift();
        quickSwatches.value.push(color);
    }
};

const removeSwatch = (index) => {
    quickSwatches.value.splice(index, 1);
};

const applySwatch = (color) => {
    props.item.color_code = color;
    showSwatches.value = false;
};
</script>
