import { defineConfig } from 'vite';

import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/pos-app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            outDir: 'public',
            buildBase: '/',
            scope: '/',
            registerType: 'autoUpdate',
            injectRegister: false,
            manifest: false,
            workbox: {
                navigateFallback: null,
                importScripts: ['/sw-fallback.js'],
                // Precache only the offline POS shell. Caching the entire admin theme adds
                // hundreds of unrelated dashboard, editor, chat, and report assets.
                globPatterns: [
                    'build/assets/**/*.{js,css}',
                    'offline.html',
                    'sw-fallback.js',
                    'favicon.ico',
                    'assets/css/{style,custom,remixicon}.css',
                    'assets/css/lib/bootstrap.min.css',
                    'assets/plugins/toastr.min.{css,js}',
                    'assets/js/app.js',
                    'assets/js/lib/{jquery-3.7.1.min,bootstrap.bundle.min,iconify-icon.min,jquery-ui.min}.js',
                    'assets/img/service-icons/*.{png,jpg,jpeg,webp,svg}',
                    'assets/images/{logo-192,logo-512,apple-touch-icon,favicon,arrow-down,times}.png',
                    'assets/images/payment/upload-image.png',
                    'assets/images/user-grid/user-grid-img13.png',
                    'assets/fonts/remixicon.{woff,woff2,ttf,eot,svg}'
                ],
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'google-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 365 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'gstatic-fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 365 days
                            },
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    },
                    {
                        // Ensure API sync routes are NetworkOnly to prevent Service Worker proxy timeouts on chunked data
                        urlPattern: /\/api\/pos\/.*/i,
                        handler: 'NetworkOnly',
                        options: {
                            // Workbox backgroundSync removed to prevent conflicts with custom Dexie queue
                        }
                    }
                ],
                maximumFileSizeToCacheInBytes: 5000000
            }
        })
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
