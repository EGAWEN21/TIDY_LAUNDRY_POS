// Root-scoped navigation safety net. Asset and API requests remain managed by Workbox.

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

let isFetchingPos = false;
let lastPosFetchTime = 0;

self.addEventListener('message', (event) => {
    if (!event.data || !event.data.type) return;

    event.waitUntil((async () => {
        let ready = false;
        const posCache = await caches.open('pos-html-cache');

        try {
            ready = Boolean(await posCache.match('/admin/pos', { ignoreSearch: true }));
            
            // Reply immediately so the UI doesn't timeout
            const message = { type: 'POS_SHELL_STATUS', ready };
            if (event.ports[0]) event.ports[0].postMessage(message);
            else event.source?.postMessage(message);

            if (event.data.type === 'CACHE_POS_SHELL') {
                // 30-second debounce to prevent spamming the server
                if (isFetchingPos || (Date.now() - lastPosFetchTime < 30000)) return;
                
                isFetchingPos = true;
                try {
                    const response = await fetch('/admin/pos', { credentials: 'same-origin', cache: 'reload' });
                    if (response.ok && !response.redirected) {
                        await posCache.put('/admin/pos', response.clone());
                        lastPosFetchTime = Date.now();
                        
                        // Broadcast success to all clients so they can update the UI instantly
                        if (!ready) {
                            const clients = await self.clients.matchAll();
                            for (const client of clients) {
                                client.postMessage({ type: 'POS_SHELL_STATUS', ready: true });
                            }
                        }
                    }
                } finally {
                    isFetchingPos = false;
                }
            }
        } catch (error) {
            // Background fetch failed, but we already replied to the UI.
        }

        // We already replied, so we do not send another message here.
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
