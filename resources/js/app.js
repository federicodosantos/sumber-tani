import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import AutoNumeric from 'autonumeric';

import cashierHandler from './cashier';
import financeHandler from './finance';
import financeEditHandler from './finance-edit';

import { db } from './db';

import { registerSW } from 'virtual:pwa-register';

if ('serviceWorker' in navigator) {
    registerSW({
        immediate: true,
        onNeedRefresh() {
            console.log("Konten baru tersedia, silakan refresh.");
        },
        onOfflineReady() {
            console.log("Aplikasi siap digunakan secara offline.");
        },
    });
}

Alpine.plugin(persist);

Alpine.data('cashierHandler', cashierHandler);
Alpine.data('financeHandler', financeHandler);
Alpine.data('financeEditHandler', financeEditHandler);

window.AutoNumeric = AutoNumeric;
window.Alpine = Alpine;
window.db = db;

Alpine.start();