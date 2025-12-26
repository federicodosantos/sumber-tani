// =====================
// QZ TRAY GLOBAL CONFIG
// =====================
qz.security.setSignatureAlgorithm("SHA512");

const DIGITAL_CERTIFICATE = `
-----BEGIN CERTIFICATE-----
MIIECzCCAvOgAwIBAgIGAZsL07lNMA0GCSqGSIb3DQEBCwUAMIGiMQswCQYDVQQG
EwJVUzELMAkGA1UECAwCTlkxEjAQBgNVBAcMCUNhbmFzdG90YTEbMBkGA1UECgwS
UVogSW5kdXN0cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMx
HDAaBgkqhkiG9w0BCQEWDXN1cHBvcnRAcXouaW8xGjAYBgNVBAMMEVFaIFRyYXkg
RGVtbyBDZXJ0MB4XDTI1MTIxMDA1MTMwM1oXDTQ1MTIxMDA1MTMwM1owgaIxCzAJ
BgNVBAYTAlVTMQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYD
VQQKDBJRWiBJbmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMs
IExMQzEcMBoGCSqGSIb3DQEJARYNc3VwcG9ydEBxei5pbzEaMBgGA1UEAwwRUVog
VHJheSBEZW1vIENlcnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCo
Tb+pldTAdefRwXoEM43CPZLd27lfhwacwmId4CxST/d8WvGWh/o8bHgb61+KW1nc
6SKEW9OTAuZfjVcz74ogG2ZaFaLzJf6JWOxGjJe5HS3BLt7NmvcaaYAXsP1oF7G3
IU/Rr8EuAk8McpRUqgdUNFKGQRFhDhTTwcHijsw6KgW0RXLIh3s6w5RiMdzLnAcM
tsbWgcTQe4cogfTf6EJj+1qtT/s0Xe+57TqZdMKj3WI6HtmvjAyGEzI27JSP/CLo
GcCrHsrXOPP6H5P4kaF6zoKXCuZS8iIHjRyc4Fwd7Et5wKXM4qdm1q9jFPoX6pv1
npj//amJRobr5/YMA5wdAgMBAAGjRTBDMBIGA1UdEwEB/wQIMAYBAf8CAQEwDgYD
VR0PAQH/BAQDAgEGMB0GA1UdDgQWBBRraXmlEZoj1t4hmsSfCE/4KElhozANBgkq
hkiG9w0BAQsFAAOCAQEAiANFhcYHV0NMNj/Khhe4ipK0NfwMdUsdwrIuGaJ0tpAn
wUzWhZXIwV3WuqF223lpmMQKJlsxSxYTJXQ2HbW8vzRF+sphfrk2wxREyeS+qR0j
E0+MY5oxe8IHHYmjBrObOwt38Glx2NkkNwKlDp8nOvDSlA0trdfDJnvI3mk5yjNa
dCfhgpOCCuyowOI2hzUCPNl+reo4uZPCtwMJFRzdZn+P+BxlEjHBBmlKT9zV4WD3
est6Jgd66TMXSo2v25kD+kcCeWFHr6ZrmPDQt1QDgfq9uHPPlKQVcu4nJCJIUT+n
5Si/WQuRbT1y6F5qTn0UUPz3m2LzqVGIOJdhsKW8rQ==
-----END CERTIFICATE-----
`;

// Load certificate
qz.security.setCertificatePromise(function(resolve, reject) {
    resolve(DIGITAL_CERTIFICATE);
});

// Sign requests
qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
        
        // CEK KONEKSI DULU
        if (!navigator.onLine) {
            console.warn("OFFLINE MODE: Skipping signature from server.");
            resolve(); 
            return;
        }

        fetch("/qz/sign", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({ toSign: toSign })
        })
        .then(res => {
            if (!res.ok) throw new Error(res.statusText);
            return res.text();
        })
        .then(resolve)
        .catch(err => {
            console.error("Gagal Sign (mungkin server down), lanjut tanpa sign.", err);
            resolve(); 
        });
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
const PRINTER_NAME = "Generic / Text Only"; // Ganti sesuai nama printer di Windows/Mac
const LINE_CHARS = 48; // Lebar kertas (biasanya 48 atau 32 atau 42)

