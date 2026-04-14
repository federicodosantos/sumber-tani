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

db.version(2).stores({
    products: 'id, item_category_id, name, stock_opname', 
    categories: 'id, name',
    offline_transactions: '++id, created_at, is_synced',
    customers_r2: 'id, name, phone_number, address',
    r2_custom_prices: 'id, customer_id, product_id'
});