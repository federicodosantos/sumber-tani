import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import AutoNumeric from 'autonumeric';

// Import Handler
import cashierHandler from './cashier'; // Ini otomatis bawa db.js di dalamnya
import financeHandler from './finance'; 

import { db } from './db';

// --- BAGIAN BARU: REGISTER PWA ---
// Ini wajib agar browser menginstall Service Worker untuk offline capabilities
import { registerSW } from 'virtual:pwa-register';

if ('serviceWorker' in navigator) {
    registerSW({
        immediate: true,
        onNeedRefresh() {
            // Opsional: Tampilkan notifikasi "Versi baru tersedia, refresh?"
            console.log("Konten baru tersedia, silakan refresh.");
        },
        onOfflineReady() {
            console.log("Aplikasi siap digunakan secara offline.");
        },
    });
}
// ----------------------------------

Alpine.plugin(persist);

// Daftarkan Data Alpine
Alpine.data('cashierHandler', cashierHandler);
Alpine.data('financeHandler', financeHandler); 

window.AutoNumeric = AutoNumeric;
window.Alpine = Alpine;
window.db = db;

Alpine.start();