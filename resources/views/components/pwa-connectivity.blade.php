<style>
    
    #offline-choice[hidden] { display: none !important; }
    #offline-choice {
        position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center;
        justify-content: center; padding: 1.25rem; background: rgba(15, 23, 42, .62);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
    }
    #offline-choice .offline-card {
        width: min(100%, 440px); padding: 2rem; border-radius: 20px; text-align: center;
        background: #fff; color: #1e293b; box-shadow: 0 24px 70px rgba(15, 23, 42, .3);
    }
    #offline-choice img { width: 72px; height: 72px; object-fit: contain; margin-bottom: 1rem; }
    #offline-choice h2 { margin: 0 0 .65rem; font-size: 1.5rem; }
    #offline-choice p { margin: 0 0 1.5rem; color: #64748b; line-height: 1.6; }
    #offline-choice .offline-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; }
    #offline-choice button, #offline-choice a {
        display: flex; align-items: center; justify-content: center; padding: .85rem 1rem;
        border-radius: 10px; border: 0; font: inherit; font-weight: 700;
        text-decoration: none; cursor: pointer;
    }
    @media (max-width: 420px) {
        #offline-choice .offline-actions { grid-template-columns: 1fr; }
    }
    #offline-reconnect { color: #fff; background: #2563eb; }
    #offline-pos-link { color: #1d4ed8; background: #eff6ff; }
    #offline-choice .offline-status { min-height: 1.25rem; margin: .8rem 0 0; font-size: .875rem; }
    [data-theme="dark"] #offline-choice .offline-card { background: #111827; color: #f8fafc; }
    [data-theme="dark"] #offline-choice p { color: #94a3b8; }
</style>

<div id="offline-choice" role="dialog" aria-modal="true" aria-labelledby="offline-choice-title" hidden>
    <div class="offline-card">
        <img src="{{ asset('assets/images/logo-192.png') }}" alt="">
        <h2 id="offline-choice-title">You're offline</h2>
        <p>This screen needs an internet connection. Reconnect to continue here, or keep taking orders in Offline POS.</p>
        <div class="offline-actions">
            <button id="offline-reconnect" type="button">Reconnect</button>
            <a id="offline-pos-link" href="{{ url('/admin/pos') }}" hidden>Continue in Offline POS</a>
        </div>
        <p id="offline-status" class="offline-status" aria-live="polite"></p>
    </div>
</div>

<script>
    (function () {
        var authenticated = @json(auth()->check());
        var dialog = document.getElementById('offline-choice');
        var status = document.getElementById('offline-status');
        var reconnect = document.getElementById('offline-reconnect');
        var posLink = document.getElementById('offline-pos-link');
        var offlinePosReady = false;

        try {
            offlinePosReady = localStorage.getItem('offline-pos-ready') === '1';
            if (authenticated) localStorage.setItem('pwa-authenticated', '1');
        } catch (e) {}
        if (posLink) posLink.hidden = !offlinePosReady;

        function showOfflineChoice(destination) {
            if (destination) {
                try { sessionStorage.setItem('offline-intended-url', destination); } catch (e) {}
            }
            if (dialog) dialog.hidden = false;
        }

        async function serverIsReachable() {
            var controller = new AbortController();
            var timeout = setTimeout(function () { controller.abort(); }, 6000);
            try {
                var response = await fetch('/connectivity-check?t=' + Date.now(), {
                    method: 'GET', cache: 'no-store', credentials: 'same-origin', signal: controller.signal
                });
                return response.status === 204;
            } catch (e) {
                return false;
            } finally {
                clearTimeout(timeout);
            }
        }

        function markOfflinePosReady() {
            try { localStorage.setItem('offline-pos-ready', '1'); } catch (e) {}
            offlinePosReady = true;
            if (posLink) posLink.hidden = false;
        }

        function warmOfflinePos(registration) {
            if (!authenticated || !navigator.onLine) return;
            var worker = navigator.serviceWorker.controller || registration.active;
            if (worker) worker.postMessage({ type: 'CACHE_POS_SHELL' });
        }

        if ('serviceWorker' in navigator) {
            var refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', function () {
                if (!refreshing) { refreshing = true; window.location.reload(); }
            });
            navigator.serviceWorker.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'POS_SHELL_CACHED') markOfflinePosReady();
            });
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then(function (registration) {
                        registration.update().catch(function () {});
                        return navigator.serviceWorker.ready;
                    })
                    .then(warmOfflinePos)
                    .catch(function (error) { console.error('Service worker registration failed:', error); });
            });
        }

        document.addEventListener('click', function (event) {
            var logoutLink = event.target.closest && event.target.closest('[data-logout-warning]');
            if (!logoutLink) return;
            var confirmed = window.confirm(
                'Log out of TidyPOS?\n\nOffline POS data and queued orders will remain on this device so they are not lost. Use the maintenance tools later if you intentionally want to remove local data.'
            );
            if (!confirmed) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);

        document.addEventListener('click', function (event) {
            var link = event.target.closest && event.target.closest('a[href]');
            if (!link || navigator.onLine || link.id === 'offline-pos-link' || link.hash || link.target === '_blank') return;
            var url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault(); event.stopImmediatePropagation(); showOfflineChoice(url.href);
        }, true);
        document.addEventListener('submit', function (event) {
            if (navigator.onLine) return;
            event.preventDefault(); event.stopImmediatePropagation(); showOfflineChoice(window.location.href);
        }, true);

        reconnect && reconnect.addEventListener('click', async function () {
            reconnect.disabled = true;
            status.textContent = 'Checking connection…';
            if (await serverIsReachable()) {
                status.textContent = 'Connected. Returning to your screen…';
                var destination = window.location.href;
                try {
                    destination = sessionStorage.getItem('offline-intended-url') || destination;
                    sessionStorage.removeItem('offline-intended-url');
                } catch (e) {}
                window.location.assign(destination);
                return;
            }
            status.textContent = 'Still offline. Check your connection and try again.';
            reconnect.disabled = false;
        });

        document.addEventListener('livewire:init', function () {
            if (!window.Livewire || typeof Livewire.hook !== 'function') return;
            Livewire.hook('request', function (options) {
                options.fail(function (failure) {
                    if (failure.status === 0 || !navigator.onLine) {
                        if (typeof failure.preventDefault === 'function') failure.preventDefault();
                        showOfflineChoice(window.location.href);
                    }
                });
            });
        });

        window.showOfflineChoice = showOfflineChoice;
    })();
</script>
