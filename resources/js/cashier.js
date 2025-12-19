import { db } from './db';

// Pastikan export default menerima props dari Layout
export default function cashierHandler(initialProducts = [], initialCategories = []) {
    return {
        // --- DATA STATE ---
        products: [],
        categories: [],
        cart: Alpine.$persist([]).as('pos-cart'),
        
        // --- UI STATE ---
        search: '',
        selectedCategory: null, 
        sortType: 'name_az',    
        priceMode: 'consument',
        isOffline: !navigator.onLine,

        // ============================================================
        // BAGIAN YANG DIMODIFIKASI (SMART INIT)
        // ============================================================
        async init() {
            window.addEventListener('online', () => { this.isOffline = false; this.syncTransactions(); });
            window.addEventListener('offline', () => { this.isOffline = true; });

            try {
                // 1. Cek dulu: Apakah ada transaksi offline yang belum terkirim?
                const pendingCount = await db.offline_transactions.where('is_synced').equals(0).count();

                // 2. Logika Update Database:
                // Kita HANYA menimpa DB lokal dengan data Server (initialProducts) JIKA:
                // a. Ada data dari server.
                // b. DAN Tidak ada transaksi offline yang nyangkut (pendingCount === 0).
                if (initialProducts.length > 0 && pendingCount === 0) {
                    
                    console.log("🔄 Data Server bersih & lebih baru. Mengupdate DB Lokal...");
                    await db.products.clear();
                    await db.products.bulkPut(initialProducts);
                    
                    await db.categories.clear();
                    await db.categories.bulkPut(initialCategories);
                    
                } else {
                    // Jika pendingCount > 0, kita JANGAN update dari server.
                    // Karena data di DB Lokal lebih akurat (stoknya sudah berkurang karena transaksi offline).
                    console.log("⚠️ Ada Transaksi Offline. Menggunakan Data Lokal (Agar stok tidak reset).");
                }
            } catch (e) {
                console.error('Gagal inisialisasi DB:', e);
            }

            // 3. Load Data Akhir ke Tampilan (Alpine State)
            // Selalu ambil dari IndexedDB agar konsisten
            this.products = await db.products.toArray();
            this.categories = await db.categories.toArray();
        },
        // ============================================================

        // --- CORE LOGIC: FILTERING & SORTING ---
        get filteredProducts() {
            let result = this.products;

            // Filter Category
            if (this.selectedCategory) {
                result = result.filter(p => p.item_category_id == this.selectedCategory);
            }

            // Filter Search
            if (this.search) {
                const q = this.search.toLowerCase();
                result = result.filter(p => p.name.toLowerCase().includes(q));
            }

            // Sorting
            return result.sort((a, b) => {
                switch (this.sortType) {
                    case 'price_low': return this.getPrice(a) - this.getPrice(b);
                    case 'price_high': return this.getPrice(b) - this.getPrice(a);
                    case 'stock_low': return a.stock_opname - b.stock_opname;
                    case 'stock_high': return b.stock_opname - a.stock_opname;
                    case 'name_za': return b.name.localeCompare(a.name);
                    default: return a.name.localeCompare(b.name);
                }
            });
        },

        // --- HELPER: HARGA DINAMIS ---
        getPrice(product) {
            let p = 0;
            if (this.priceMode === 'r1') p = product.price_r1;
            else if (this.priceMode === 'r2') p = product.price_r2;
            else p = product.price_consument;

            return Number(p) || 0;
        },

        // --- CART LOGIC ---
        addToCart(product) {
            const activePrice = this.getPrice(product);
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                if (existingItem.qty < product.stock_opname) {
                    existingItem.qty++;
                    existingItem.price = activePrice; 
                } else {
                    alert('Stok tidak mencukupi!');
                }
            } else {
                if (product.stock_opname > 0) {
                    this.cart.push({
                        id: product.id,
                        name: product.name,
                        price: activePrice,
                        stock: product.stock_opname,
                        qty: 1
                    });
                } else {
                    alert('Stok habis!');
                }
            }
        },

        // --- UPDATE STOK REALTIME ---
        async decrementLocalStock(soldItems) {
            for (let item of soldItems) {
                // 1. Cari produk di State Alpine (Tampilan Layar)
                const product = this.products.find(p => p.id === item.id);
                
                if (product) {
                    // Kurangi stok di layar
                    // (Saya rapikan syntaxnya sedikit agar lebih standar JS)
                    product.stock_opname -= item.qty;

                    // 2. Update juga di IndexedDB 'products' 
                    if (window.db) {
                        try {
                            await db.products.update(product.id, {
                                stock_opname: product.stock_opname
                            });
                        } catch (e) {
                            console.error('Gagal update stok lokal:', e);
                        }
                    }
                }
            }
        },

        updateQty(id, change) {
            const item = this.cart.find(item => item.id === id);
            if (!item) return;

            const newQty = item.qty + change;
            
            if (newQty > item.stock) {
                alert('Stok tidak mencukupi! Stok tersedia: ' + item.stock);
                return;
            }
            
            if (newQty <= 0) {
                this.removeItem(id);
            } else {
                item.qty = newQty;
            }
        },

        setQty(id, value) {
            const item = this.cart.find(item => item.id === id);
            if (!item) return;
            let newQty = parseInt(value) || 0;
            if (newQty <= 0) newQty = 1;

            if (newQty > item.stock) {
                alert(`Stok tidak mencukupi! Max: ${item.stock}`);
                item.qty = item.stock; 
                return;
            }
            item.qty = newQty;
        },

        handleQtyBlur(id, event) {
            const value = event.target.value;
            const item = this.cart.find(item => item.id === id);
            if (!item) return;
            if (!value || value === '' || parseInt(value) <= 0) {
                event.target.value = item.qty;
                alert('Jumlah minimal adalah 1');
                return;
            }
            this.setQty(id, value);
        },

        removeItem(id) {
            this.cart = this.cart.filter(item => item.id !== id);
        },

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        },

        get totalQty() {
            return this.cart.reduce((total, item) => total + item.qty, 0);
        },
        
        get totalPrice() {
            return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
        },

        // --- CHECKOUT & SYNC ---
        async processCheckout() {
            if (this.cart.length === 0) return alert('Keranjang kosong');
            if (!confirm('Proses Transaksi?')) return;

            const cleanCart = JSON.parse(JSON.stringify(this.cart));
            const offlineUuid = self.crypto.randomUUID();

            const payload = {
                items: cleanCart,
                totalQty: this.totalQty,
                totalAmount: this.totalPrice,
                created_at: new Date().toISOString(),
                offline_uuid: offlineUuid
            };

            if (this.isOffline) {
                // OFFLINE FLOW
                try {
                    await db.offline_transactions.add({ ...payload, is_synced: 0 });
                    
                    // Update Stok
                    await this.decrementLocalStock(cleanCart);
                    
                    alert('OFFLINE: Transaksi tersimpan lokal.');
                    this.cart = [];
                } catch (e) {
                    console.error(e);
                    alert('Gagal simpan offline');
                }
            } else {
                // ONLINE FLOW
                fetch('/checkout', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    // Tambahkan return res.json() agar data response bisa dipakai
                    if (!res.ok) throw new Error(res.statusText);
                    return res.json(); 
                })
                .then(async response => {
                    // Update Stok
                    await this.decrementLocalStock(cleanCart);

                    alert('Transaksi Berhasil!');
                    this.cart = [];
                })
                .catch(async error => {
                    console.error('Network Error, saving offline...', error);
                    await db.offline_transactions.add({...payload, is_synced: 0});

                    // Update Stok
                    await this.decrementLocalStock(cleanCart);
                    
                    alert('Koneksi terputus. Tersimpan offline.');
                    this.cart = [];
                });
            }
        },

        async syncTransactions() {
            const unsynced = await db.offline_transactions.toArray();
            if (unsynced.length === 0) return;

            console.log(`Menyinkronkan ${unsynced.length} transaksi offline...`);

            try {
                const tokenResponse = await fetch('/refresh-csrf');
                if (tokenResponse.ok) {
                    const newCsrfToken = await tokenResponse.text();
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', newCsrfToken);
                    console.log('✅ CSRF Token diperbarui.');
                }
            } catch (e) {
                console.warn('⚠️ Gagal refresh token, mencoba dengan token lama...');
            }

            let authError = false;

            for (let trx of unsynced) {
                if (authError) break;

                try {
                    const response = await fetch('/checkout', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(trx)
                    });

                    if (response.ok) {
                        console.log('Sinkronisasi berhasil untuk ID:', trx.id);
                        await db.offline_transactions.delete(trx.id);
                        continue;
                    }

                    if (response.status === 422) {
                        const errorData = await response.json();
                        console.error('Validasi gagal untuk ID:', trx.id, errorData);
                        await db.offline_transactions.update(trx.id, {
                            is_synced: -1,
                            sync_error: JSON.stringify(errorData)
                        });
                        continue;
                    }

                    if (response.status === 401 || response.status === 419) {
                        console.error('Sesi habis.');
                        authError = true;
                        alert('Sesi Anda telah habis. Silakan login ulang.');
                        window.location.href = '/';
                        return;
                    }

                } catch (e) {
                    console.error('Gagal sync ID:', trx.id);
                    this.isOffline = true;
                    return; 
                }
            }
            
            if (!authError) {
                const sisa = await db.offline_transactions.where('is_synced').equals(0).count();
                if (sisa === 0) {
                    alert('Semua data offline berhasil disinkronisasi!');
                } else {
                    alert(`Sinkronisasi selesai. Sisa ${sisa} transaksi gagal (cek error).`);
                }
            }
        }
    };
}