<template>
<ReAuthModal />
<SyncQueueModal />
<div v-if="fatalError" class="tw-absolute tw-inset-0 tw-z-50 tw-bg-red-50 tw-text-red-900 tw-p-8 tw-overflow-y-auto">
    <h1 class="tw-text-2xl tw-font-bold tw-mb-4">Fatal UI Error</h1>
    <pre class="tw-whitespace-pre-wrap tw-text-sm">{{ fatalError }}</pre>
    <button @click="fatalError = null" class="tw-mt-4 tw-px-4 tw-py-2 tw-bg-red-600 tw-text-white tw-rounded">Dismiss</button>
</div>
<div :class="[isDarkMode ? 'premium-bg-dark' : 'premium-bg-light', 'tw-w-full tw-min-h-screen tw-transition-colors tw-duration-300']">
    <div :class="[isDarkMode ? 'glass-panel-dark' : 'glass-panel-light', 'tw-w-full tw-flex tw-justify-between tw-items-center tw-shadow-sm tw-border-b tw-z-10 tw-relative']">
        <div class="tw-flex tw-gap-2 tw-px-3 tw-py-2 tw-items-center">
            <a href="/admin/orders" class="no-underline">
                <button
                    class="bg-primary-600 tw-text-white tw-text-xs radius-8 px-20 tw-py-2 d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor" class="tw-size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back</span>
                </button>
            </a>
            
            <template v-if="detached">
                <button
                    class="tw-px-2 tw-py-1.5 bg-primary-600 tw-w-fit tw-rounded-md tw-text-white tw-flex tw-items-center tw-gap-1.5 tw-border-0 tw-shadow-md"
                    @click="shown = !shown">
                    
                        <div v-if="!shown" class="tw-flex  tw-items-center tw-gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>
                            <span class="text-sm ">Cart</span>
                        </div>
                    
                    
                        <div v-else class="tw-flex tw-items-center tw-gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="tw-size-4">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                            </svg>

                            <span class="text-sm ">Products</span>
                        </div>
                    
                </button>
            </template>
            
            <div class="tw-ml-4 tw-flex tw-items-center">
                <span v-if="pos.isOnline" class="tw-bg-green-100 tw-text-green-800 tw-text-xs tw-font-bold tw-px-3 tw-py-1.5 tw-rounded tw-shadow-sm tw-flex tw-items-center tw-gap-1">
                    <span class="tw-w-2 tw-h-2 tw-bg-green-500 tw-rounded-full"></span> Online
                </span>
                <span v-else class="tw-bg-red-100 tw-text-red-800 tw-text-xs tw-font-bold tw-px-3 tw-py-1.5 tw-rounded tw-shadow-sm tw-flex tw-items-center tw-gap-1">
                    <span class="tw-w-2 tw-h-2 tw-bg-red-500 tw-rounded-full"></span> Offline Mode
                </span>
            </div>
            <button @click="toggleTheme" class="tw-ml-4 tw-p-2 tw-rounded-full tw-transition-colors hover:tw-bg-slate-200 dark:hover:tw-bg-slate-700" aria-label="Toggle dark mode">
                <svg v-if="!isDarkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-5 tw-h-5 tw-text-slate-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-5 tw-h-5 tw-text-slate-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                </svg>
            </button>
            <button data-bs-toggle="modal" data-bs-target="#syncQueueModal" class="tw-ml-2 tw-p-2 tw-rounded-full tw-transition-colors hover:tw-bg-slate-200 dark:hover:tw-bg-slate-700 tw-text-slate-600 dark:tw-text-slate-300" title="Sync Manager" aria-label="Open Sync Manager">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21v-5h5"/></svg>
            </button>
        </div>
    </div>

    <div class="tw-w-[100%] tw-h-[calc(100vh-3.5rem)] tw-flex lg:tw-flex-row tw-flex-col tw-relative tw-overflow-hidden">
        <div class="lg:tw-w-5/12 tw-w-full tw-flex-col tw-h-full tw-p-3 tw-overflow-y-auto">
            <ProductGrid 
                v-model:searchQuery="searchQuery" 
                :filteredServices="filteredServices" 
                @select-service="selectService" 
            />
        </div>
        <CartTable :shown="shown" :detached="detached" :isSyncing="isSyncingPrint" @save="saveOffline" @saveOffline="saveOffline" @syncAndPrint="syncAndPrint" @clearAll="clearAll" />
    </div>
    </div>


    <Teleport to="body">
        <InstallPrompt />
        <ServiceTypeModal :availableServiceTypes="availableServiceTypes" :currency="pos.settings.currency" @add-items="addItems" />
        <NotesModal />
        <DiscountModal />
        <AddonModal />
    </Teleport>
        <PaymentModal :isSyncing="isSyncingPrint" @saveOffline="saveOffline" @syncAndPrint="syncAndPrint" />
        <CustomerModal @customer-created="(cust) => selectCustomer(cust)" />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, onErrorCaptured } from 'vue';
