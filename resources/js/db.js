// resources/js/db.js
import Dexie from 'dexie';

export const db = new Dexie('PosDatabase');

db.version(1).stores({
    // id, nama field yang dijadikan index pencarian
    products: 'id, item_category_id, name, stock_opname', 
    categories: 'id, name',
    // Transaksi offline yang belum disync
    offline_transactions: '++id, created_at, is_synced' 
});