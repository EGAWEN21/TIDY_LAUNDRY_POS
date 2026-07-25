<template>
<div v-if="pos.needsReAuth" class="reauth-overlay">
        <div class="reauth-modal">
            <div class="reauth-header">
                <div class="icon-circle">
                    <iconify-icon icon="mdi:lock-reset" class="lock-icon"></iconify-icon>
                </div>
                <h2>Session Expired</h2>
                <p>Please verify your identity to securely resume synchronization.</p>
            </div>
            
            <form @submit.prevent="handleReAuth" class="reauth-body">
                <div class="user-chip">
                    <div class="avatar">{{ userInitials }}</div>
                    <div class="user-info">
                        <span class="user-name">{{ userName }}</span>
                        <span class="user-email">{{ userEmail }}</span>
                    </div>
                </div>

                <div v-if="errorMessage" class="auth-error" role="alert">{{ errorMessage }}</div>

                <div class="input-group">
                    <label>Enter Password</label>
                    <input 
                        type="password" 
                        v-model="password" 
                        placeholder="••••••••" 
                        required 
                        autofocus
                    />
                </div>

                <button type="submit" class="submit-btn" :disabled="isLoading">
                    <svg v-if="isLoading" class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>{{ isLoading ? 'Authenticating...' : 'Unlock & Resume' }}</span>
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePosStore } from '../../stores/posStore';
import { toast } from 'vue3-toastify';
import axios from 'axios';

const pos = usePosStore();
const password = ref('');
const isLoading = ref(false);
const errorMessage = ref('');

const userName = computed(() => window.PosConfig?.user?.name || 'Cashier');
const userEmail = computed(() => window.PosConfig?.user?.email || '');
const userInitials = computed(() => {
    return userName.value.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});

const handleReAuth = async () => {
    if (!password.value) return;
    
    isLoading.value = true;
    errorMessage.value = '';
    try {
        const response = await axios.post('/api/login', {
            email: userEmail.value,
            password: password.value
        });

        const token = response.data.token;
        if (!token) throw new Error('The server did not return an authentication token.');

        window.PosConfig.apiToken = token;
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        password.value = '';

        // Release the sync gate now that a fresh token is available.
        // If the retry receives another 401, the store will set it again.
        pos.needsReAuth = false;

        if (!pos.isOnline) {
            errorMessage.value = 'You are offline. Reconnect before resuming synchronization.';
            return;
        }

        const syncResult = await pos.syncOfflineData();
        if (!syncResult.success) {
            errorMessage.value = syncResult.reason === 'reauth_required'
                ? 'The session is still expired. Please verify your password and try again.'
                : 'Authentication succeeded, but synchronization did not complete. Your order remains safely queued.';
            return;
        }
        toast.success('Authentication successful. Synchronization resumed.');
    } catch (error) {
        const status = error.response?.status;
        const serverMessage = error.response?.data?.message;
        errorMessage.value = status === 401
            ? 'Invalid email or password.'
            : status === 403
                ? (serverMessage || 'Your account is not authorized to use the POS.')
                : status === 419
                    ? 'Your browser session expired. Refresh the POS and try again.'
                    : status === 429
                        ? 'Too many login attempts. Please wait before trying again.'
                        : !error.response
                            ? 'Unable to contact the server. Check your connection and try again.'
                            : (serverMessage || 'Authentication failed. Please try again.');
        password.value = '';
    } finally {
        isLoading.value = false;
    }
};
</script>

<style scoped>
.reauth-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease-out;
}

.reauth-modal {
    background: #ffffff;
    border-radius: 24px;
    width: 100%;
    max-width: 420px;
    padding: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: translateY(0);
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.reauth-header {
    text-align: center;
    margin-bottom: 28px;
}

.icon-circle {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);
}

.lock-icon {
    font-size: 32px;
    color: #3b82f6;
}

.reauth-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    font-family: 'Inter', sans-serif;
}

.reauth-header p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.avatar {
    width: 40px;
    height: 40px;
    background: #3b82f6;
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
}

.user-email {
    font-size: 12px;
    color: #64748b;
}

.auth-error {
    margin-bottom: 16px;
    padding: 10px 12px;
    border: 1px solid #fecaca;
    border-radius: 10px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 13px;
    line-height: 1.4;
}

.input-group {
    margin-bottom: 24px;
}

.input-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.input-group input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 15px;
    color: #0f172a;
    transition: all 0.2s;
    background: #ffffff;
}

.input-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.submit-btn {
    width: 100%;
    padding: 14px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.submit-btn:hover:not(:disabled) {
    background: #2563eb;
}

.submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.spinner {
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

.opacity-25 { opacity: 0.25; }
.opacity-75 { opacity: 0.75; }

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
