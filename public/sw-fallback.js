// Root-scoped navigation safety net. Asset and API requests remain managed by Workbox.

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('message', (event) => {
    if (!event.data || event.data.type !== 'CACHE_POS_SHELL') return;

    event.waitUntil((async () => {
        try {
            const response = await fetch('/admin/pos', { credentials: 'same-origin', cache: 'reload' });
            if (!response.ok || response.redirected) return;
            const posCache = await caches.open('pos-html-cache');
            await posCache.put('/admin/pos', response.clone());
            event.source?.postMessage({ type: 'POS_SHELL_CACHED' });
        } catch (error) {
            // A later online page load will retry warming the shell.
        }
    })());
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    event.respondWith((async () => {
        try {
            const response = await fetch(event.request);
            if (/^\/admin\/pos\/?$/.test(url.pathname) && response.ok && !response.redirected) {
                const posCache = await caches.open('pos-html-cache');
                await posCache.put('/admin/pos', response.clone());
            }
            return response;
        } catch (error) {
            if (/^\/admin\/pos\/?$/.test(url.pathname)) {
                const posCache = await caches.open('pos-html-cache');
                const cachedPos = await posCache.match('/admin/pos', { ignoreSearch: true });
                if (cachedPos) return cachedPos;
            }

            return (await caches.match('/offline.html', { ignoreSearch: true })) || Response.error();
        }
    })());
});
