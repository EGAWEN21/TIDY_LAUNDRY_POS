// Root-scoped navigation safety net. Asset and API requests remain managed by Workbox.

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('message', (event) => {
    if (!event.data || !['CACHE_POS_SHELL', 'CHECK_POS_SHELL'].includes(event.data.type)) return;

    event.waitUntil((async () => {
        let ready = false;
        const posCache = await caches.open('pos-html-cache');

        try {
            ready = Boolean(await posCache.match('/admin/pos', { ignoreSearch: true }));

            if (event.data.type === 'CACHE_POS_SHELL') {
                const response = await fetch('/admin/pos', { credentials: 'same-origin', cache: 'reload' });
                if (response.ok && !response.redirected) {
                    await posCache.put('/admin/pos', response.clone());
                    ready = true;
                }
            }
        } catch (error) {
            ready = Boolean(await posCache.match('/admin/pos', { ignoreSearch: true }));
        }

        const message = { type: 'POS_SHELL_STATUS', ready };
        if (event.ports[0]) {
            event.ports[0].postMessage(message);
        } else {
            event.source?.postMessage(message);
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
