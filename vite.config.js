import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        
        // Plugin Tailwind CSS v4
        tailwindcss(),

        // Plugin PWA
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public', // Output SW ke root public
            base: '/',
            scope: '/',
            build: {
                emptyOutDir: false, // Jangan hapus file lain di public (index.php, images, dll)
            },
            manifest: {
                name: 'Sumber Tani POS',
                short_name: 'POS Tani',
                description: 'Aplikasi Kasir Toko Pertanian',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'landscape',
                start_url: '/',
                icons: [
                    {
                        src: '/images/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/images/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            },
            workbox: {
                // 1. Matikan fallback index.html (Karena Laravel pakai PHP/Blade)
                navigateFallback: null, 

                // 2. Folder yang akan di-scan untuk cache
                globDirectory: 'public',
                
                // 3. Pola file yang WAJIB di-download di awal (Precache)
                // Kita hanya fokus ke folder build saja agar aman & cepat
                globPatterns: [
                    'build/**/*.{js,css,html,ico,png,svg,woff,woff2}',
                ],

                // Hapus cache lama jika ada update baru
                cleanupOutdatedCaches: true,
                
                // 4. Runtime Caching (Strategi Offline saat app berjalan)
                runtimeCaching: [
                    {
                        // Cache Halaman HTML (Blade)
                        // Strategi: Network First (Coba internet dulu, kalau mati baru buka cache)
                        urlPattern: ({ request }) => request.mode === 'navigate',
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'pages-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 7 // 7 Hari
                            },
                            cacheableResponse: { statuses: [0, 200] }
                        }
                    },
                    {
                        // Cache Aset Statis (Gambar, JS, CSS, Font)
                        // Strategi: Cache First (Ambil dari cache biar cepat)
                        urlPattern: ({ request }) => 
                            ['style', 'script', 'image', 'font'].includes(request.destination),
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'assets-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 Hari
                            }
                        }
                    },
                    {
                        // JANGAN CACHE: API Sync & QZ Tray (Printer)
                        // Strategi: Network Only (Wajib ada koneksi/localhost)
                        urlPattern: ({ url }) => 
                            url.pathname.includes('checkout') || 
                            url.hostname === 'localhost' || 
                            url.protocol === 'ws:', 
                        handler: 'NetworkOnly', 
                    }
                ]
            }
        }),
    ],
    // Pastikan build process tidak menghapus folder public secara keseluruhan
    build: {
        emptyOutDir: false,
    }
});