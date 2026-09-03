/**
 * Aritmatika desimal eksak untuk frontend (mirror DecimalMathService di backend).
 *
 * Berbasis integer-millis (nilai × 1000) agar bebas dari error floating-point
 * saat penjumlahan/perkalian lintas banyak item ber-3-desimal. Pembulatan
 * half-away-from-zero, konsisten dengan kolom DECIMAL(…,3) di MySQL.
 */

const SCALE = 3;

function toMillis(value) {
    if (value === null || value === undefined || value === '') return 0;
    const n = Number(String(value).replace(',', '.'));
    return Math.round((Number.isFinite(n) ? n : 0) * 1000);
}

function roundHalfAway(x) {
    return (x < 0 ? -1 : 1) * Math.round(Math.abs(x));
}

export function add(a, b) {
    return (toMillis(a) + toMillis(b)) / 1000;
}

export function sub(a, b) {
    return (toMillis(a) - toMillis(b)) / 1000;
}

export function mul(a, b) {
    return roundHalfAway((toMillis(a) * toMillis(b)) / 1000) / 1000;
}

export function sum(values) {
    return values.reduce((acc, v) => add(acc, v), 0);
}

export { SCALE };