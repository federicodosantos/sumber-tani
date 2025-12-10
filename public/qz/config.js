// =====================
// QZ TRAY GLOBAL CONFIG
// =====================

// --- QZ auto connect with retry ---
async function connectQZ() {
    if (qz.websocket.isActive()) return;

    try {
        await qz.websocket.connect();
        console.log("QZ Connected.");
    } catch (err) {
        console.warn("QZ gagal connect, retry dalam 1s...");
        setTimeout(connectQZ, 1000);
    }
}

function disconnectQZ() {
    if (qz.websocket.isActive()) {
        qz.websocket.disconnect();
    }
}

document.addEventListener("DOMContentLoaded", connectQZ);
window.addEventListener("beforeunload", disconnectQZ);


// =====================
//   Printer Utilities
// =====================
async function listPrinters() {
    try {
        await connectQZ();
        const printers = await qz.printers.find();
        console.log("Daftar printer:", printers);
        alert(printers.join("\n"));
    } catch (err) {
        alert("Gagal mengambil daftar printer.\n" + err);
    }
}


// =====================
//  Print Configuration
// =====================
const PRINTER_NAME = "Generic / Text Only";    // update sesuai listPrinters
const LINE_CHARS = 42;

// helper formatting
function padRight(text, len) { return (text + " ".repeat(len)).slice(0, len); }
function padLeft(text, len) { return (" ".repeat(len) + text).slice(-len); }

function formatItemLine(name, qty, price, total) {
    return (
        padRight(name, 20) +
        padLeft(qty, 3) +
        padLeft(price, 8) +
        padLeft(total, 9)
    );
}


// =====================
//     PRINT RECEIPT
// =====================
async function printReceipt(saleId) {
    try {
        // pastikan QZ connect
        await connectQZ();

        const res = await fetch(`/receipt/${saleId}`);
        const data = await res.json();

        const config = qz.configs.create(PRINTER_NAME);
        const esc = "\x1B";   // ESC
        const gs  = "\x1D";   // GS

        const cmds = [
            esc + "@",                     // init
            esc + "a" + "\x01",            // center
            data.store.name + "\n",
            data.store.address + "\n",
            "------------------------------\n",
            esc + "a" + "\x00",            // left
            "Tanggal: " + data.transaction.datetime + "\n",
            "------------------------------\n"
        ];

        data.items.forEach(item => {
            cmds.push(
                formatItemLine(
                    item.name,
                    item.qty,
                    item.price.toLocaleString("id-ID"),
                    item.total.toLocaleString("id-ID")
                ) + "\n"
            );
        });

        cmds.push(
            "------------------------------\n",
            "TOTAL : " + padLeft(data.transaction.total.toLocaleString("id-ID"), 15) + "\n",
            "\n\n",
            gs + "V" + "\x00"              // cut
        );

        await qz.print(config, cmds);
        console.log("Print berhasil.");

    } catch (err) {
        console.error("QZ ERROR:", err);
        alert("Gagal mencetak struk.\n" + err);
    }
}
