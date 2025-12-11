// =====================
// QZ TRAY GLOBAL CONFIG
// =====================
// Set SHA512 algorithm (IMPORTANT!)
qz.security.setSignatureAlgorithm("SHA512");

// Load certificate
qz.security.setCertificatePromise(function(resolve, reject) {
    fetch("/qz/digital-certificate", { 
        cache: "no-store",
        headers: { 'Content-Type': 'text/plain' }
    })
    .then(res => res.text())
    .then(resolve)
    .catch(reject);
});

// Sign requests
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        fetch("/qz/sign", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ toSign: toSign })
        })
        .then(res => res.text())
        .then(resolve)
        .catch(reject);
    };
});

// Connect
async function connectQZ() {
    if (qz.websocket.isActive()) return;
    
    try {
        await qz.websocket.connect();
        console.log("QZ Connected - Silent printing enabled!");
    } catch (err) {
        console.error("QZ Connection failed:", err);
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

// =====================
//     PRINT RECEIPT
// =====================
function separator(width = 32) {
    return "-".repeat(width) + "\n";
}

function formatItemLine(name, qty, price, total) {
    const col1 = 24;  // name
    const col2 = 6;   // qty
    const col3 = 9;   // price
    const col4 = 9;   // total

    // Cropping jika nama terlalu panjang
    if (name.length > col1) {
        name = name.substring(0, col1 - 1) + "…";
    }

    return (
        name.padEnd(col1) +
        qty.toString().padStart(col2) +
        price.toString().padStart(col3) +
        total.toString().padStart(col4)
    );
}


async function printReceipt(saleId) {
    try {
        await connectQZ();

        const res = await fetch(`/receipt/${saleId}`);
        const data = await res.json();

        const config = qz.configs.create(PRINTER_NAME);
        const esc = "\x1B";
        const gs  = "\x1D";

        const cmds = [
            esc + "@",

            // header center
            esc + "a" + "\x01",
            data.store.name + "\n",
            data.store.address + "\n\n\n",

            // left
            esc + "a" + "\x00",
            "Tanggal: " + data.transaction.datetime + "\n",
            separator(48)
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
        const totalStr = data.transaction.total.toLocaleString("id-ID");
        cmds.push(
            separator(48),
            "TOTAL : ".padEnd(48 - totalStr.length) + totalStr + "\n",
            separator(48),
            "\n\n",
            esc + "a" + "\x01",
            "Terima kasih atas kunjungan Anda!\n",
            "Silahkan datang kembali!\n\n",
            "Hubungi kami:\n",
            data.store.phone + "\n",
            data.store.email + "\n",
            gs + "V" + "\x00"
        );

        await qz.print(config, cmds);

    } catch (err) {
        console.error("QZ ERROR:", err);
        alert("Gagal mencetak struk.\n" + err);
    }
}

