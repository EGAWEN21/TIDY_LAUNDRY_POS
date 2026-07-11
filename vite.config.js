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
            outDir: 'public/build',
            buildBase: '/build/',
            scope: '/admin/pos/',
            registerType: 'autoUpdate',
            injectRegister: 'script',
            workbox: {
                navigateFallback: '/admin/pos',
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2,ttf,eot}'],
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
                            backgroundSync: {
                                name: 'api-syncQueue',
                                options: {
                                    maxRetentionTime: 24 * 60 // 24 hours
                                }
                            }
                        }
                    }
                ]
            },
            manifest: {
                name: 'TidyPOS Offline',
                short_name: 'TidyPOS',
                description: 'Offline capable Point of Sale',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/admin/pos',
                icons: [
                    {
                        src: '/assets/img/logo-ct.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/assets/img/logo-ct.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            }
        })
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
});
