import { db } from './db';

export default function cashierHandler(initialProducts = [], initialCategories = []) {
    return {
        products: [],
        categories: [],
        cart: Alpine.$persist([]).as('pos-cart'),
        
        search: '',
        selectedCategory: null, 
        sortType: 'name_az',    
        priceMode: 'consument',
        isOffline: !navigator.onLine,
        manualTotal: null,

        async init() {
            window.addEventListener('online', () => { this.isOffline = false; this.syncTransactions(); });
            window.addEventListener('offline', () => { this.isOffline = true; });

            setInterval(async () => {
                if (navigator.onLine) {
                    try {
                    if (window.db) {    
                        const pendingCount = await db.offline_transactions.where('is_synced').equals(0).count();
                        if (pendingCount > 0) {
                            this.syncTransactions();
                        }
                    }
                }
                catch (e) {
                    console.error('Gagal sinkronisasi otomatis:', e);
                    }
                }
            }, 10000);

            try {
                const pendingCount = await db.offline_transactions.where('is_synced').equals(0).count();

                if (initialProducts.length > 0 && pendingCount === 0) {
                    
                    console.log("🔄 Data Server bersih & lebih baru. Mengupdate DB Lokal...");
                    await db.products.clear();
                    await db.products.bulkPut(initialProducts);
                    
                    await db.categories.clear();
                    await db.categories.bulkPut(initialCategories);
                    
                } else {
                    console.log("⚠️ Ada Transaksi Offline. Menggunakan Data Lokal (Agar stok tidak reset).");
                }
            } catch (e) {
                console.error('Gagal inisialisasi DB:', e);
            }

            this.products = await db.products.toArray();
            this.categories = await db.categories.toArray();
        },
        get filteredProducts() {
            let result = this.products;

            if (this.selectedCategory) {
                result = result.filter(p => p.item_category_id == this.selectedCategory);
            }

            if (this.search) {
                const q = this.search.toLowerCase();
                result = result.filter(p => p.name.toLowerCase().includes(q));
            }

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

        getPrice(product) {
            let p = 0;
            if (this.priceMode === 'r1') p = product.price_r1;
            else if (this.priceMode === 'r2') p = product.price_r2;
            else p = product.price_consument;

            return Number(p) || 0;
        },

        addToCart(product) {
            const activePrice = this.getPrice(product);
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                if (existingItem.price !== activePrice) {
                    const oldPrice = this.formatRupiah(existingItem.price);
                    const newPrice = this.formatRupiah(activePrice);

                    alert(
                        `⚠️ GAGAL MENAMBAH ITEM!\n\n` +
                        `Produk ini sudah ada di keranjang dengan harga: ${oldPrice}\n` +
                        `Sedangkan mode harga Anda saat ini adalah: ${newPrice}\n\n` +
                        `Solusi: Hapus item dari keranjang terlebih dahulu jika ingin menggunakan harga baru.`
                    );
                    return;
                }

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

        async decrementLocalStock(soldItems) {
            for (let item of soldItems) {
                const product = this.products.find(p => p.id === item.id);
                
                if (product) {
                    product.stock_opname -= item.qty;

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
            if (this.cart.length === 0) {
                this.manualTotal = null;
                return 0;
            }

            if (this.manualTotal !== null) {
                return this.manualTotal;
            }

            return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
        },

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
                try {
                    await db.offline_transactions.add({ ...payload, is_synced: 0 });
                    
                    await this.decrementLocalStock(cleanCart);
                    
                    alert('OFFLINE: Transaksi tersimpan lokal.');

                    if (typeof window.printReceipt === 'function') {
                        window.printReceipt(null, payload); 
                    }
                    this.cart = [];
                } catch (e) {
                    console.error(e);
                    alert('Gagal simpan offline');
                }
            } else {
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
                    if (!res.ok) throw new Error(res.statusText);
                    return res.json(); 
                })
                .then(async response => {
                    await this.decrementLocalStock(cleanCart);

                    if (response.transaction_id && typeof window.printReceipt === 'function') {
                        window.printReceipt(response.transaction_id);
                    }

                    alert('Transaksi Berhasil!');
                    this.cart = [];
                    this.manualTotal = null;
                })
                .catch(async error => {
                    console.error('Network Error, saving offline...', error);
                    await db.offline_transactions.add({...payload, is_synced: 0});

                    await this.decrementLocalStock(cleanCart);
                    
                    alert('Koneksi terputus. Tersimpan offline.');
                    this.cart = [];
                    this.manualTotal = null;
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

                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow';
                        toast.innerText = `Data offline (ID: ${trx.id}) berhasil disimpan ke server.`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 5000);

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