export default (config) => ({
    products: config.products || [],
    items: JSON.parse(JSON.stringify(config.initial.items || [])),
    discount: Number(config.initial.discount) || 0,
    payment_method: config.initial.payment_method || 'Cash',
    is_paid: !!config.initial.is_paid,
    cash_received: config.initial.cash_received,
    transaction_date: config.initial.transaction_date,
    submitting: false,

    // Per-row product search state
    activeSearchIdx: null,

    init() {
        // Hydrate item names from product list when missing
        this.items.forEach((row) => {
            const p = this.products.find((x) => x.id == row.id);
            if (p && !row.name) row.name = p.name;
            if (p && row.maxStock === undefined) row.maxStock = (p.stock || 0) + (Number(row.qty) || 0);
        });
    },

    get totalQty() {
        return Math.round(this.items.reduce((s, r) => s + (Number(r.qty) || 0), 0) * 1000) / 1000;
    },
    get subtotal() {
        return Math.round(
            this.items.reduce(
                (s, r) => s + (Number(r.price) || 0) * (Number(r.qty) || 0),
                0
            ) * 1000
        ) / 1000;
    },
    get totalAmount() {
        return Math.max(0, Math.round((this.subtotal - (Number(this.discount) || 0)) * 1000) / 1000);
    },
    get changeAmount() {
        if (this.payment_method !== 'Cash' || !this.is_paid) return 0;
        return Math.max(0, Math.round(((Number(this.cash_received) || 0) - this.totalAmount) * 1000) / 1000);
    },

    formatRp(n) {
        return 'Rp ' + Number(n || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        });
    },

    formatQty(qty) {
        return String(qty).replace('.', ',');
    },

    setQty(row, value) {
        const parsed = parseFloat(String(value).replace(',', '.')) || 0;
        row.qty = parsed < 0.001 ? 0.001 : parsed;
    },

    handleQtyBlur(row, event) {
        const parsed = parseFloat(String(event.target.value).replace(',', '.')) || 0;
        if (parsed < 0.001) {
            event.target.value = this.formatQty(row.qty);
            alert('Jumlah minimal adalah 0,001');
        }
    },

    addRow() {
        this.items.push({ id: '', name: '', price: 0, qty: 1, maxStock: null });
    },

    removeRow(idx) {
        this.items.splice(idx, 1);
    },

    pickProduct(idx, product) {
        const row = this.items[idx];
        row.id = product.id;
        row.name = product.name;
        row.maxStock = product.stock;
        if (!row.price || row.price === 0) row.price = product.price;
        if (!row.qty || row.qty < 0.001) row.qty = 1;
        this.activeSearchIdx = null;
    },

    incQty(idx) {
        const row = this.items[idx];
        row.qty = (Number(row.qty) || 0) + 1;
    },
    decQty(idx) {
        const row = this.items[idx];
        const next = (Number(row.qty) || 0) - 1;
        row.qty = Math.max(0.001, next);
    },

    productLabel(row) {
        if (row.name) return row.name;
        const p = this.products.find((x) => x.id == row.id);
        return p ? p.name : '';
    },

    async submitForm(e) {
        if (this.submitting) return;
        if (this.items.length === 0) {
            alert('Minimal harus ada 1 item.');
            return;
        }
        for (const row of this.items) {
            if (!row.id || !row.qty || row.qty < 0.001) {
                alert('Pastikan semua baris lengkap (produk dipilih & qty ≥ 0,001).');
                return;
            }
        }

        this.submitting = true;
        const form = e.target;
        const payload = new FormData();
        payload.append('_token', form.querySelector('[name=_token]').value);
        payload.append('_method', 'PUT');

        this.items.forEach((r, i) => {
            payload.append(`items[${i}][id]`, r.id);
            payload.append(`items[${i}][price]`, r.price);
            payload.append(`items[${i}][qty]`, r.qty);
        });
        payload.append('totalQty', this.totalQty);
        payload.append('totalAmount', this.totalAmount);
        payload.append('discount', this.discount || 0);
        payload.append('payment_method', this.payment_method);
        payload.append('is_paid', this.is_paid ? 1 : 0);

        if (
            this.payment_method === 'Cash' &&
            this.is_paid &&
            this.cash_received !== null &&
            this.cash_received !== ''
        ) {
            payload.append('cash_received', this.cash_received);
            payload.append('change_amount', this.changeAmount);
        }
        if (this.transaction_date) {
            payload.append('transaction_date', this.transaction_date.replace('T', ' '));
        }

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: payload,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (res.redirected) {
                window.location.href = res.url;
                return;
            }
            const text = await res.text();
            document.open();
            document.write(text);
            document.close();
        } catch (err) {
            alert('Gagal menyimpan: ' + err.message);
            this.submitting = false;
        }
    },

    filteredProducts(query) {
        const q = (query || '').toLowerCase().trim();
        if (!q) return this.products.slice(0, 50);
        return this.products
            .filter((p) => p.name.toLowerCase().includes(q))
            .slice(0, 50);
    },
});
