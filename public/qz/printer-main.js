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
            
            let msg = err.message || String(err);
            
            // --- TRANSLASI ERROR KE BAHASA MANUSIA ---
            if (msg.includes("reading '0'") || msg.includes("undefined")) {
                msg = "Terjadi gangguan koneksi ke QZ Tray. Pastikan aplikasinya sudah berjalan dan icon di taskbar berwarna hijau.";
            } else if (msg.includes("Printer not found")) {
                msg = `Printer "${typeof PRINTER_NAME !== 'undefined' ? PRINTER_NAME : 'kasir'}" tidak ditemukan.\nPastikan nama printer di Windows sesuai.`;
            } else if (msg.includes("web socket is not open") || msg.includes("Socket not connected")) {
                msg = "Koneksi ke QZ Tray terputus. Silakan refresh halaman atau buka kembali aplikasi QZ Tray.";
            } else if (msg.includes("Failed to fetch")) {
                msg = "Gagal mengambil data struk dari server. Periksa koneksi internet/jaringan Anda.";
            } else if (msg.includes("Permission denied")) {
                msg = "Akses cetak ditolak oleh QZ Tray. Silakan klik 'Allow' pada pop-up QZ Tray.";
            }

            window.dispatchEvent(new CustomEvent('open-qz-error', { 
                detail: { 
                    title: "GAGAL MENCETAK STRUK", 
                    message: "Detail: " + msg 
                } 
            }));
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
            
            let msg = err.message || String(err);
            
            // --- TRANSLASI ERROR KE BAHASA MANUSIA ---
            if (msg.includes("reading '0'") || msg.includes("undefined")) {
                msg = "Terjadi gangguan koneksi ke QZ Tray. Pastikan aplikasinya sudah berjalan dan icon di taskbar berwarna hijau.";
            } else if (msg.includes("Printer not found")) {
                msg = `Printer "${typeof PRINTER_NAME !== 'undefined' ? PRINTER_NAME : 'kasir'}" tidak ditemukan.\nPastikan nama printer di Windows sesuai.`;
            } else if (msg.includes("web socket is not open") || msg.includes("Socket not connected")) {
                msg = "Koneksi ke QZ Tray terputus. Silakan refresh halaman atau buka kembali aplikasi QZ Tray.";
            } else if (msg.includes("Failed to fetch")) {
                msg = "Gagal mengambil data nota dari server. Periksa koneksi internet/jaringan Anda.";
            } else if (msg.includes("Permission denied")) {
                msg = "Akses cetak ditolak oleh QZ Tray. Silakan klik 'Allow' pada pop-up QZ Tray.";
            }

            window.dispatchEvent(new CustomEvent('open-qz-error', { 
                detail: { 
                    title: "GAGAL MENCETAK NOTA", 
                    message: "Detail: " + msg 
                } 
            }));
        }
    }
};

// Aliases for backward compatibility in cashier.js
async function printReceipt(saleId, offlineData = null) {
    return await ThermalPrinter.printCashierReceipt(saleId, offlineData);
}
