// =====================
//  PRINTER UTILITIES
// =====================

const PRINTER_NAME = "Generic / Text Only"; // Ganti sesuai nama printer di Windows/Mac
const LINE_CHARS = 48; // Lebar kertas (biasanya 48 atau 32 atau 42)

// ESC/POS Command Constants
const ESC = "\x1B";
const GS  = "\x1D";
const CMD_RESET = ESC + "@";
const CMD_ALIGN_LEFT   = ESC + "a" + "\x00";
const CMD_ALIGN_CENTER = ESC + "a" + "\x01";
const CMD_ALIGN_RIGHT  = ESC + "a" + "\x02";
const CMD_CUT_PAPER    = GS + "V" + "\x00";

/**
 * Creates a separator line
 */
function separator(width = LINE_CHARS) {
    return "-".repeat(width) + "\n";
}

/**
 * Formats a double line separator
 */
function doubleSeparator(width = LINE_CHARS) {
    return "=".repeat(width) + "\n";
}

/**
 * Formats 4 columns for receipt items
 */
function formatItemLine(name, qty, price, total) {
    const col1 = 20;  // name
    const col2 = 6;   // qty
    const col3 = 11;  // price
    const col4 = 11;  // total

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

/**
 * Header for item columns
 */
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

/**
 * Formats a right-aligned label-value pair
 */
function formatRightLine(label, value, width = LINE_CHARS) {
    const text = label + " : ";
    return text.padStart(width - value.length) + value;
}

/**
 * Utility for number formatting (Indonesian style)
 */
function formatIDR(value) {
    return Number(value).toLocaleString("id-ID");
}
