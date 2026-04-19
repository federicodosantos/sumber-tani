/**
 * Thermal Printer Orchestrator
 * Connects layouts with QZ Tray and provides global functions
 */

const ThermalPrinter = {
    
    /**
     * Print Cashier Receipt (Original logic)
     */
    async printCashierReceipt(saleId, offlineData = null) {
        try {
            await connectQZ();
            let data = null;

            if (saleId) {
                const res = await fetch(`/receipt/${saleId}`);
                if (!res.ok) throw new Error("Gagal ambil data struk dari server");
                data = await res.json();
            } else if (offlineData) {
                // Offline fallback (only for cashier)
                const now = new Date();
                const dateStr = now.toLocaleDateString('id-ID', { 
                    day: 'numeric', month: 'long', year: 'numeric' 
                }) + ' ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

                data = {
                    store: {
                        name: "TOKO SUMBERTANI",
                        address: "Jl. Trans Sulawesi, Motolohu, Kec. Randangan,\nKab. Pohuwato, Gorontalo 96469",
                        phone: "+6281356745129",
                        email: "sumbertani0209@gmail.com"
                    },
                    transaction: {
                        datetime: dateStr,
                        total: offlineData.totalAmount,
                        discount: offlineData.discount,
                        payment_method: offlineData.payment_method || "Cash",
                        cash_received: offlineData.cash_received ?? null,
                        change_amount: offlineData.change_amount ?? null,
                    },
                    items: offlineData.items.map(item => ({
                        name: item.name,
                        qty: item.qty,
                        price: item.price,
                        total: item.price * item.qty
                    }))
                };
            } else {
                throw new Error("Tidak ada data untuk dicetak!");
            }

            const cmds = layoutCashierReceipt(data);
            const config = qz.configs.create(PRINTER_NAME);
            
            await qz.print(config, cmds);
        } catch (err) {
            console.error("QZ ERROR (Cashier):", err);
            alert("Gagal mencetak struk kasir.\n" + err);
        }
    },

    /**
     * Print R2 Customer Invoice (New logic)
     */
    async printR2Invoice(invoiceId) {
        try {
            await connectQZ();
            
            // Online only for R2
            const res = await fetch(`/invoice-receipt/${invoiceId}`);
            if (!res.ok) throw new Error("Gagal ambil data invoice dari server");
            const data = await res.json();

            const cmds = layoutR2Invoice(data);
            const config = qz.configs.create(PRINTER_NAME);
            
            await qz.print(config, cmds);
        } catch (err) {
            console.error("QZ ERROR (R2):", err);
            alert("Gagal mencetak nota R2.\n" + err);
        }
    }
};

// Aliases for backward compatibility in cashier.js
async function printReceipt(saleId, offlineData = null) {
    return await ThermalPrinter.printCashierReceipt(saleId, offlineData);
}
