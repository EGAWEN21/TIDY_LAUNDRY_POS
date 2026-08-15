import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosApp from './vue/PosApp.vue';
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';
import { registerSW } from 'virtual:pwa-register';

const app = createApp(PosApp);
const pinia = createPinia();

app.use(pinia);
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  theme: 'colored'
});
app.mount('#pos-app');

try {
    localStorage.setItem('pwa-authenticated', '1');
} catch (error) {
    // Storage can be unavailable in private browsing; the POS remains usable for this session.
}

registerSW({
    immediate: true,
    onOfflineReady() {
        window.dispatchEvent(new CustomEvent('pwa-offline-ready'));
    },
    onRegisterError(error) {
        console.error('PWA service worker registration failed:', error);
    }
});

// Global Navigation Guard & Premium Transition Bridge
document.addEventListener('click', function(event) {
    const target = event.target;
    if (!target || typeof target.closest !== 'function') return;

    const link = target.closest('a');
    if (link) {
        const href = link.getAttribute('href');
        const protocol = link.protocol || '';
        
        // Only intercept standard HTTP/HTTPS links (ignore tel:, mailto:, blob:, #, javascript:)
        if (href && (protocol === 'http:' || protocol === 'https:') && !href.startsWith('#') && !href.includes('/admin/pos')) {
            
            // 1. Offline Guard (Block ALL navigation, even new tabs)
            if (!navigator.onLine) {
                event.preventDefault();
                event.stopPropagation(); // Stop it from even reaching Vue
                if (typeof window.showOfflineChoice === 'function') {
                    window.showOfflineChoice(link.href);
                }
                return;
            }

            // At this point, they are ONLINE.
            
            // Allow default browser behavior for downloads, new tabs, and modifier keys (Ctrl/Cmd/Shift click)
            if (link.getAttribute('target') === '_blank' || link.hasAttribute('download') || event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0) {
                return; // Let the browser handle it naturally
            }

            // 2. Standalone App Premium Transition (Glassmorphism Bridge)
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                event.preventDefault();
                event.stopPropagation();
                
                const splash = document.getElementById('pwa-splash');
                if (splash) {
                    splash.style.opacity = '0'; // Reset opacity to 0 first
                    splash.style.display = 'flex';
                    // Force reflow to ensure CSS transition applies
                    void splash.offsetWidth;
                    splash.classList.remove('splash-hidden');
                    splash.style.opacity = '1';
                }
                
                // Wait 150ms for the glass overlay to fade in, then navigate
                setTimeout(() => {
                    window.location.href = href;
                }, 150);
            }
        }
    }
}, true); // Use capture phase to intercept BEFORE any child components