// --- CONFIG STORE OFFLINE ---
// Karena saat offline kita gak bisa nanya nama toko ke server,
// Kita simpan hardcode disini untuk fallback.
const STORE_CONFIG = {
    name: "TOKO SUMBERTANI",
    address: "Jl. Trans Sulawesi, Motolohu, Kec. Randangan, Kab. Pohuwato, Gorontalo 96469",
    phone: "+6281356745129",
    email: "sumbertani0209@gmail.com"
};

function separator(width = 48) {
    return "-".repeat(width) + "\n";
}

function formatItemLine(name, qty, price, total) {
    // Sesuaikan lebar kolom dengan LINE_CHARS
    // Contoh untuk lebar 48:
    const col1 = 20;  // name
    const col2 = 6;   // qty
    const col3 = 11;  // price
    const col4 = 11;  // total

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

function headerItemLine() {
    const col1 = 20;  // ITEM
    const col2 = 6;   // JML
    const col3 = 11;  // HARGA
    const col4 = 11;  // SUBTOTAL

    return (
        "ITEM".padEnd(col1) +
        "JML".padStart(col2) +
        "HARGA".padStart(col3) +
        "SUBTOTAL".padStart(col4)
    );
}

function formatRightLine(label, value, width = LINE_CHARS) {
    const text = label + " : ";
    return text.padStart(width - value.length) + value;
}


// =====================
//    PRINT RECEIPT
// =====================
/**
 * Fungsi Hybrid: Bisa Online (by ID) atau Offline (by Data Object)
 * @param {string|number|null} saleId - ID transaksi (jika online)
 * @param {object|null} offlineData - Data belanjaan (jika offline)
 */
async function printReceipt(saleId, offlineData = null) {
    try {
        await connectQZ();

        let data = null;

        // --- LOGIKA 1: ONLINE (Punya ID) ---
        if (saleId) {
            const res = await fetch(`/receipt/${saleId}`);
            if (!res.ok) throw new Error("Gagal ambil data struk dari server");
            data = await res.json();
        } 
        // --- LOGIKA 2: OFFLINE (Punya Data Mentah) ---
        else if (offlineData) {
            // Kita harus 'menipu' struk supaya format datanya 
            // SAMA PERSIS dengan format JSON dari server.
            
            // Format tanggal lokal Indonesia
            const now = new Date();
            const dateStr = now.toLocaleDateString('id-ID', { 
                day: 'numeric', month: 'long', year: 'numeric' 
            }) + ' ' + now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

            data = {
                store: STORE_CONFIG, // Pakai config hardcode di atas
                transaction: {
                    datetime: dateStr,
                    total: offlineData.totalAmount, // Ambil dari payload
                    discount: offlineData.discount,
                },
                // Mapping items dari cart ke format struk
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

        // --- PROSES CETAK (Sama untuk Online & Offline) ---
        const config = qz.configs.create(PRINTER_NAME);
        const esc = "\x1B";
        const gs  = "\x1D";

        // Pastikan total di handle sebagai number sebelum toLocaleString
        const totalVal = Number(data.transaction.total); 

        const cmds = [
            esc + "@",

            // header center
            esc + "a" + "\x01",
            data.store.name + "\n",
            data.store.address + "\n\n",

            // left align info
            esc + "a" + "\x00",
            "Tanggal: " + data.transaction.datetime + "\n",
            separator(LINE_CHARS),

            // HEADER KOLOM
            headerItemLine() + "\n",
        ];


        // Loop items
        data.items.forEach(item => {
            cmds.push(
                formatItemLine(
                    item.name,
                    item.qty,
                    Number(item.price).toLocaleString("id-ID"),
                    Number(item.total).toLocaleString("id-ID")
                ) + "\n"
            );
        });

        const totalStr = totalVal.toLocaleString("id-ID");
        const discountVal = Number(data.transaction.discount || 0);
        const discountStr = discountVal.toLocaleString("id-ID");

        cmds.push(
            separator(LINE_CHARS),
            // Right align total manually using spaces
            formatRightLine("DISKON", discountStr) + "\n",
            "TOTAL : ".padStart(LINE_CHARS - totalStr.length - 1) + totalStr + "\n",
            separator(LINE_CHARS),
            "\n",
            
            // Footer Center
            esc + "a" + "\x01",
            "Terima kasih atas kunjungan Anda!\n",
            
            // Info kontak
            data.store.phone + "\n",
            data.store.email + "\n",
            gs + "V" + "\x00" // Cut paper
        );

        await qz.print(config, cmds);

    } catch (err) {
        console.error("QZ ERROR:", err);
        alert("Gagal mencetak struk.\n" + err);
    }
}