import { db } from './db';

export default function cashierHandler(initialProducts = [], initialCategories = []) {
    return {
        products: [],
        categories: [],
        tabs: Alpine.$persist([]).as('pos-tabs'),
        activeTabId: Alpine.$persist(null).as('pos-active-tab'),

        search: '',
        selectedCategory: null,
        sortType: 'name_az',
        isOffline: !navigator.onLine,
        orderPanelWidth: 320,
        panelMinWidth: 320,
        isPanelResizing: false,
        leftSidebarCollapsed: false,

        // R2 Modal state (not per tab)
        showR2Modal: false,
        r2SearchQuery: '',
        r2SearchResults: [],
        isSearchingR2: false,
        r2SearchTimeout: null,

        // --- Reactive Proxies to activeTab ---
        get activeTab() { return this.tabs.find(t => t.id === this.activeTabId) || null; },
        get cart() { return this.activeTab?.cart || []; },
        set cart(val) { if (this.activeTab) this.activeTab.cart = val; },

        get paymentMethod() { return this.activeTab?.paymentMethod || 'Cash'; },
        set paymentMethod(val) { if (this.activeTab) this.activeTab.paymentMethod = val; },

        get isPaid() { return this.activeTab?.isPaid || false; },
        set isPaid(val) { if (this.activeTab) this.activeTab.isPaid = val; },

        get discount() { return this.activeTab?.discount || 0; },
        set discount(val) { if (this.activeTab) this.activeTab.discount = val; },

        get priceMode() { return this.activeTab?.priceMode || 'consument'; },
        set priceMode(val) { if (this.activeTab) this.activeTab.priceMode = val; },

        get manualTotal() { return this.activeTab?.manualTotal ?? null; },
        set manualTotal(val) { if (this.activeTab) this.activeTab.manualTotal = val; },

        get cashReceivedInput() { return this.activeTab?.cashReceivedInput || ''; },
        set cashReceivedInput(val) { if (this.activeTab) this.activeTab.cashReceivedInput = val; },

        get selectedCustomer() { return this.activeTab?.selectedCustomer || null; },
        set selectedCustomer(val) { if (this.activeTab) this.activeTab.selectedCustomer = val; },

        get customerCustomPrices() { return this.activeTab?.customerCustomPrices || {}; },
        set customerCustomPrices(val) { if (this.activeTab) this.activeTab.customerCustomPrices = val; },

        // --- Tab Management ---
        createNewTab(initialCart = []) {
            let uuid = Math.random().toString(36).substring(2, 10);
            try { uuid = self.crypto.randomUUID(); } catch(e) {}
            return {
                id: uuid,
                cart: initialCart,
                paymentMethod: 'Cash',
                isPaid: false,
                discount: 0,
                priceMode: 'consument',
                manualTotal: null,
                cashReceivedInput: '',
                selectedCustomer: null,
                customerCustomPrices: {},
                pendingSync: false,
                offlineUuid: null,
            };
        },
        openNewTab() {
            if (this.tabs.length >= 5) {
                alert('Maksimal 5 tab!');
                return;
            }
            const newTab = this.createNewTab();
            this.tabs.push(newTab);
            this.activeTabId = newTab.id;
        },
        switchTab(id) {
            this.activeTabId = id;
        },
        closeTab(id) {
            const tab = this.tabs.find(t => t.id === id);
            if (!tab) return;
            if (tab.pendingSync) {
                alert('Tab ini sedang menunggu sinkronisasi online dan tidak dapat ditutup. Tunggu hingga online.');
                return;
            }
            if (tab.cart.length > 0) {
                if (!confirm('Batalkan transaksi ini? Keranjang akan dikosongkan.')) return;
            }
            this.forceCloseTab(id);
        },
        forceCloseTab(id) {
            this.tabs = this.tabs.filter(t => t.id !== id);
            if (this.tabs.length === 0) {
                this.openNewTab();
            } else if (this.activeTabId === id) {
                this.activeTabId = this.tabs[this.tabs.length - 1].id;
            }
        },
        // --- Visual Stock ---
        getVisualStock(product) {
            if (!product) return 0;
            const qtyInAllTabs = this.tabs.reduce((sum, tab) => {
                const item = tab.cart.find(i => i.id === product.id);
                return sum + (item ? item.qty : 0);
            }, 0);
            return product.stock_opname - qtyInAllTabs;
        },

        async init() {
            if (this.tabs.length === 0) {
                let initialCart = [];
                try {
                    const oldStr = localStorage.getItem('pos-cart');
                    if (oldStr && oldStr.startsWith('[')) {
                        const parsed = JSON.parse(oldStr);
                        if (Array.isArray(parsed) && parsed.length > 0) initialCart = parsed;
                    }
                } catch(e) {}
                const newTab = this.createNewTab(initialCart);
                this.tabs.push(newTab);
                this.activeTabId = newTab.id;
            } else if (!this.tabs.some(t => t.id === this.activeTabId)) {
                this.activeTabId = this.tabs.length > 0 ? this.tabs[0].id : null;
            }

            window.addEventListener('online', () => {
                this.isOffline = false;
                this.syncTransactions();
            });
            window.addEventListener('offline', () => {
                this.isOffline = true;
            });

            setInterval(async () => {
                if (navigator.onLine) {
                    try {
                        if (window.db) {
                            const pendingCount = await db.offline_transactions.where('is_synced').equals(0).count();
                            if (pendingCount > 0) {
                                this.syncTransactions();
                            }
                        }
                    } catch (e) {
                        console.error('Gagal sinkronisasi otomatis:', e);
                    }
                }
            }, 10000);

            try {
                const pendingCount = await db.offline_transactions.where('is_synced').equals(0).count();

                if (initialProducts.length > 0 && pendingCount === 0) {
                    console.log('🔄 Data Server bersih & lebih baru. Mengupdate DB Lokal...');
                    await db.products.clear();
                    await db.products.bulkPut(initialProducts);

                    await db.categories.clear();
                    await db.categories.bulkPut(initialCategories);
                } else {
                    console.log('⚠️ Ada Transaksi Offline. Menggunakan Data Lokal (Agar stok tidak reset).');
                }
            } catch (e) {
                console.error('Gagal inisialisasi DB:', e);
            }

            this.products = await db.products.toArray();
            this.categories = await db.categories.toArray();
            this.syncCartPricesToMode();
            this.orderPanelWidth = Math.max(this.panelMinWidth, Math.min(420, Math.floor(window.innerWidth * 0.28)));
            this.leftSidebarCollapsed = localStorage.getItem('cashier-left-sidebar-collapsed') === '1';
        },

        openR2Modal() {
            this.showR2Modal = true;
            this.r2SearchQuery = '';
            this.r2SearchResults = [];
            this.fetchR2Customers();
        },

        closeR2Modal() {
            this.showR2Modal = false;
        },

        searchR2Customers() {
            clearTimeout(this.r2SearchTimeout);
            this.r2SearchTimeout = setTimeout(() => {
                this.fetchR2Customers();
            }, 300);
        },

        async fetchR2Customers() {
            this.isSearchingR2 = true;
            try {
                const params = new URLSearchParams();
                if (this.r2SearchQuery) params.set('q', this.r2SearchQuery);
                const res = await fetch(`/api/customer-r2/search?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    this.r2SearchResults = await res.json();
                }
            } catch (e) {
                console.error('Gagal mencari pelanggan R2:', e);
            } finally {
                this.isSearchingR2 = false;
            }
        },

        selectR2Customer(customer) {
            this.selectedCustomer = customer;
            this.showR2Modal = false;
            this.customerCustomPrices = {}; // clear while loading
            this.setPriceMode('r2');
            this.fetchCustomPricesForCustomer(customer.id);
        },

        removeR2Customer() {
            this.selectedCustomer = null;
            this.setPriceMode('consument');
            this.openR2Modal();
            this.customerCustomPrices = {};
        },

        async fetchCustomPricesForCustomer(customerId) {
            try {
                const res = await fetch(`/api/customer-r2/${customerId}/custom-prices`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.customerCustomPrices = data; // { product_id: custom_price, ... }
                    // Re-apply prices to cart items that may already be in the cart
                    this.syncCartPricesToMode();
                }
            } catch (e) {
                console.error('Gagal memuat harga khusus pelanggan:', e);
            }
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
            if (this.priceMode === 'r1') {
                p = product.price_r1;
            } else if (this.priceMode === 'r2') {
                // Use the customer-specific price if available, otherwise fall back to system r2 price
                const customPrice = this.customerCustomPrices[product.id];
                p = (customPrice !== undefined && customPrice !== null) ? customPrice : product.price_r2;
            } else {
                p = product.price_consument;
            }

            return Number(p) || 0;
        },

        setPaymentMethod(method) {
            this.paymentMethod = method;
            if (method !== 'Cash') {
                this.cashReceivedInput = '';
            }
        },

        toggleLeftSidebar() {
            this.leftSidebarCollapsed = !this.leftSidebarCollapsed;
            localStorage.setItem('cashier-left-sidebar-collapsed', this.leftSidebarCollapsed ? '1' : '0');
        },

        get panelMaxWidth() {
            return Math.floor(window.innerWidth / 2);
        },

        startOrderPanelResize(event) {
            event.preventDefault();
            this.isPanelResizing = true;
            document.body.classList.add('select-none');

            const onMove = (moveEvent) => {
                const nextWidth = window.innerWidth - moveEvent.clientX;
                const clampedWidth = Math.max(this.panelMinWidth, Math.min(nextWidth, this.panelMaxWidth));
                this.orderPanelWidth = clampedWidth;
            };

            const onUp = () => {
                this.isPanelResizing = false;
                document.body.classList.remove('select-none');
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };

            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        setPriceMode(mode) {
            if (this.priceMode === mode) return;

            if (mode !== 'r2') {
                this.selectedCustomer = null;
            }

            this.priceMode = mode;
            this.syncCartPricesToMode();
        },

        syncCartPricesToMode() {
            this.cart = this.cart.map(item => {
                const product = this.products.find(p => p.id === item.id);
                const basePrice = product ? this.getPrice(product) : Number(item.basePrice ?? item.price ?? 0);

                return {
                    ...item,
                    basePrice,
                    price: basePrice,
                    isManualPrice: false,
                };
            });

            this.manualTotal = null;
        },

        addToCart(product) {
            if (this.getVisualStock(product) <= 0) {
                alert('Stok habis!');
                return;
            }

            const activePrice = this.getPrice(product);
            const existingItem = this.cart.find(item => item.id === product.id);

            if (existingItem) {
                existingItem.qty++;
                return;
            }

            this.cart.push({
                id: product.id,
                name: product.name,
                basePrice: activePrice,
                price: activePrice,
                isManualPrice: false,
                stock: product.stock_opname,
                qty: 1,
            });
        },

        parseNumberInput(rawValue) {
            if (rawValue === null || rawValue === undefined) return 0;
            // Remove dot separators first, then handle comma as decimal
            const cleaned = rawValue.toString().replace(/\./g, '').replace(/,/g, '.');
            const parsed = Number(cleaned);
            return Number.isFinite(parsed) ? parsed : 0;
        },

        formatNumberInput(value) {
            if (value === null || value === undefined) return '';
            // Strip non-digit characters
            const digits = value.toString().replace(/[^0-9]/g, '');
            if (digits === '') return '';
            // Add dot thousand separators
            return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        setItemManualPrice(id, rawValue) {
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;

            const parsedValue = this.parseNumberInput(rawValue);
            if (parsedValue <= 0) {
                item.price = Number(item.basePrice) || 0;
                item.isManualPrice = false;
                return;
            }

            item.price = parsedValue;
            item.isManualPrice = parsedValue !== Number(item.basePrice);
            this.manualTotal = null;
        },

        resetItemPrice(id) {
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;

            item.price = Number(item.basePrice) || 0;
            item.isManualPrice = false;
        },

        async decrementLocalStock(soldItems) {
            for (const item of soldItems) {
                const product = this.products.find(p => p.id === item.id);

                if (product) {
                    product.stock_opname -= item.qty;

                    if (window.db) {
                        try {
                            await db.products.update(product.id, {
                                stock_opname: product.stock_opname,
                            });
                        } catch (e) {
                            console.error('Gagal update stok lokal:', e);
                        }
                    }
                }
            }
        },

        updateQty(id, change) {
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;
            const product = this.products.find(p => p.id === id);
            if (!product) return;

            const maxAllowed = this.getVisualStock(product) + item.qty;
            const newQty = item.qty + change;

            if (newQty > maxAllowed) {
                alert(`Stok tidak mencukupi! Stok tersedia: ${maxAllowed}`);
                return;
            }

            if (newQty <= 0) {
                this.removeItem(id);
            } else {
                item.qty = newQty;
            }
        },

        setQty(id, value) {
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;
            const product = this.products.find(p => p.id === id);
            if (!product) return;

            const maxAllowed = this.getVisualStock(product) + item.qty;
            let newQty = parseInt(value, 10) || 0;
            if (newQty <= 0) newQty = 1;

            if (newQty > maxAllowed) {
                alert(`Stok tidak mencukupi! Max: ${maxAllowed}`);
                item.qty = maxAllowed;
                return;
            }

            item.qty = newQty;
        },

        handleQtyBlur(id, event) {
            const value = event.target.value;
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;

            if (!value || parseInt(value, 10) <= 0) {
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
                minimumFractionDigits: 0,
            }).format(Number(number) || 0);
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
                return Number(this.manualTotal) || 0;
            }

            return this.cart.reduce((total, item) => total + ((Number(item.price) || 0) * item.qty), 0);
        },

        get systemCartTotal() {
            return this.cart.reduce((total, item) => total + ((Number(item.basePrice) || 0) * item.qty), 0);
        },

        get cashReceived() {
            return this.parseNumberInput(this.cashReceivedInput);
        },

        get changeAmount() {
            if (this.paymentMethod !== 'Cash') return 0;
            const change = this.cashReceived - this.totalPrice;
            return change > 0 ? change : 0;
        },

        async processCheckout() {
            if (this.cart.length === 0) return alert('Keranjang kosong');

            if (this.paymentMethod === 'Cash') {
                if (this.cashReceived <= 0) {
                    return alert('Masukkan uang consumer terlebih dahulu.');
                }

                if (this.cashReceived < this.totalPrice) {
                    return alert('Uang consumer kurang dari total transaksi.');
                }
            }

            if (!confirm('Proses Transaksi?')) return;

            const cleanCart = JSON.parse(JSON.stringify(this.cart));
            const offlineUuid = self.crypto.randomUUID();
            const originalTotal = cleanCart.reduce((total, item) => total + ((Number(item.price) || 0) * item.qty), 0);
            const isPaid = this.paymentMethod === 'Kredit' ? 0 : 1;

            let discountValue = 0;
            if (this.manualTotal !== null) {
                discountValue = originalTotal - parseFloat(this.manualTotal);
                if (discountValue < 0) discountValue = 0;
            }

            const payload = {
                items: cleanCart,
                totalQty: this.totalQty,
                totalAmount: this.totalPrice,
                discount: discountValue,
                payment_method: this.paymentMethod,
                created_at: new Date().toISOString(),
                offline_uuid: offlineUuid,
                is_paid: isPaid,
                cash_received: this.paymentMethod === 'Cash' ? this.cashReceived : null,
                change_amount: this.paymentMethod === 'Cash' ? this.changeAmount : null,
                customer_id: this.selectedCustomer ? this.selectedCustomer.id : null,
            };

            if (this.isOffline) {
                try {
                    await db.offline_transactions.add({ ...payload, is_synced: 0 });

                    await this.decrementLocalStock(cleanCart);

                    alert('OFFLINE: Transaksi tersimpan lokal. Tab ditunda hingga sinkronisasi.');

                    if (typeof window.printReceipt === 'function') {
                        window.printReceipt(null, payload);
                    }

                    if (this.activeTab) {
                        this.activeTab.pendingSync = true;
                        this.activeTab.offlineUuid = offlineUuid;
                    }
                    if (this.tabs.length < 5) {
                        this.openNewTab();
                    } else {
                        const nextTab = this.tabs.find(t => t.id !== this.activeTabId && !t.pendingSync);
                        if (nextTab) this.activeTabId = nextTab.id;
                    }
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
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
                        this.forceCloseTab(this.activeTabId);
                    })
                    .catch(async error => {
                        console.error('Network Error, saving offline...', error);
                        await db.offline_transactions.add({ ...payload, is_synced: 0 });

                        await this.decrementLocalStock(cleanCart);

                        alert('Koneksi terputus. Tersimpan offline.');
                        if (this.activeTab) {
                            this.activeTab.pendingSync = true;
                            this.activeTab.offlineUuid = offlineUuid;
                        }
                        if (this.tabs.length < 5) this.openNewTab();
                        else {
                            const nextTab = this.tabs.find(t => t.id !== this.activeTabId && !t.pendingSync);
                            if (nextTab) this.activeTabId = nextTab.id;
                        }
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

            for (const trx of unsynced) {
                if (authError) break;

                const payloadToSend = { ...trx };
                delete payloadToSend.id;
                delete payloadToSend.is_synced;
                delete payloadToSend.sync_error;

                try {
                    const response = await fetch('/checkout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payloadToSend),
                    });

                    if (response.ok) {
                        console.log('Sinkronisasi berhasil untuk ID:', trx.id);
                        await db.offline_transactions.delete(trx.id);

                        const tabToClose = this.tabs.find(t => t.offlineUuid === trx.offline_uuid);
                        if (tabToClose) {
                            this.forceCloseTab(tabToClose.id);
                        }

                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 rounded bg-green-500 px-4 py-2 text-white z-[9999]';
                        toast.innerText = `Data offline (ID: ${trx.id}) berhasil disinkronisasi.`;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 5000);

                        continue;
                    }

                    if (response.status === 422) {
                        const errorData = await response.json();
                        console.error('Validasi gagal untuk ID:', trx.id, errorData);
                        await db.offline_transactions.update(trx.id, {
                            is_synced: -1,
                            sync_error: JSON.stringify(errorData),
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
        },
    };
}
