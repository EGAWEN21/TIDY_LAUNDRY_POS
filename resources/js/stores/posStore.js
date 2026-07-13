import { defineStore } from 'pinia';
import { db } from '../db';
import axios from 'axios';

export const usePosStore = defineStore('pos', {
    state: () => ({
        services: [],
        serviceTypes: [],
        serviceDetails: [],
        addons: [],
        customers: [],
        settings: { tax_percentage: 0, tax_type: 1, currency: '$' },
        cart: [],
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
            
            // Set Axios token
            axios.defaults.headers.common['Authorization'] = `Bearer ${window.PosConfig.apiToken}`;
            axios.defaults.headers.common['Accept'] = 'application/json';

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
                    const queueCount = await db.syncQueue.count();
                    if (this.cart.length > 0 || queueCount > 0) {
                        return; // Prevent shifting the master catalog while active offline work is present
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
        },

        updateOnlineStatus(e) {
            this.isOnline = navigator.onLine;
            if(this.isOnline) {
                this.syncOfflineData();
                // Also trigger an immediate check for updates when coming online
                axios.get('/api/pos/check-update').then(async res => {
                    if(res.data.timestamp > this.lastSyncTimestamp) {
                        const queueCount = await db.syncQueue.count();
                        if (this.cart.length === 0 && queueCount === 0) {
                            this.fetchFromServer();
                        }
                    }
                }).catch(err => {});
            }
        },

        async fetchFromServer() {
            try {
                const response = await axios.get('/api/pos/init');
                const data = response.data;
                this.lastSyncTimestamp = data.timestamp || 0;
                
                // Clear old data and save new
                await db.transaction('rw', db.services, db.serviceTypes, db.serviceDetails, db.addons, db.customers, db.settings, async () => {
                    await db.services.clear();
                    await db.serviceTypes.clear();
                    await db.serviceDetails.clear();
                    await db.addons.clear();
                    await db.customers.clear();
                    await db.settings.clear();

                    await db.services.bulkAdd(data.services);
                    await db.serviceTypes.bulkAdd(data.service_types);
                    await db.serviceDetails.bulkAdd(data.service_details);
                    await db.addons.bulkAdd(data.addons);
                    await db.customers.bulkAdd(data.customers);
                    await db.settings.put({ id: 1, ...data.settings });
                });

                await this.loadFromLocal();
            } catch (error) {
                console.error("Failed to fetch from server", error);
                await this.loadFromLocal();
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
        },
        
        async syncOfflineData() {
            if(this.isSyncing || !this.isOnline || this.needsReAuth) return { success: false, reason: 'offline_or_syncing' };
            this.isSyncing = true;
            
            let syncResult = {
                success: true,
                syncedOrders: {},
                requiresApproval: {}
            };
            
            try {
                const chunkArray = (array, size) => {
                    const result = [];
                    for (let i = 0; i < array.length; i += size) result.push(array.slice(i, i + size));
                    return result;
                };

                // Sync Orders First (Unified Graph Sync will handle nested customers)
                const allOrders = await db.syncQueue.where('type').equals('order').toArray();
                const pendingOrders = allOrders.filter(o => o.status !== 'error');
                const orderChunks = chunkArray(pendingOrders, 20);

                for (const chunk of orderChunks) {
                    try {
                        const payload = chunk.map(p => p.data);
                        const response = await axios.post('/api/pos/sync-orders', { orders: payload });
                        
                        if(response.data.synced_orders) {
                            for(let uuid in response.data.synced_orders) {
                                syncResult.syncedOrders[uuid] = response.data.synced_orders[uuid];
                                if (response.data.requires_approval) {
                                    syncResult.requiresApproval[uuid] = response.data.requires_approval[uuid];
                                }
                                const item = chunk.find(p => p.data.uuid === uuid);
                                if(item) await db.syncQueue.delete(item.id);
                            }
                        }

                        // Granular Error Reporting for failed items in batch
                        if (response.data.failed) {
                            for(let uuid in response.data.failed) {
                                const item = chunk.find(p => p.data.uuid === uuid);
                                if (item) {
                                    await db.syncQueue.update(item.id, { 
                                        status: 'error', 
                                        error_message: response.data.failed[uuid] 
                                    });
                                }
                            }
                            syncResult.success = false;
                        }
                    } catch (error) {
                        if (error.response && error.response.status === 401) break;
                        for (const item of chunk) {
                            const newCount = (item.retry_count || 0) + 1;
                            const newStatus = newCount > 3 ? 'error' : 'pending';
                            const errMsg = error.response ? `Server Error: ${error.response.status}` : 'Poor or unstable network connection.';
                            await db.syncQueue.update(item.id, { retry_count: newCount, status: newStatus, error_message: errMsg });
                        }
                        syncResult.success = false;
                    }
                }

                // Sync Standalone Customers (if any)
                const allCustomers = await db.syncQueue.where('type').equals('customer').toArray();
                const pendingCustomers = allCustomers.filter(c => c.status !== 'error');
                const customerChunks = chunkArray(pendingCustomers, 20);

                for (const chunk of customerChunks) {
                    try {
                        const payload = chunk.map(p => p.data);
                        const response = await axios.post('/api/pos/sync-customers', { customers: payload });
                        
                        if(response.data.synced_customers) {
                            for(let uuid in response.data.synced_customers) {
                                const item = chunk.find(p => p.data.uuid === uuid);
                                if(item) {
                                    await db.syncQueue.delete(item.id);
                                    // Update local customer status
                                    const cust = await db.customers.where('uuid').equals(uuid).first();
                                    if (cust) await db.customers.update(cust.id, { sync_status: 'synced' });
                                }
                            }
                        }

                        if (response.data.failed) {
                            for(let uuid in response.data.failed) {
                                const item = chunk.find(p => p.data.uuid === uuid);
                                if (item) {
                                    await db.syncQueue.update(item.id, { 
                                        status: 'error', 
                                        error_message: response.data.failed[uuid] 
                                    });
                                }
                            }
                        }
                    } catch (error) {
                        if (error.response && error.response.status === 401) break;
                        for (const item of chunk) {
                            const newCount = (item.retry_count || 0) + 1;
                            const newStatus = newCount > 3 ? 'error' : 'pending';
                            const errMsg = error.response ? `Server Error: ${error.response.status}` : 'Poor or unstable network connection.';
                            await db.syncQueue.update(item.id, { retry_count: newCount, status: newStatus, error_message: errMsg });
                        }
                        syncResult.success = false;
                    }
                }

            } catch (error) {
                console.error("Global Sync Error:", error);
                syncResult.success = false;
            } finally {
                this.isSyncing = false;
                await this.loadFromLocal();
            }
            return syncResult;
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
