// =====================
// QZ TRAY CORE CONFIG
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

qz.security.setCertificatePromise(function(resolve, reject) {
    resolve(DIGITAL_CERTIFICATE);
});

qz.security.setSignaturePromise(function(toSign) {
    return function(resolve, reject) {
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

async function connectQZ() {
    if (qz.websocket.isActive()) return;
    try {
        await qz.websocket.connect();
        console.log("QZ Connected - Thermal printing active!");
    } catch (err) {
        console.error("QZ Connection failed:", err);
        setTimeout(connectQZ, 2000);
    }
}

function disconnectQZ() {
    if (qz.websocket.isActive()) {
        qz.websocket.disconnect();
    }
}

document.addEventListener("DOMContentLoaded", connectQZ);
window.addEventListener("beforeunload", disconnectQZ);

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
