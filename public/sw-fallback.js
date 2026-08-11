// TidyPOS Custom Service Worker Fallback Handler
// This script runs BEFORE Workbox evaluates its routing rules.

self.addEventListener('fetch', (event) => {
    // We only want to handle page navigations (HTML requests).
    // CSS, JS, API calls, and images are handled by Workbox.
    if (event.request.mode !== 'navigate') {
        return;
    }

    // Do NOT handle the offline POS route here.
    // We want Workbox's NetworkFirst strategy (defined in vite.config.js) 
    // to manage the /admin/pos cache natively.
    const url = new URL(event.request.url);
    if (/^\/admin\/pos/.test(url.pathname)) {
        return;
    }

    // For all other page navigations (like the Login page or Dashboard),
    // we attempt to fetch them directly from the network.
    // If the network completely fails (due to being offline),
    // we intercept the TypeError and serve the offline.html page from the precache.
    event.respondWith(
        fetch(event.request).catch((error) => {
            console.warn('Network request failed, serving offline fallback.', error);
            return caches.match('/offline.html', { ignoreSearch: true });
        })
    );
});
