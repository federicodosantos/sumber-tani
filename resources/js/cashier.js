import { db } from './db';

export default function cashierHandler(initialProducts = [], initialCategories = [], initialCustomers = [], initialCustomPrices = []) {
    return {
        products: [],
        categories: [],
        tabs: Alpine.$persist([]).as('pos-tabs'),
        activeTabId: Alpine.$persist(null).as('pos-active-tab'),

        search: '',
        highlightedIndex: 0,
        selectedCategory: null,
        sortType: 'name_az',
        isOffline: !navigator.onLine,
        orderPanelWidth: 320,
        panelMinWidth: 320,
        isPanelResizing: false,
        leftSidebarCollapsed: false,

        // Customer Modal state (not per tab)
        showR2Modal: false,
        r2SearchQuery: '',
        r2SearchResults: [],
        r2HighlightedIndex: 0,
        isCheckoutExpanded: true,
        isSearchingR2: false,
        r2SearchTimeout: null,
        customerTypeFilter: 'all',

        // Tracks any globally-opened <x-modal> by name to disable cashier shortcuts while open
        openModals: [],
        get isAnyModalOpen() { return this.openModals.length > 0; },

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

            window.addEventListener('open-modal', (e) => {
                if (e.detail && !this.openModals.includes(e.detail)) {
                    this.openModals.push(e.detail);
                }
            });
            window.addEventListener('modal-closed', (e) => {
                if (e.detail) {
                    this.openModals = this.openModals.filter(n => n !== e.detail);
                }
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

                    await db.customers_r2.clear();
                    await db.customers_r2.bulkPut(initialCustomers);

                    await db.r2_custom_prices.clear();
                    await db.r2_custom_prices.bulkPut(initialCustomPrices);
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

            // Keyboard navigation
            window.addEventListener('keydown', (e) => this.handleGlobalKeydown(e));
            
            // Watchers to reset highlight
            this.$watch('search', () => { this.highlightedIndex = 0; });
            this.$watch('selectedCategory', () => { this.highlightedIndex = 0; });
            this.$watch('sortType', () => { this.highlightedIndex = 0; });
            this.$watch('r2SearchResults', () => { this.r2HighlightedIndex = 0; });
        },

        calculateColumns() {
            if (window.innerWidth >= 1536) return 3; // 2xl
            if (window.innerWidth >= 1024) return 2; // lg
            return 1;
        },

        handleGlobalKeydown(e) {
            // R2 Modal Navigation Handling
            if (this.showR2Modal) {
                const results = this.r2SearchResults;

                if (e.key === 'Tab') {
                    e.preventDefault();
                    document.getElementById('r2-customer-search-input')?.focus();
                    return;
                }

                if (['ArrowUp', 'ArrowDown'].includes(e.key)) {
                    if (results.length === 0) return;
                    e.preventDefault();
                    const direction = e.key === 'ArrowUp' ? -1 : 1;
                    this.r2HighlightedIndex = Math.max(0, Math.min(results.length - 1, this.r2HighlightedIndex + direction));
                    
                    this.$nextTick(() => {
                        const el = document.getElementById(`r2-row-${this.r2HighlightedIndex}`);
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    });
                    return;
                }

                if (e.key === 'Enter') {
                    const isInSearch = document.activeElement.id === 'r2-customer-search-input';
                    
                    if (results.length > 0 && this.r2HighlightedIndex >= 0 && this.r2HighlightedIndex < results.length) {
                        e.preventDefault();
                        this.selectCustomer(results[this.r2HighlightedIndex]);
                        this.$dispatch('close-modal', 'r2-customer');
                    }
                    return;
                }

                // Don't block character typing in search input
                if (document.activeElement.id === 'r2-customer-search-input') return;

                // Stop other shortcuts from triggering while modal is open
                return;
            }

            // Global handling (shortcuts when modal is closed)

            // Block all cashier shortcuts while any other modal is open
            // (prevents Enter/Backspace/etc. from leaking into cart while confirm modal is shown)
            if (this.isAnyModalOpen) {
                return;
            }

            // Handle Ctrl shortcuts (Category & Price Mode & Checkout)
            if (e.ctrlKey) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const cashGuard = this.paymentMethod === 'Cash' && this.cashReceived < this.totalPrice;
                    if (this.cart.length > 0 && !cashGuard) {
                        this.processCheckout();
                    }
                    return;
                }
                if (['ArrowUp', 'ArrowDown'].includes(e.key)) {
                    e.preventDefault();
                    this.cycleCategory(e.key === 'ArrowUp' ? -1 : 1);
                    return;
                }
                if (['ArrowRight', 'ArrowLeft'].includes(e.key)) {
                    e.preventDefault();
                    this.cyclePriceMode(e.key === 'ArrowLeft' ? -1 : 1);
                    return;
                }
            }

            const products = this.filteredProducts;
            if (products.length === 0) return;

            const cols = this.calculateColumns();
            const maxIndex = products.length - 1;

            // Handle Enter to add highlighted item
            if (e.key === 'Enter') {
                // If focus is in an input other than search, let default happen
                if (document.activeElement.tagName === 'INPUT' && document.activeElement !== document.querySelector('input[x-model="search"]')) {
                    return;
                }
                
                if (this.highlightedIndex >= 0 && this.highlightedIndex <= maxIndex) {
                    e.preventDefault();
                    this.addToCart(products[this.highlightedIndex]);
                }
                return;
            }

            // Handle Backspace to decrease/remove highlighted item
            if (e.key === 'Backspace') {
                // If focus is in any input, let default backspace happen (character deletion)
                if (document.activeElement.tagName === 'INPUT') {
                    return;
                }

                if (this.highlightedIndex >= 0 && this.highlightedIndex <= maxIndex) {
                    const product = products[this.highlightedIndex];
                    const existingItem = this.cart.find(item => item.id === product.id);
                    if (existingItem) {
                        e.preventDefault();
                        this.updateQty(product.id, -1);
                    }
                }
                return;
            }

            // Arrow navigation
            if (['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'].includes(e.key)) {
                // If in search input, ArrowDown starts navigation
                if (document.activeElement.tagName === 'INPUT' && e.key !== 'ArrowDown') {
                     // Allow horizontal navigation in input
                     if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') return;
                }

                e.preventDefault();

                switch (e.key) {
                    case 'ArrowRight':
                        this.highlightedIndex = Math.min(maxIndex, this.highlightedIndex + 1);
                        break;
                    case 'ArrowLeft':
                        this.highlightedIndex = Math.max(0, this.highlightedIndex - 1);
                        break;
                    case 'ArrowDown':
                        if (document.activeElement === document.querySelector('input[x-model="search"]')) {
                            // Already at index 0 probably, just move focus away if desired or just update index
                        }
                        this.highlightedIndex = Math.min(maxIndex, this.highlightedIndex + cols);
                        break;
                    case 'ArrowUp':
                        this.highlightedIndex = Math.max(0, this.highlightedIndex - cols);
                        break;
                }

                // Scroll into view
                this.$nextTick(() => {
                    const el = document.getElementById(`product-card-${this.highlightedIndex}`);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            }

            // Shortcut to focus search
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
                e.preventDefault();
                document.querySelector('input[x-model="search"]')?.focus();
            }
        },

        cycleCategory(direction) {
            // [null, ...ids]
            const categoryIds = [null, ...this.categories.map(c => c.id)];
            const currentIndex = categoryIds.indexOf(this.selectedCategory);
            
            let nextIndex = currentIndex + direction;
            if (nextIndex < 0) nextIndex = categoryIds.length - 1;
            if (nextIndex >= categoryIds.length) nextIndex = 0;

            this.selectedCategory = categoryIds[nextIndex];
        },

        cyclePriceMode(direction) {
            // Two visible cashier modes: 'consument' and a customer mode (R1 or R2 driven by selected customer).
            const inCustomerMode = this.priceMode === 'r1' || this.priceMode === 'r2';

            if (inCustomerMode) {
                this.setPriceMode('consument');
                return;
            }

            // Switching to customer mode: open modal so cashier can pick R1 or R2.
            if (this.selectedCustomer) {
                this.priceMode = this.selectedCustomer.type === 'r1' ? 'r1' : 'r2';
                this.syncCartPricesToMode();
            } else {
                this.openCustomerModal();
                this.$dispatch('open-modal', 'r2-customer');
            }
        },

        openCustomerModal() {
            this.showR2Modal = true;
            this.r2SearchQuery = '';
            this.r2SearchResults = [];
            this.fetchR2Customers();
        },
        // Backward-compatible alias
        openR2Modal() { this.openCustomerModal(); },

        closeR2Modal() {
            this.showR2Modal = false;
        },

        setCustomerTypeFilter(type) {
            this.customerTypeFilter = ['r1', 'r2'].includes(type) ? type : 'all';
            this.fetchR2Customers();
        },

        searchR2Customers() {
            clearTimeout(this.r2SearchTimeout);
            this.r2SearchTimeout = setTimeout(() => {
                this.fetchR2Customers();
            }, 300);
        },

        _localFilterCustomers(rows) {
            const q = (this.r2SearchQuery || '').toLowerCase();
            const typeFilter = this.customerTypeFilter;
            return rows.filter(c => {
                const type = c.type || 'r2';
                if (typeFilter !== 'all' && type !== typeFilter) return false;
                if (!q) return true;
                return (
                    c.name.toLowerCase().includes(q) ||
                    (c.phone_number && c.phone_number.includes(q)) ||
                    (c.address && c.address.toLowerCase().includes(q))
                );
            });
        },

        async fetchR2Customers() {
            this.isSearchingR2 = true;
            try {
                if (this.isOffline) {
                    let results = this._localFilterCustomers(await db.customers_r2.toArray());
                    results.sort((a, b) => a.name.localeCompare(b.name));
                    this.r2SearchResults = results.slice(0, 50);
                } else {
                    const params = new URLSearchParams();
                    if (this.r2SearchQuery) params.set('q', this.r2SearchQuery);
                    if (this.customerTypeFilter !== 'all') params.set('type', this.customerTypeFilter);
                    const res = await fetch(`/api/customer-r2/search?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        this.r2SearchResults = await res.json();
                    }
                }
            } catch (e) {
                console.error('Gagal mencari pelanggan:', e);
                try {
                    let results = this._localFilterCustomers(await db.customers_r2.toArray());
                    results.sort((a, b) => a.name.localeCompare(b.name));
                    this.r2SearchResults = results.slice(0, 50);
                } catch (err) {
                    console.error('Fallback lokal gagal:', err);
                }
            } finally {
                this.isSearchingR2 = false;
            }
        },

        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.showR2Modal = false;
            this.customerCustomPrices = {};
            this.priceMode = (customer.type === 'r1') ? 'r1' : 'r2';
            this.fetchCustomPricesForCustomer(customer.id);
        },
        // Backward-compatible alias
        selectR2Customer(customer) { this.selectCustomer(customer); },

        removeCustomer() {
            this.selectedCustomer = null;
            this.setPriceMode('consument');
            this.openCustomerModal();
            this.customerCustomPrices = {};
        },
        // Backward-compatible alias
        removeR2Customer() { this.removeCustomer(); },

        async fetchCustomPricesForCustomer(customerId) {
            try {
                if (this.isOffline) {
                    const prices = await db.r2_custom_prices.where('customer_id').equals(customerId).toArray();
                    const priceMap = {};
                    prices.forEach(p => {
                        priceMap[p.product_id] = p.custom_price;
                    });
                    this.customerCustomPrices = priceMap;
                    this.syncCartPricesToMode();
                } else {
                    const res = await fetch(`/api/customer-r2/${customerId}/custom-prices`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.customerCustomPrices = data; // { product_id: custom_price, ... }
                        // Re-apply prices to cart items that may already be in the cart
                        this.syncCartPricesToMode();
                    }
                }
            } catch (e) {
                console.error('Gagal memuat harga khusus pelanggan:', e);
                try {
                    const prices = await db.r2_custom_prices.where('customer_id').equals(customerId).toArray();
                    const priceMap = {};
                    prices.forEach(p => {
                        priceMap[p.product_id] = p.custom_price;
                    });
                    this.customerCustomPrices = priceMap;
                    this.syncCartPricesToMode();
                } catch (err) {
                    console.error('Fallback lokal gagal:', err);
                }
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
                const customPrice = this.customerCustomPrices[product.id];
                p = (customPrice !== undefined && customPrice !== null) ? customPrice : product.price_r1;
            } else if (this.priceMode === 'r2') {
                const customPrice = this.customerCustomPrices[product.id];
                p = (customPrice !== undefined && customPrice !== null) ? customPrice : product.price_r2;
            } else {
                p = product.price_consument;
            }

            return Number(p) || 0;
        },

        // True when the active customer (R1 or R2) has a custom price that differs from the system base price.
        hasCustomR2Price(product) {
            if (!this.selectedCustomer) return false;
            if (this.priceMode !== 'r1' && this.priceMode !== 'r2') return false;
            const cp = this.customerCustomPrices[product.id];
            if (cp === undefined || cp === null) return false;
            const base = this.priceMode === 'r1' ? product.price_r1 : product.price_r2;
            return Number(cp) !== Number(base);
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

        toggleCheckoutExpansion() {
            this.isCheckoutExpanded = !this.isCheckoutExpanded;
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
            // Nilai dari komponen input-rupiah selalu berformat RAW (titik = desimal),
            // mis. "1234.567". Bukan format tampilan Indonesia (titik = ribuan).
            const cleaned = rawValue.toString().trim();
            if (cleaned === '') return 0;
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
            const targetId = Number(id);
            const parsedValue = this.parseNumberInput(rawValue);
            
            this.cart = this.cart.map(item => {
                if (Number(item.id) === targetId) {
                    const newPrice = parsedValue <= 0 ? (Number(item.basePrice) || 0) : parsedValue;
                    return {
                        ...item,
                        price: newPrice,
                        isManualPrice: newPrice !== Number(item.basePrice)
                    };
                }
                return item;
            });
            
            this.manualTotal = null;
            this.tabs = [...this.tabs]; // Force global reactive refresh
        },

        resetItemPrice(id) {
            const targetId = Number(id);
            this.cart = this.cart.map(item => {
                if (Number(item.id) === targetId) {
                    return {
                        ...item,
                        price: Number(item.basePrice) || 0,
                        isManualPrice: false
                    };
                }
                return item;
            });
            this.tabs = [...this.tabs]; // Force global reactive refresh
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

        setQty(id, value, event = null) {
            const item = this.cart.find(cartItem => cartItem.id === id);
            if (!item) return;
            const product = this.products.find(p => p.id === id);
            if (!product) return;

            const maxAllowed = this.getVisualStock(product) + item.qty;

            if (this.countDecimals(value) > 3) {
                alert('Jumlah maksimal 3 angka desimal.');
                if (event) event.target.value = this.formatQty(item.qty);
                return;
            }

            let newQty = parseFloat(String(value).replace(',', '.')) || 0;
            if (newQty <= 0) newQty = 0.001;

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

            if (!value || parseFloat(String(value).replace(',', '.')) <= 0) {
                event.target.value = item.qty;
                alert('Jumlah minimal adalah 0.001');
                return;
            }

            this.setQty(id, value, event);
        },

        countDecimals(value) {
            const str = String(value ?? '').trim();
            if (!str) return 0;
            const idx = Math.max(str.indexOf(','), str.indexOf('.'));
            if (idx === -1) return 0;
            return str.slice(idx + 1).length;
        },

        formatQty(qty) {
            return String(qty).replace('.', ',');
        },

        removeItem(id) {
            this.cart = this.cart.filter(item => item.id !== id);
        },

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 3,
            }).format(Number(number) || 0);
        },

        formatStock(value) {
            const n = Number(value);
            if (!Number.isFinite(n)) return '0';
            return n.toLocaleString('id-ID', { maximumFractionDigits: 3 });
        },

        get totalQty() {
            return Math.round(this.cart.reduce((total, item) => total + (Number(item.qty) || 0), 0) * 1000) / 1000;
        },

        get totalPrice() {
            if (this.cart.length === 0) {
                this.manualTotal = null;
                return 0;
            }

            if (this.manualTotal !== null) {
                return Math.round((Number(this.manualTotal) || 0) * 1000) / 1000;
            }

            return Math.round(this.cart.reduce((total, item) => total + ((Number(item.price) || 0) * (Number(item.qty) || 0)), 0) * 1000) / 1000;
        },

        get systemCartTotal() {
            return Math.round(this.cart.reduce((total, item) => total + ((Number(item.basePrice) || 0) * (Number(item.qty) || 0)), 0) * 1000) / 1000;
        },

        get cashReceived() {
            return this.parseNumberInput(this.cashReceivedInput);
        },

        get changeAmount() {
            if (this.paymentMethod !== 'Cash') return 0;
            const change = this.cashReceived - this.totalPrice;
            return Math.round((change > 0 ? change : 0) * 1000) / 1000;
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

            // Dispatch event to open confirm modal instead of using confirm()
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-checkout' }));
        },

        async executeCheckout() {

            const cleanCart = JSON.parse(JSON.stringify(this.cart));
            const offlineUuid = self.crypto.randomUUID();
            const originalTotal = Math.round(cleanCart.reduce((total, item) => total + ((Number(item.price) || 0) * (Number(item.qty) || 0)), 0) * 1000) / 1000;
            const isPaid = this.paymentMethod === 'Kredit' ? 0 : 1;

            let discountValue = 0;
            if (this.manualTotal !== null) {
                discountValue = Math.round((originalTotal - parseFloat(this.manualTotal)) * 1000) / 1000;
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

            // Simpan perubahan harga ke local cache (IndexedDB) jika pelanggan R2 dipilih
            if (this.selectedCustomer) {
                try {
                    for (const item of cleanCart) {
                        const basePrice = item.basePrice ?? null;
                        const manualPrice = item.price ?? null;
                        
                        if (basePrice !== null && manualPrice !== null && Number(manualPrice) !== Number(basePrice)) {
                            // 1. Update memory cache langsung
                            this.customerCustomPrices[item.id] = Number(manualPrice);
                            
                            // 2. Update Dexie local storage
                            if (window.db) {
                                const exist = await db.r2_custom_prices.where({
                                    customer_id: this.selectedCustomer.id,
                                    product_id: item.id
                                }).first();

                                if (exist) {
                                    await db.r2_custom_prices.update(exist.id, { custom_price: Number(manualPrice) });
                                } else {
                                    const tempId = Math.floor(Math.random() * 1000000); // Temporary ID for local reference
                                    await db.r2_custom_prices.put({
                                        id: tempId,
                                        customer_id: this.selectedCustomer.id,
                                        product_id: item.id,
                                        custom_price: Number(manualPrice)
                                    });
                                }
                            }
                        }
                    }
                } catch (e) {
                    console.error('Gagal update cache harga lokal:', e);
                }
            }

            if (this.isOffline) {
                try {
                    await db.offline_transactions.add({ ...payload, is_synced: 0 });

                    await this.decrementLocalStock(cleanCart);

                    alert('OFFLINE: Transaksi berhasil tersimpan lokal ke dalam antrean sinkronisasi.');

                    if (typeof window.printReceipt === 'function') {
                        window.printReceipt(null, payload);
                    }

                    this.forceCloseTab(this.activeTabId);
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

                        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'success-checkout' }));
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
