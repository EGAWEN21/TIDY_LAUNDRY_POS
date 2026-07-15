<template>
  <div v-if="showPrompt" class="tw-fixed tw-bottom-6 tw-left-0 tw-w-full tw-p-4 tw-z-50 tw-animate-slide-up">
    <div class="tw-bg-white tw-rounded-xl tw-shadow-2xl tw-p-4 tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-justify-between tw-border tw-border-slate-100 tw-gap-4">
      <div class="tw-flex tw-items-center tw-gap-4">
        <div class="tw-bg-primary-50 tw-p-3 tw-rounded-lg">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="tw-w-8 tw-h-8 tw-text-primary-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
          </svg>
        </div>
        <div>
          <h3 class="tw-text-base tw-font-bold tw-text-slate-900">Install TidyPOS</h3>
          <p class="tw-text-sm tw-text-slate-500 tw-mt-0.5" v-if="!isIOS">Install our app for the fastest offline checkout experience.</p>
          <p class="tw-text-sm tw-text-slate-500 tw-mt-0.5" v-else>Tap <span class="tw-font-bold">Share</span>, then <span class="tw-font-bold">Add to Home Screen</span> for the best offline experience.</p>
        </div>
      </div>
      
      <div class="tw-flex tw-items-center tw-gap-3 tw-w-full sm:tw-w-auto">
        <button @click="dismissPrompt" class="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-slate-600 tw-bg-slate-100 tw-rounded-lg hover:tw-bg-slate-200 tw-transition-colors tw-w-full sm:tw-w-auto">
          Not Now
        </button>
        <button v-if="!isIOS" @click="installApp" class="tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-white tw-bg-primary-600 tw-rounded-lg hover:tw-bg-primary-700 tw-transition-colors tw-w-full sm:tw-w-auto tw-whitespace-nowrap">
          Install App
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const showPrompt = ref(false);
const deferredPrompt = ref(null);
const isIOS = ref(false);

onMounted(() => {
  // Check if app is already installed
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    return;
  }

  // Check cooling-off period
  const cooldownStr = localStorage.getItem('pwa_install_cooldown');
  if (cooldownStr) {
    const cooldownTime = parseInt(cooldownStr);
    if (Date.now() < cooldownTime) {
      return; // Still in cooldown
    }
  }

  // Detect iOS Safari
  const userAgent = window.navigator.userAgent.toLowerCase();
  isIOS.value = /iphone|ipad|ipod/.test(userAgent);

  // Standard PWA Install logic for Chrome/Edge
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later.
    deferredPrompt.value = e;
    // Update UI notify the user they can install the PWA
    showPrompt.value = true;
    startAutoDismiss();
  });

  // If iOS, show the prompt manually (they don't support beforeinstallprompt)
  if (isIOS.value) {
    showPrompt.value = true;
    startAutoDismiss();
  }
});

let dismissTimeout;
const startAutoDismiss = () => {
    dismissTimeout = setTimeout(() => {
        if(showPrompt.value) {
            autoDismiss();
        }
    }, 10000); // 10 seconds
};

const autoDismiss = () => {
    showPrompt.value = false;
    // 24 hours cooldown for auto-dismiss
    localStorage.setItem('pwa_install_cooldown', Date.now() + (24 * 60 * 60 * 1000));
};

const dismissPrompt = () => {
  showPrompt.value = false;
  clearTimeout(dismissTimeout);
  // 7 days cooldown for manual dismiss
  localStorage.setItem('pwa_install_cooldown', Date.now() + (7 * 24 * 60 * 60 * 1000));
};

const installApp = async () => {
  if (!deferredPrompt.value) return;
  clearTimeout(dismissTimeout);
  
  // Show the install prompt
  deferredPrompt.value.prompt();
  
  // Wait for the user to respond to the prompt
  const { outcome } = await deferredPrompt.value.userChoice;
  
  if (outcome === 'accepted') {
    showPrompt.value = false;
  }
  
  // We've used the prompt, and can't use it again, throw it away
  deferredPrompt.value = null;
};
</script>

<style scoped>
@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.tw-animate-slide-up {
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