import { usePosStore } from '../stores/posStore';
import { db } from '../db';
import PaymentModal from './components/PaymentModal.vue';
import CustomerModal from './components/CustomerModal.vue';
import ProductGrid from './components/ProductGrid.vue';
import ServiceTypeModal from './components/ServiceTypeModal.vue';
import NotesModal from './components/NotesModal.vue';
import DiscountModal from './components/DiscountModal.vue';
import AddonModal from './components/AddonModal.vue';
import CartTable from './components/CartTable.vue';
import InstallPrompt from './components/InstallPrompt.vue';
import ReAuthModal from './components/ReAuthModal.vue';
import SyncQueueModal from './components/SyncQueueModal.vue';
import { toast } from 'vue3-toastify';

const pos = usePosStore();

const fatalError = ref(null);
onErrorCaptured((err, instance, info) => {
    fatalError.value = `${err.name}: ${err.message}\n\nStack:\n${err.stack}\n\nInfo:\n${info}`;
    console.error('Captured Vue Error:', err, info);
    return false; // Prevent further propagation
});

const generateUUID = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

const searchQuery = ref('');
const selected_type = ref(null);
const shown = ref(false);


const detached = ref(false);
const isMobileCartView = ref(false);

const checkDetached = () => {
    detached.value = window.innerWidth < 1024;
};

// Service Selection State
const showServiceTypeModal = ref(false);
const selectedService = ref(null);
const availableServiceTypes = ref([]);

// Customer State
const customerQuery = ref('');
const showNewCustomerModal = ref(false);
const newCustomer = ref({ name: '', phone: '' });

// Theme Logic
const isDarkMode = ref(localStorage.getItem('pos-theme') === 'dark');
const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('pos-theme', isDarkMode.value ? 'dark' : 'light');
    if (isDarkMode.value) {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
};

onMounted(async () => {
  if (isDarkMode.value) {
      document.documentElement.setAttribute('data-theme', 'dark');
  }
  checkDetached();
  window.addEventListener('resize', checkDetached);
  await pos.initialize();
});

onUnmounted(() => {
  window.removeEventListener('resize', checkDetached);
});

// Computed properties
const filteredServices = computed(() => {
  if (!searchQuery.value) return pos.services;
  const q = searchQuery.value.toLowerCase();
  return pos.services.filter(s => s.service_name.toLowerCase().includes(q));
});

const selectCustomer = (cust) => {
  pos.cartCustomer = cust;
  pos.customerQuery = '';
};

// Actions
const selectService = (service) => {
  selectedService.value = service;
  
  // Find available types for this service by checking serviceDetails
  const details = pos.serviceDetails.filter(sd => sd.service_id === service.id);
  const typeIds = details.map(d => d.service_type_id);
  
  availableServiceTypes.value = pos.serviceTypes
    .filter(st => typeIds.includes(st.id))
    .sort((a, b) => {
      if (a.position !== b.position) {
        return (a.position || 0) - (b.position || 0);
      }
      return a.id - b.id;
    })
    .map(st => {
      const d = details.find(d => d.service_type_id === st.id);
      return { ...st, price: d.service_price };
    });
    
  // Show modal
  const modalEl = document.getElementById('servicetype');
  if (modalEl) {
      if (typeof window.bootstrap !== 'undefined') {
          window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
      } else if (typeof window.$ !== 'undefined') {
          window.$('#servicetype').modal('show');
      }
  }
};

const addToCart = (service, type) => {
  // Tax logic conversion for price based on PosScreen.php
  let finalPrice = parseFloat(type.price);
  
  pos.cart.push({
    service_id: service.id,
    service_name: service.service_name,
    service_type_id: type.id,
    service_type_name: type.service_type_name,
    price: finalPrice, // Keeping base price, tax computed in getters
    quantity: 1,
    color_code: ''
  });
};

const clearAll = () => {
  pos.cart = [];
  pos.cartCustomer = null;
  pos.customerQuery = '';
  pos.payments = [];
  pos.cartAddons = [];
  pos.discount = 0;
  pos.paymentNotes = '';
};

const isSyncingPrint = ref(false);

