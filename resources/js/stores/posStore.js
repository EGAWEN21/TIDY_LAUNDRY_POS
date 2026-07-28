import { defineStore } from 'pinia';

import {
    db,
    claimLegacyQueueRecords,
    getCurrentPosUserId,
    isOwnedByCurrentPosUser,
    isSyncEligible
} from '../db';
import axios from 'axios';
import { useSyncEngine } from '../composables/useSyncEngine';

export const usePosStore = defineStore('pos', {
    state: () => ({
        services: [],
        serviceTypes: [],
        serviceDetails: [],
        addons: [],
        customers: [],
        settings: { tax_percentage: 0, tax_type: 1, currency: '$' },
        cart: [],
        cartUuid: null,
        cartAddons: [],
        cartCustomer: null,
        payments: [],
        cartDiscountType: null,
        paymentNotes: '',
        discount: 0,
        orderDate: new Date().toISOString().split('T')[0],
        deliveryDate: (() => { const d = new Date(); d.setDate(d.getDate() + 2); return d.toISOString().split('T')[0]; })(),
        customerQuery: '',
        isOnline: navigator.onLine,
        isSyncing: false,
        lastSyncTimestamp: 0,
        needsReAuth: false,
        syncErrors: 0,
    }),
    
    getters: {
        cartOrderId: (state) => {
            return `OFFLINE-${Date.now().toString().slice(-6)}`;
        },
        currentBalance: (state) => {
            const total = state.cart.reduce((t, i) => t + (i.price * i.quantity), 0) + 
                          state.cartAddons.reduce((t, a) => t + a.price, 0);
            const paid = state.payments.reduce((t, p) => t + p.amount, 0);
            return total - paid;
        },
        cartSubTotal: (state) => {
            return state.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
        },
        cartTotalItems: (state) => {
            return state.cart.reduce((total, item) => total + item.quantity, 0);
        },
        cartAddonsTotal: (state) => {
            return state.cartAddons.reduce((total, addon) => total + addon.price, 0);
        },
        cartTax: (state) => {
            // Simplified tax logic, should match PosScreen.php exactly
            let total = 0;
            const sub = state.cartSubTotal + state.cartAddonsTotal;
            if(state.settings.tax_type == 2) {
                // Tax included in price
                const taxFree = sub * (100 / (100 + parseFloat(state.settings.tax_percentage)));
                total = sub - taxFree;
            } else {
                // Tax excluded
                total = sub * (parseFloat(state.settings.tax_percentage) / 100);
            }
            return total;
        },
        cartDiscount: (state) => {
            return parseFloat(state.discount) || 0;
        },
        cartTotal: (state) => {
            let total = 0;
            if(state.settings.tax_type == 2) {
                total = state.cartSubTotal + state.cartAddonsTotal;
            } else {
                total = state.cartSubTotal + state.cartAddonsTotal + state.cartTax;
            }
            return Math.max(0, total - (parseFloat(state.discount) || 0));
        },
        calculateItemTax: (state) => {
            return (item) => {
                let tax = 0;
                if(state.settings.tax_type == 2) {
                    const taxFree = item.price * (100 / (100 + parseFloat(state.settings.tax_percentage)));
                    tax = (item.price - taxFree) * item.quantity;
                } else {
                    tax = (item.price * (parseFloat(state.settings.tax_percentage) / 100)) * item.quantity;
                }
                return tax;
            }
        },
        calculateItemTotal: (state) => {
            return (item) => {
                if(state.settings.tax_type == 2) {
                    return item.price * item.quantity;
                }
                const tax = (item.price * (parseFloat(state.settings.tax_percentage) / 100)) * item.quantity;
                return (item.price * item.quantity) + tax;
            }
        }
    },

    actions: {
        async initialize() {
            // Request persistent storage to prevent silent eviction
            if (navigator.storage && navigator.storage.persist) {
                navigator.storage.persist().then(granted => {
                    if (granted) console.log("Storage will not be cleared except by explicit user action");
                });
            }

            window.addEventListener('online', this.updateOnlineStatus);
            window.addEventListener('offline', this.updateOnlineStatus);
            
            axios.defaults.headers.common['Accept'] = 'application/json';
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            if (window.PosConfig.apiToken) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${window.PosConfig.apiToken}`;
            } else {
                delete axios.defaults.headers.common['Authorization'];
            }

            await claimLegacyQueueRecords();

            if(this.isOnline) {
                // FLUSH OFFLINE QUEUE FIRST before overwriting catalog to prevent App Boot data rot!
                await this.syncOfflineData();
                await this.fetchFromServer();
            } else {
                await this.loadFromLocal();
            }

            // Background Auto-Updater Engine
            setInterval(async () => {
                if(this.isOnline && !this.isSyncing) {
                    // Pause background polling if cart is active or if there are items in the queue
                    const actionableQueueCount = await db.syncQueue
                        .filter(item => isOwnedByCurrentPosUser(item) && isSyncEligible(item))
                        .count();
                    if (this.cart.length > 0) {
                        return; // Do not sync or refresh catalog while active cart work is present
                    }
                    if (actionableQueueCount > 0) {
                        await this.syncOfflineData();
                        return; // Process due queue work before refreshing the catalog
                    }

                    try {
                        const res = await axios.get('/api/pos/check-update');
                        if(res.data.timestamp > this.lastSyncTimestamp) {
                            console.log('Background Sync: Updates detected. Silently updating local database...');
                            await this.fetchFromServer();
                        }
                    } catch (e) {
                        // ignore background errors
                    }
                }
            }, 60000); // 60 seconds

            // Persist cart state to IndexedDB on every mutation
            this.$subscribe(async (mutation, state) => {
                if (!window.PosConfig || !window.PosConfig.user) return;
                
                try {
                    if (state.cart.length > 0 || state.cartAddons.length > 0 || state.cartCustomer) {
                        await db.cart.put({
                            id: window.PosConfig.user.id, // Isolate draft per user
                            uuid: 'OFFLINE-CART',
                            cart_uuid: state.cartUuid,
                            user_id: window.PosConfig.user.id,
                            items: JSON.parse(JSON.stringify(state.cart)),
                            addons: JSON.parse(JSON.stringify(state.cartAddons)),
                            customer_id: state.cartCustomer ? state.cartCustomer.id : null,
                            total: state.cartTotal,
                            tax: state.cartTax,
                            discount: state.discount,
                            payments: JSON.parse(JSON.stringify(state.payments)),
                            status: 'draft',
                            notes: state.paymentNotes
                        });
                    } else {
                        // Cart has been explicitly cleared (e.g., checkout complete), so delete the draft
                        await db.cart.delete(window.PosConfig.user.id);
                    }
                } catch (err) {
                    console.error('Failed to save draft to IndexedDB:', err);
                }
            });
        },

        updateOnlineStatus(e) {
            this.isOnline = navigator.onLine;
            if(this.isOnline) {
                this.syncOfflineData();
                // Also trigger an immediate check for updates when coming online
                axios.get('/api/pos/check-update').then(async res => {
                    if(res.data.timestamp > this.lastSyncTimestamp) {
                        const actionableQueueCount = await db.syncQueue
                                                .filter(item => isOwnedByCurrentPosUser(item) && isSyncEligible(item))
                                                .count();
                        if (this.cart.length === 0 && actionableQueueCount === 0) {
                            this.fetchFromServer();
                        }
                    }
                }).catch(err => {});
            }
        },

        async fetchFromServer() {
            try {
                const response = await axios.get('/api/pos/init');
                const data = response.data.data ?? response.data;
                this.lastSyncTimestamp = data.timestamp || 0;
                
                // Clear old data and save new
                await db.transaction('rw', db.services, db.serviceTypes, db.serviceDetails, db.addons, db.settings, async () => {
                    await db.services.clear();
                    await db.serviceTypes.clear();
                    await db.serviceDetails.clear();
                    await db.addons.clear();
                    await db.settings.clear();

                    await db.services.bulkAdd(data.services);
                    await db.serviceTypes.bulkAdd(data.service_types);
                    await db.serviceDetails.bulkAdd(data.service_details);
                    await db.addons.bulkAdd(data.addons);
                    await db.settings.put({ id: 1, ...data.settings });
                });

                await this.fetchCustomers();
                await this.loadFromLocal();
            } catch (error) {
                console.error("Failed to fetch from server", error);
                if (error.response?.status === 401) {
                    this.needsReAuth = true;
                }
                await this.loadFromLocal();
            }
        },

        async fetchCustomers() {
            let cursor = null;
            let hasMore = true;
            
            // Clear customers once before paginated fetch
            await db.customers.clear();
            
            while (hasMore) {
                const url = cursor 
                    ? `/api/pos/sync-catalog?cursor=${cursor}` 
                    : '/api/pos/sync-catalog';
                const response = await axios.get(url);
                const page = response.data.data ?? response.data;
                
                if (page.customers && page.customers.length > 0) {
                    await db.customers.bulkAdd(page.customers);
                }
                
                cursor = page.next_cursor;
                hasMore = page.has_more === true && cursor !== null;
            }
        },

        async loadFromLocal() {
            this.services = (await db.services.toArray()).sort((a, b) => b.id - a.id);
            this.serviceTypes = await db.serviceTypes.toArray();
            this.serviceDetails = await db.serviceDetails.toArray();
            this.addons = (await db.addons.toArray()).sort((a, b) => b.id - a.id);
            this.customers = (await db.customers.toArray()).sort((a, b) => b.id - a.id);
            
            const settings = await db.settings.get(1);
            if(settings) {
                this.settings = settings;
            }
            
            // Hydrate offline cart if exists for this user
            if (window.PosConfig && window.PosConfig.user) {
                const draft = await db.cart.get(window.PosConfig.user.id);
                if (draft) {
                    this.cart = draft.items || [];
                    this.cartAddons = draft.addons || [];
                    this.payments = draft.payments || [];
                    this.discount = draft.discount || 0;
                    this.paymentNotes = draft.notes || '';
                    this.cartUuid = draft.cart_uuid || null;
                    if (draft.customer_id) {
                        this.cartCustomer = this.customers.find(c => c.id === draft.customer_id) || null;
                    }
                }
            }
        },
        
        async syncOfflineData() {
            const { runSync } = useSyncEngine();
            return await runSync(this);
        },

        increaseQty(index) {
            if(this.cart[index]) {
                this.cart[index].quantity++;
            }
        },

        decreaseQty(index) {
            if(this.cart[index] && this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        removeItem(index) {
            this.cart.splice(index, 1);
        },

        duplicateItem(index) {
            if(this.cart[index]) {
                const itemToDuplicate = JSON.parse(JSON.stringify(this.cart[index]));
                this.cart.splice(index + 1, 0, itemToDuplicate);
            }
        }
    }
});
