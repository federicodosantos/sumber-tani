export default function cashierHandler() {
    return {
        
        cart: Alpine.$persist([]).as('pos-cart'),

        get totalQty() {
            return this.cart.reduce((total, item) => total + item.qty, 0);
        },
        
        get totalPrice() {
            return this.cart.reduce((total, item) => total + (item.price * item.qty), 0);
        },

        addToCart(id, name, stock, price) {
            const existingItem = this.cart.find(item => item.id === id);

            if (existingItem) {
                if (existingItem.qty < stock) {
                    existingItem.qty++;
                } else {
                    alert('Stok tidak mencukupi!');
                }
            } else {
                if (stock > 0) {
                    this.cart.push({ id, name, price, stock, qty: 1 });
                } else {
                    alert('Stok habis!');
                }
            }
        },

        updateQty(id, change) {
            const item = this.cart.find(item => item.id === id);
            if (!item) return;

            const newQty = item.qty + change;
            if (newQty > item.stock) {
                alert('Stok mentok boss!');
                return;
            }
            if (newQty <= 0) {
                this.removeItem(id);
            } else {
                item.qty = newQty;
            }
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

        processCheckout() {
            if (this.cart.length === 0) return alert('Keranjang kosong');

            console.log('Checkout:', JSON.parse(JSON.stringify(this.cart)));

            if (confirm('Proses Transaksi?')) {
                this.cart = [];
                alert('Transaksi Berhasil!');
            }
        }
    };
}