const buildOrderData = (type = 'save') => {
  if (pos.cart.length === 0) {
    toast.error("Cart is empty");
    return null;
  }
  
  const orderData = {
    uuid: generateUUID(),
    customer_id: pos.cartCustomer ? pos.cartCustomer.id : null,
    customer_name: pos.cartCustomer ? pos.cartCustomer.name : null,
    phone_number: pos.cartCustomer ? pos.cartCustomer.phone : null,
    new_customer: (pos.cartCustomer && pos.cartCustomer.sync_status === 'pending') ? pos.cartCustomer : null,
    order_date: pos.orderDate,
    delivery_date: pos.deliveryDate,
    sub_total: pos.cartSubTotal,
    addon_total: pos.cartAddonsTotal,
    discount: pos.cartDiscount,
    tax_percentage: pos.settings.tax_percentage,
    tax_amount: pos.cartTax,
    tax_type: pos.settings.tax_type,
    taxable_amount: pos.cartTotal - pos.cartTax,
    total: pos.cartTotal,
    status: 0,
    details: pos.cart.map(item => ({
      service_id: item.service_id,
      service_name: item.service_type_name,
      service_quantity: item.quantity,
      service_detail_total: item.price * item.quantity,
      service_price: item.price,
      color_code: item.color_code
    })),
    payments: [...pos.payments]
  };

  if(type === 'cash') {
    orderData.payments.push({
      payment_type: 1, // 1 is Cash
      payment_type_name: 'Cash',
      amount: orderData.total,
      notes: "Offline Cash Payment"
    });
  }

  return orderData;
};

const saveOffline = async (type = 'save') => {
  const orderData = buildOrderData(type);
  if (!orderData) return;

  // Save to offline queue
  const plainOrderData = JSON.parse(JSON.stringify(orderData));
  await db.syncQueue.add({
    type: 'order',
    data: plainOrderData,
    timestamp: Date.now(),
    status: 'pending',
    retry_count: 0
  });

  toast.success("Order Saved Offline! It will sync automatically in the background.");
  closeModalsAndReset();
};

const syncAndPrint = async () => {
  const orderData = buildOrderData('save');
  if (!orderData) return;

  isSyncingPrint.value = true;

  try {
    // 1. Temporarily save to queue
    const plainOrderData = JSON.parse(JSON.stringify(orderData));
    await db.syncQueue.add({
      type: 'order',
      data: plainOrderData,
      timestamp: Date.now(),
      status: 'pending',
      retry_count: 0
    });

    // 2. Await full sync
    const syncResult = await pos.syncOfflineData();

    if (!syncResult.success) {
      toast.warning("You are currently offline or sync failed. The order is queued securely.");
      closeModalsAndReset();
      return;
    }

    // 3. Find our specific order in the syncResult mapped IDs
    const serverOrderId = syncResult.syncedOrders[orderData.uuid];
    const requiresApproval = syncResult.requiresApproval[orderData.uuid];

    if (!serverOrderId) {
      toast.error("Sync completed, but could not retrieve the server Order ID for printing.");
      closeModalsAndReset();
      return;
    }

    if (requiresApproval) {
      toast.info("Order requires manager approval. Cannot print receipt yet.");
    } else {
      // 4. Open the native thermal printer bypass!
      window.open('/admin/orders/print/' + serverOrderId, '_blank');
    }

    closeModalsAndReset();

  } catch (error) {
    console.error("Print Sync Error:", error);
    toast.error("An error occurred while syncing. Order is saved offline.");
    closeModalsAndReset();
  } finally {
    isSyncingPrint.value = false;
  }
};

const closeModalsAndReset = () => {
  const paymentModalEl = document.getElementById('payment');
  if (paymentModalEl && paymentModalEl.classList.contains('show')) {
      if (typeof window.bootstrap !== 'undefined') {
          const mInst = window.bootstrap.Modal.getOrCreateInstance(paymentModalEl);
          if (mInst) mInst.hide();
      } else if (typeof window.$ !== 'undefined') {
          window.$('#payment').modal('hide');
      }
  }
  clearAll();
};

// Handles multiple service type selections (matching the online POS behavior)
const addItems = (typeIds) => {
  if (!selectedService.value || !typeIds || typeIds.length === 0) return;
  
  typeIds.forEach(typeId => {
    const type = availableServiceTypes.value.find(t => t.id == typeId);
    if (type) {
      addToCart(selectedService.value, type);
    }
  });
  
  selected_type.value = null;
  
  // Hide modal
  const modalEl = document.getElementById('servicetype');
  if (modalEl && modalEl.classList.contains('show')) {
    if (typeof window.bootstrap !== 'undefined') {
      const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
      if (modalInstance) modalInstance.hide();
    } else if (typeof window.$ !== 'undefined') {
      window.$('#servicetype').modal('hide');
    }
  }
};

</script>