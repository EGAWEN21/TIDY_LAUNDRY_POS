<template>
  <div class="tw-h-[calc(100vh-4rem)] tw-flex tw-flex-col dark:tw-bg-slate-900/50 tw-bg-white/50 tw-backdrop-blur-xl tw-shadow-[-4px_0_24px_rgba(0,0,0,0.05)] tw-border-l tw-border-white/20 dark:tw-border-white/5 tw-p-4 lg:tw-p-6" :class="shown && detached ? 'tw-absolute tw-inset-0 tw-w-full tw-z-50' : 'tw-hidden lg:tw-block lg:tw-w-7/12 tw-w-full tw-shrink-0'">
      <div class="tw-flex lg:tw-flex-row tw-flex-col lg:tw-items-center tw-items-start tw-gap-4 lg:tw-gap-8 tw-w-full tw-shrink-0">
          <div class="tw-flex tw-min-w-fit tw-shrink tw-flex-col">
              <div class="tw-text-sm">Order : <span class="tw-font-bold">#{{ pos.cartOrderId }}</span></div>
              <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-text-sm tw-relative">
                      Date : <span class="tw-font-bold">{{ pos.orderDate }}</span>
                      <input type="date" v-model="pos.orderDate" name="" class="tw-opacity-0 tw-absolute tw-pointer-events-none" ref="datePicker">
                  </div>
                  <button @click="$refs.datePicker.showPicker()" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                          <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                          <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                      </svg>
                  </button>
              </div>

              <div class="tw-flex tw-items-center tw-gap-2">
                  <div class="tw-text-sm tw-relative">
                      Delivery Date : <span class="tw-font-bold">{{ pos.deliveryDate }}</span>
                      <input type="date" v-model="pos.deliveryDate" name="" class="tw-opacity-0 tw-absolute tw-pointer-events-none" ref="deliveryDatePicker">
                  </div>
                  <button @click="$refs.deliveryDatePicker.showPicker()" class="tw-px-2 tw-py-1 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                          <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z" />
                          <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2" />
                      </svg>
                  </button>
              </div>
          </div>
          <div class="tw-flex tw-items-center tw-gap-2 tw-w-full tw-shrink">
              <div class="tw-flex tw-flex-col tw-gap-2 tw-w-full">
                  <div v-if="pos.cartCustomer" class="tw-flex tw-items-center tw-justify-between tw-w-full tw-border tw-rounded-lg tw-px-3 tw-py-2 tw-bg-green-50/80 dark:tw-bg-green-900/30 tw-border-green-300 dark:tw-border-green-700">
                      <div class="tw-flex tw-flex-col">
                          <span class="tw-font-bold tw-text-sm tw-text-green-800 dark:tw-text-green-300">{{ pos.cartCustomer.name }}</span>
                          <span class="tw-text-xs tw-text-green-600 dark:tw-text-green-400">{{ pos.cartCustomer.phone }}</span>
                      </div>
                      <button @click="pos.cartCustomer = null" class="tw-text-red-500 hover:tw-text-red-700 dark:tw-text-red-400 dark:hover:tw-text-red-300 tw-font-bold tw-px-2 tw-text-lg" aria-label="Remove customer">&times;</button>
                  </div>
                  <div class="icon-field tw-relative tw-w-full tw-items-center">
                      <span class="icon -tw-translate-y-[2px]">
                          <iconify-icon icon="f7:person"></iconify-icon>
                      </span>
                      <input type="text" class="tw-block tw-w-full tw-rounded-md tw-border tw-border-gray-300 tw-px-3 tw-py-2 tw-text-sm tw-shadow-sm focus:tw-border-blue-500 focus:tw-outline-none" :placeholder="pos.cartCustomer ? 'Change Customer...' : 'Select A Customer'" @focus="showCustomerDropdown = true" @blur="hideCustomerDropdown" v-model="pos.customerQuery">
                      <div v-show="showCustomerDropdown && filteredCustomers.length > 0" class="tw-absolute tw-top-[100%] tw-left-0 tw-w-full tw-z-20 tw-shadow-md tw-bg-white tw-rounded-lg">
                          <ul>
                              <li v-for="row in filteredCustomers" :key="row.id" class="dropdown-item px-16 py-8 rounded text-secondary-light bg-hover-neutral-200 text-hover-neutral-900 tw-cursor-pointer" @mousedown.prevent="selectCustomer(row)">{{ row.name }} - {{ row.phone }}</li>
                          </ul>
                      </div>
                  </div>
              </div>
              <button type="button" data-bs-toggle="modal" data-bs-target="#addcustomer" class="tw-px-4 tw-py-3 bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill-add" viewBox="0 0 16 16">
                      <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0m-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                      <path d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4" />
                  </svg>
              </button>
          </div>
      </div>
      <div class="tw-w-full tw-flex tw-flex-col tw-flex-1 tw-min-h-0 tw-mt-6 tw-rounded-2xl tw-overflow-clip tw-border tw-border-white/60 dark:tw-border-white/10 tw-shadow-lg tw-bg-white/60 dark:tw-bg-slate-800/60 tw-backdrop-blur-md">
          <div class="tw-flex tw-flex-col tw-h-[calc(100vh-31rem)] lg:tw-h-[calc(100vh-25rem)] lg:tw-w-full tw-overflow-auto custom-scroll tw-shadow-[inset_-12px_0_15px_-15px_rgba(0,0,0,0.15)] tw-pr-2">
              <div class="tw-flex tw-flex-col lg:tw-w-full tw-w-full tw-min-w-[60rem]">
                  <table class="tw-w-full tw-text-xs tw-whitespace-nowrap">
                      <thead class="tw-sticky tw-top-0 tw-z-20 tw-bg-slate-100/90 dark:tw-bg-slate-700/90 tw-backdrop-blur-sm tw-text-slate-600 dark:tw-text-slate-300 tw-uppercase tw-tracking-wider tw-font-semibold tw-border-b tw-border-white/40 dark:tw-border-white/10 tw-shadow-sm">
                          <tr>
                              <th class="tw-py-3 tw-px-3 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-left tw-sticky tw-left-0 tw-bg-slate-100 dark:tw-bg-slate-700 tw-z-30 tw-shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Service</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Color</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Price</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">Rate</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[15%] tw-text-center">QTY</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">Tax ({{ pos.settings.tax_percentage }}%)</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[10%] tw-text-center">Total</th>
                              <th class="tw-py-2 tw-px-1 tw-text-xs tw-w-[10rem] lg:tw-w-[5%] tw-text-center"></th>
                          </tr>
                      </thead>
                      <TransitionGroup name="cart-list" tag="tbody">
                              <CartItemRow v-for="(item, key) in pos.cart" :key="key" :item="item" :index="key" />
                      </TransitionGroup>
                  </table>
              </div>
          </div>
          <CartTotals />
      </div>
      <div class="tw-flex tw-flex-wrap lg:tw-flex-nowrap tw-items-center tw-gap-2 tw-mt-1 tw-p-2 tw-w-full lg:tw-h-14">
          <button class="tw-w-[calc(50%-0.25rem)] lg:tw-w-auto lg:tw-flex-1 tw-justify-center tw-font-semibold tw-py-2 lg:tw-h-full bg-success-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md" data-bs-toggle="modal" data-bs-target="#payment">
              <span>Payment</span>
          </button>
          <button class="tw-w-[calc(50%-0.25rem)] lg:tw-w-auto lg:tw-flex-1 tw-justify-center tw-font-semibold tw-py-2 lg:tw-h-full bg-info-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md" @click.prevent="$emit('save', 'cash')">
              <span>Cash</span>
          </button>
          <button class="tw-flex-1 tw-justify-center tw-font-semibold tw-py-2 lg:tw-h-full tw-bg-orange-500 hover:tw-bg-orange-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md" @click.prevent="$emit('saveOffline')">
              <span>Save</span>
          </button>
          <button :disabled="isSyncing" class="tw-flex-1 tw-justify-center tw-font-semibold tw-py-2 lg:tw-h-full bg-primary-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md disabled:tw-opacity-50" @click.prevent="$emit('syncAndPrint')">
              <svg v-if="isSyncing" class="tw-animate-spin -tw-ml-1 tw-mr-2 tw-h-4 tw-w-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span class="tw-text-xs sm:tw-text-sm">{{ isSyncing ? 'Syncing...' : 'Sync & Print' }}</span>
          </button>
          <button class="tw-w-12 tw-shrink-0 tw-py-2 lg:tw-h-full bg-danger-600 tw-rounded-md tw-text-white tw-flex tw-items-center tw-justify-center tw-gap-1.5 tw-border-0 tw-shadow-md tw-min-h-[2.5rem]" @click.prevent="$emit('clearAll')">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z" /><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z" /></svg>
          </button>
      </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePosStore } from '../../stores/posStore';
import CartItemRow from './CartItemRow.vue';
import CartTotals from './CartTotals.vue';

const props = defineProps({
  shown: Boolean,
  detached: Boolean,
  isSyncing: Boolean
});

const emit = defineEmits(['save', 'clearAll', 'saveOffline', 'syncAndPrint']);

const pos = usePosStore();

const showCustomerDropdown = ref(false);

const filteredCustomers = computed(() => {
  if (!pos.customerQuery) return [...pos.customers].reverse().slice(0, 5);
  const q = pos.customerQuery.toLowerCase();
  return pos.customers.filter(c => 
    (c.name && c.name.toLowerCase().includes(q)) || 
    (c.phone && c.phone.includes(q))
  ).slice(0, 5);
});

const hideCustomerDropdown = () => {
    setTimeout(() => {
        showCustomerDropdown.value = false;
    }, 150);
};

const selectCustomer = (cust) => {
  pos.cartCustomer = cust;
  pos.customerQuery = '';
  showCustomerDropdown.value = false;
};
</script>

<style>
/* Vue Transition Group Animation for Cart items */
.cart-list-enter-active,
.cart-list-leave-active {
  transition: all 0.2s ease;
}
.cart-list-enter-from,
.cart-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
