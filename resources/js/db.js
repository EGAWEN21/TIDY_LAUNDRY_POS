import Dexie from 'dexie';

export const db = new Dexie('TidyPOSDatabase');

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
