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
            scope: '/admin/pos-app/',
            registerType: 'autoUpdate',
            injectRegister: 'script',
            workbox: {
                navigateFallback: '/admin/pos-app',
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2,ttf,eot}'],
            },
            manifest: {
                name: 'TidyPOS Offline',
                short_name: 'TidyPOS',
                description: 'Offline capable Point of Sale',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/admin/pos-app',
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
