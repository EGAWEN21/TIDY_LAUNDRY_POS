import { db, acquireSyncLock, classifySyncError, getCurrentPosUserId, getRetryDelay, isOwnedByCurrentPosUser, isSyncEligible } from '../db';
import axios from 'axios';

const unwrapApiData = (response) => response?.data?.data ?? response?.data ?? {};

export function useSyncEngine() {
    const chunkArray = (array, size) => {
        const result = [];
        for (let i = 0; i < array.length; i += size) result.push(array.slice(i, i + size));
        return result;
    };

    const markPermanentItemFailure = async (item, message) => {
        await db.syncQueue.update(item.id, {
            status: 'permanent_failure',
            failure_category: 'server_validation',
            error_message: message,
            last_attempt_at: new Date().toISOString(),
            next_retry_at: 0
        });
    };

    const updateChunkFailure = async (chunk, error, fallbackMessage) => {
        const classification = classifySyncError(error);
        const now = Date.now();
        const serverMessage = error.response?.data?.message;
        const errorMessage = serverMessage || fallbackMessage;

        for (const item of chunk) {
            const retryCount = (item.retry_count || 0) + 1;
            const exhausted = classification.status === 'retryable_failure' && retryCount > 5;
            const authPaused = classification.status === 'auth_required';
            const status = exhausted
                ? 'permanent_failure'
                : authPaused
                    ? 'pending'
                    : classification.status;
            await db.syncQueue.update(item.id, {
                status,
                retry_count: retryCount,
                failure_category: exhausted ? 'retry_limit' : classification.category,
                error_message: exhausted ? 'Automatic retry limit reached. Manual review is required.' : errorMessage,
                last_attempt_at: new Date(now).toISOString(),
                next_retry_at: status === 'retryable_failure' ? now + getRetryDelay(retryCount) : 0
            });
        }

        return classification.status === 'auth_required' ? 'reauth_required' : classification.status;
    };

    const runSync = async (store) => {
        if(store.isSyncing || !store.isOnline || store.needsReAuth) return { success: false, reason: 'offline_or_syncing' };
        
        const releaseSyncLock = await acquireSyncLock();
        if (!releaseSyncLock) return { success: false, reason: 'sync_in_progress' };
        
        const lockRenewalTimer = setInterval(() => {
            releaseSyncLock.renew().catch(error => console.warn('Failed to renew sync lock:', error));
        }, 30 * 1000);
        
        store.isSyncing = true;
        
        let syncResult = {
            success: true,
            outcome: 'completed',
            reason: null,
            attempted: 0,
            syncedOrders: {},
            requiresApproval: {},
            failed: {}
        };
        
        try {
            const userId = getCurrentPosUserId();
            if (userId === null) {
                return {
                    success: false,
                    outcome: 'authentication_required',
                    reason: 'missing_user_identity',
                    attempted: 0,
                    syncedOrders: {},
                    requiresApproval: {},
                    failed: {}
                };
            }

            const allOrders = (await db.syncQueue.where('type').equals('order').toArray())
                .filter(item => isOwnedByCurrentPosUser(item));
            const pendingOrders = allOrders.filter(o => isSyncEligible(o));
            const orderChunks = chunkArray(pendingOrders, 5);
            
            const allCustomers = (await db.syncQueue.where('type').equals('customer').toArray())
                .filter(item => isOwnedByCurrentPosUser(item));
            const pendingCustomers = allCustomers.filter(c => isSyncEligible(c));
            const customerChunks = chunkArray(pendingCustomers, 5);

            syncResult.attempted = pendingOrders.length + pendingCustomers.length;
            if (syncResult.attempted === 0) {
                syncResult.success = false;
                syncResult.outcome = allOrders.length || allCustomers.length
                    ? 'attention_required'
                    : 'nothing_to_sync';
                syncResult.reason = allOrders.length || allCustomers.length
                    ? 'no_pending_items'
                    : 'queue_empty';
                return syncResult;
            }

            for (const chunk of orderChunks) {
                try {
                    const payload = chunk.map(p => p.data);
                    const response = await axios.post('/api/pos/sync-orders', { orders: payload });
                    const result = unwrapApiData(response);
                    
                    if(result.synced_orders) {
                        for(const uuid in result.synced_orders) {
                            syncResult.syncedOrders[uuid] = result.synced_orders[uuid];
                            if (result.requires_approval) {
                                syncResult.requiresApproval[uuid] = result.requires_approval[uuid];
                            }
                            const item = chunk.find(p => p.data.uuid === uuid);
                            if (item) {
                                const requiresApproval = result.requires_approval?.[uuid];
                                if (requiresApproval) {
                                    await db.syncQueue.update(item.id, {
                                        status: 'pending_approval',
                                        failure_category: 'approval_required',
                                        error_message: '',
                                        last_attempt_at: new Date().toISOString(),
                                        next_retry_at: 0
                                    });
                                } else {
                                    await db.syncQueue.delete(item.id);
                                }
                            }
                        }
                    }

                    if (result.failed && Object.keys(result.failed).length > 0) {
                        for(const uuid in result.failed) {
                            const item = chunk.find(p => p.data.uuid === uuid);
                            if (item) {
                                await markPermanentItemFailure(item, result.failed[uuid]);
                            }
                        }
                        syncResult.success = false;
                        syncResult.outcome = 'partial_failure';
                        Object.assign(syncResult.failed, result.failed);
                    }
                } catch (error) {
                    const failureStatus = await updateChunkFailure(
                        chunk,
                        error,
                        'The order batch could not be synchronized.'
                    );
                    syncResult.success = false;
                    syncResult.outcome = 'partial_failure';
                    if (failureStatus === 'reauth_required') {
                        store.needsReAuth = true;
                        syncResult.reason = 'reauth_required';
                        break;
                    }
                }
            }

            if (store.needsReAuth) {
                return syncResult;
            }

            for (const chunk of customerChunks) {
                try {
                    const payload = chunk.map(p => p.data);
                    const response = await axios.post('/api/pos/sync-customers', { customers: payload });
                    const result = unwrapApiData(response);
                    
                    if(result.synced_customers) {
                        for(const uuid in result.synced_customers) {
                            const item = chunk.find(p => p.data.uuid === uuid);
                            if(item) {
                                await db.syncQueue.delete(item.id);
                                const cust = await db.customers.where('uuid').equals(uuid).first();
                                if (cust) await db.customers.update(cust.id, { sync_status: 'synced' });
                            }
                        }
                    }

                    if (result.failed && Object.keys(result.failed).length > 0) {
                        syncResult.success = false;
                        syncResult.outcome = 'partial_failure';
                        Object.assign(syncResult.failed, result.failed);
                        for(const uuid in result.failed) {
                            const item = chunk.find(p => p.data.uuid === uuid);
                            if (item) {
                                await markPermanentItemFailure(item, result.failed[uuid]);
                            }
                        }
                    }
                } catch (error) {
                    const failureStatus = await updateChunkFailure(
                        chunk,
                        error,
                        'The customer batch could not be synchronized.'
                    );
                    syncResult.success = false;
                    syncResult.outcome = 'partial_failure';
                    if (failureStatus === 'reauth_required') {
                        store.needsReAuth = true;
                        syncResult.reason = 'reauth_required';
                        break;
                    }
                }
            }

        } catch (error) {
            console.error("Global Sync Error:", error);
            syncResult.success = false;
        } finally {
            clearInterval(lockRenewalTimer);
            store.isSyncing = false;
            await releaseSyncLock();
            await store.loadFromLocal();
        }
        return syncResult;
    };

    return {
        runSync
    };
}
