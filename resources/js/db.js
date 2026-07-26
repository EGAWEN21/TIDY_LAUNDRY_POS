import Dexie from 'dexie';

export const db = new Dexie('TidyPOSDatabase');

export const getCurrentPosUserId = () => {
    const userId = globalThis?.PosConfig?.user?.id;
    return userId === undefined || userId === null ? null : String(userId);
};

export const isOwnedByCurrentPosUser = (item) => {
    const userId = getCurrentPosUserId();
    return userId !== null && String(item?.user_id ?? '') === userId;
};

export const isSyncEligible = (item, now = Date.now()) => {
    if (!['pending', 'retryable_failure'].includes(item?.status)) return false;
    return !item.next_retry_at || item.next_retry_at <= now;
};

export const classifySyncError = (error) => {
    const status = error?.response?.status;
    if (status === 401) return { category: 'authentication', status: 'auth_required' };
    if ([403, 409, 422].includes(status)) return { category: 'permanent', status: 'permanent_failure' };
    if (status === 429 || (status >= 500 && status <= 599)) {
        return { category: 'transient', status: 'retryable_failure' };
    }
    if (!status) return { category: 'transient', status: 'retryable_failure' };
    return { category: 'permanent', status: 'permanent_failure' };
};

export const getRetryDelay = (retryCount) => {
    const baseDelay = 30 * 1000;
    const maxDelay = 15 * 60 * 1000;
    return Math.min(baseDelay * (2 ** Math.max(0, retryCount - 1)), maxDelay);
};

const syncLockId = 'pos-sync';
const syncLockOwner = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
const syncLockLeaseMs = 2 * 60 * 1000;

export const acquireSyncLock = async () => {
    const now = Date.now();
    const expiresAt = now + syncLockLeaseMs;
    let acquired = false;

    await db.transaction('rw', db.syncLocks, async () => {
        const existing = await db.syncLocks.get(syncLockId);
        if (!existing || existing.expires_at <= now || existing.owner === syncLockOwner) {
            await db.syncLocks.put({ id: syncLockId, owner: syncLockOwner, expires_at: expiresAt });
            acquired = true;
        }
    });

    if (!acquired) return null;

    const renew = async () => {
        let renewed = false;
        await db.transaction('rw', db.syncLocks, async () => {
            const existing = await db.syncLocks.get(syncLockId);
            if (existing?.owner === syncLockOwner) {
                await db.syncLocks.update(syncLockId, { expires_at: Date.now() + syncLockLeaseMs });
                renewed = true;
            }
        });
        return renewed;
    };

    const release = async () => {
        await db.transaction('rw', db.syncLocks, async () => {
            const existing = await db.syncLocks.get(syncLockId);
            if (existing?.owner === syncLockOwner) {
                await db.syncLocks.delete(syncLockId);
            }
        });
    };

    release.renew = renew;
    return release;
};

export const claimLegacyQueueRecords = async () => {
    const userId = getCurrentPosUserId();
    if (userId === null) return 0;

    let claimedCount = 0;
    await db.transaction('rw', db.syncQueue, async () => {
        claimedCount = await db.syncQueue
            .filter(item => item.user_id === null || item.user_id === undefined)
            .modify(item => {
                item.user_id = userId;
                item.claimed_from_legacy = true;
                item.claimed_at = new Date().toISOString();
                item.claimed_by = userId;
                item.legacy_unassigned = false;
            });
    });

    return claimedCount;
};

db.version(1).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status', // sync_status: 'synced', 'pending'
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, items, addons, customer_id, total, tax, discount, payments, status',
    syncQueue: '++id, type, data, timestamp' // type: 'order' or 'customer'
});

db.version(2).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, items, addons, customer_id, total, tax, discount, payments, status',
    syncQueue: '++id, type, data, timestamp, status, retry_count'
}).upgrade(tx => {
    return tx.syncQueue.toCollection().modify(queueItem => {
        queueItem.status = 'pending';
        queueItem.retry_count = 0;
    });
});

db.version(3).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, items, addons, customer_id, total, tax, discount, payments, status',
    syncQueue: '++id, type, data, timestamp, status, retry_count, error_message'
}).upgrade(tx => {
    return tx.syncQueue.toCollection().modify(queueItem => {
        queueItem.error_message = '';
    });
});

db.version(4).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, user_id, items, addons, customer_id, total, tax, discount, payments, status, notes',

    syncQueue: '++id, type, uuid, user_id, data, timestamp, status, retry_count, error_message'
});

db.version(5).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, user_id, items, addons, customer_id, total, tax, discount, payments, status, notes',
    syncQueue: '++id, type, uuid, user_id, data, timestamp, status, retry_count, error_message'
}).upgrade(tx => {
    return tx.syncQueue.toCollection().modify(queueItem => {
        // Legacy records are intentionally quarantined until POS initializes.
        queueItem.uuid = queueItem.uuid || queueItem.data?.uuid || null;
        queueItem.user_id = null;
        queueItem.legacy_unassigned = true;
    });
});

db.version(6).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, user_id, items, addons, customer_id, total, tax, discount, payments, status, notes',
    syncQueue: '++id, type, uuid, user_id, data, timestamp, status, retry_count, error_message, failure_category, next_retry_at, last_attempt_at'
}).upgrade(tx => {
    return tx.syncQueue.toCollection().modify(queueItem => {
        queueItem.failure_category = queueItem.failure_category || (queueItem.status === 'error' ? 'legacy_attention' : null);
        queueItem.status = queueItem.status === 'error' ? 'permanent_failure' : (queueItem.status || 'pending');
        queueItem.next_retry_at = queueItem.next_retry_at || 0;
        queueItem.last_attempt_at = queueItem.last_attempt_at || null;
    });
});

db.version(7).stores({
    services: 'id, service_name, is_active',
    serviceTypes: 'id, service_type_name, position',
    serviceDetails: 'id, service_id, service_type_id, service_price',
    addons: 'id, addon_name, addon_price',
    customers: 'id, uuid, phone, name, email, tax_number, address, sync_status',
    settings: 'id, tax_percentage, tax_type, financial_year_id, currency',
    cart: 'id, uuid, user_id, items, addons, customer_id, total, tax, discount, payments, status, notes',
    syncQueue: '++id, type, uuid, user_id, data, timestamp, status, retry_count, error_message, failure_category, next_retry_at, last_attempt_at',
    syncLocks: 'id, owner, expires_at'
});
