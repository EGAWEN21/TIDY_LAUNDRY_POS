<template>
    <div class="modal fade" id="syncQueueModal" tabindex="-1" role="dialog" aria-hidden="true" @show.bs.modal="fetchQueue">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content radius-16 bg-base">
                <div class="modal-header py-16 px-24 border border-top-0 border-start-0 border-end-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-circle-sm bg-primary-50 text-primary-600">
                            <iconify-icon icon="mdi:cloud-sync-outline" class="fs-4"></iconify-icon>
                        </div>
                        <h1 class="modal-title text-md m-0">Sync Manager Dashboard</h1>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-24">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="text-secondary-light m-0 text-sm">Manage pending offline orders and customers securely.</p>
                        <button @click="forceSync" class="btn btn-primary btn-sm d-flex align-items-center gap-2" :disabled="pos.isSyncing">
                            <iconify-icon icon="mdi:refresh" :class="{'tw-animate-spin': pos.isSyncing}"></iconify-icon>
                            {{ pos.isSyncing ? 'Syncing...' : 'Sync Now' }}
                        </button>
                    </div>

                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    
                    <div v-else-if="queueItems.length === 0" class="text-center py-5 empty-state">
                        <iconify-icon icon="mdi:cloud-check" class="display-1 text-success opacity-50 mb-3"></iconify-icon>
                        <h5 class="fw-semibold">All Caught Up!</h5>
                        <p class="text-secondary-light text-sm">There are no pending items in the offline queue.</p>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table basic-border-table mb-0 tw-text-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in queueItems" :key="item.id" class="align-middle">
                                    <td>
                                        <span :class="['badge radius-8 px-12 py-4', item.type === 'order' ? 'bg-primary-50 text-primary-600' : 'bg-info-50 text-info-600']">
                                            {{ item.type.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark">{{ getDetails(item) }}</div>
                                        <div class="text-xs text-secondary-light">Retries: {{ item.retry_count }}</div>
                                    </td>
                                    <td>
                                        <span v-if="item.status === 'pending'" class="badge bg-warning-50 text-warning-600 radius-8 px-12 py-4">Pending</span>
                                        <span v-else-if="item.status === 'error'" class="badge bg-danger-50 text-danger-600 radius-8 px-12 py-4">Error</span>
                                    </td>
                                    <td class="text-secondary-light text-xs">
                                        {{ formatTime(item.timestamp) }}
                                    </td>
                                    <td class="text-end">
                                        <button @click="deleteItem(item.id)" class="btn btn-sm btn-outline-danger radius-8 d-inline-flex align-items-center justify-content-center p-2" title="Delete Offline Data">
                                            <iconify-icon icon="mdi:trash-can-outline"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePosStore } from '../../stores/posStore';
import { db } from '../../db';
import { toast } from 'vue3-toastify';

const pos = usePosStore();
const queueItems = ref([]);
const isLoading = ref(true);

const fetchQueue = async () => {
    isLoading.value = true;
    try {
        queueItems.value = await db.syncQueue.orderBy('timestamp').reverse().toArray();
    } catch (e) {
        console.error(e);
        toast.error("Failed to load sync queue.");
    } finally {
        isLoading.value = false;
    }
};

const forceSync = async () => {
    if (!pos.isOnline) {
        toast.warning("You are currently offline. Cannot sync.");
        return;
    }
    const result = await pos.syncOfflineData();
    if (result.success) {
        toast.success("Sync completed successfully.");
    } else {
        toast.error("Sync failed for some items.");
    }
    await fetchQueue();
};

const deleteItem = async (id) => {
    if (confirm("Are you sure you want to delete this offline item? This cannot be undone.")) {
        await db.syncQueue.delete(id);
        toast.success("Item deleted from queue.");
        await fetchQueue();
    }
};

const getDetails = (item) => {
    if (item.type === 'order') {
        return `${item.data.customer_name || 'Walk-in Customer'} - ${pos.settings.currency || '$'}${item.data.total}`;
    }
    if (item.type === 'customer') {
        return `${item.data.name} (${item.data.phone})`;
    }
    return 'Unknown Data';
};

const formatTime = (ts) => {
    return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' });
};

// Expose fetchQueue to global scope so bootstrap modal events can trigger it if needed
onMounted(() => {
    // Listen for bootstrap modal show event
    const modal = document.getElementById('syncQueueModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', fetchQueue);
    }
});
</script>

<style scoped>
.icon-circle-sm {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.empty-state {
    background: #f8fafc;
    border-radius: 16px;
    border: 1px dashed #cbd5e1;
}
</style>
