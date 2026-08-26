<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Arithmetic desimal eksak berbasis BCMath untuk nilai uang/quantity.
 *
 * Semua operasi beroperasi pada string desimal dan mempertahankan skala
 * maksimal 3 desimal (default) tanpa error floating-point. Gunakan service
 * ini di seluruh perhitungan finance (total, piutang, FIFO, saldo kredit,
 * HPP, laba-rugi, neraca) sebagai pengganti arithmetic float.
 */
class DecimalMathService
{
    public const SCALE = 3;

    public function add(string|int|float $a, string|int|float $b, ?int $scale = null): string
    {
        return bcadd($this->normalize($a), $this->normalize($b), $scale ?? self::SCALE);
    }

    public function subtract(string|int|float $a, string|int|float $b, ?int $scale = null): string
    {
        return bcsub($this->normalize($a), $this->normalize($b), $scale ?? self::SCALE);
    }

    public function multiply(string|int|float $a, string|int|float $b, ?int $scale = null): string
    {
        $scale = $scale ?? self::SCALE;

        // Hitung pada skala lebih tinggi lalu bulatkan (half-away-from-zero)
        // agar konsisten dengan pembulatan kolom DECIMAL di MySQL, bukan
        // memotong (truncate) seperti bcmul pada skala target.
        return $this->round(bcmul($this->normalize($a), $this->normalize($b), $scale + 4), $scale);
    }

    public function divide(string|int|float $a, string|int|float $b, ?int $scale = null): string
    {
        if ($this->isZero($b, $scale)) {
            throw new InvalidArgumentException('Division by zero.');
        }

        $scale = $scale ?? self::SCALE;

        return $this->round(bcdiv($this->normalize($a), $this->normalize($b), $scale + 4), $scale);
    }

    /**
     * Membandingkan dua nilai desimal. Mengembalikan -1, 0, atau 1.
     */
    public function compare(string|int|float $a, string|int|float $b, ?int $scale = null): int
    {
        return bccomp($this->normalize($a), $this->normalize($b), $scale ?? self::SCALE);
    }

    public function isZero(string|int|float $a, ?int $scale = null): bool
    {
        return $this->compare($a, 0, $scale) === 0;
    }

    public function isPositive(string|int|float $a, ?int $scale = null): bool
    {
        return $this->compare($a, 0, $scale) > 0;
    }

    public function isNegative(string|int|float $a, ?int $scale = null): bool
    {
        return $this->compare($a, 0, $scale) < 0;
    }

    /**
     * Pembulatan half-away-from-zero ke skala tertentu (konsisten dengan
     * perilaku kolom DECIMAL di MySQL).
     */
    public function round(string|int|float $value, ?int $scale = null): string
    {
        $scale = $scale ?? self::SCALE;
        $value = $this->normalize($value);

        // Deteksi tanda dari representasi string (bukan isNegative yang
        // membandingkan pada skala target dan bisa menganggap nilai sub-skala
        // seperti -0.0005 sebagai nol).
        $negative = str_starts_with($value, '-');
        $absolute = $negative ? $this->subtract(0, $value, $scale + 4) : $value;

        $half = '0.'.str_repeat('0', $scale).'5';
        $withHalf = bcadd($absolute, $half, $scale + 4);

        return $negative
            ? bcsub('0', $this->truncate($withHalf, $scale), $scale)
            : $this->truncate($withHalf, $scale);
    }

    private function truncate(string $value, int $scale): string
    {
        $pos = strpos($value, '.');

        if ($pos === false) {
            return $value;
        }

        $int = substr($value, 0, $pos);

        if ($scale === 0) {
            return $int;
        }

        $frac = str_pad(substr($value, $pos + 1, $scale), $scale, '0');

        return $int.'.'.$frac;
    }

    private function normalize(string|int|float $value): string
    {
        if (is_float($value)) {
            // Buang noise floating-point (mis. 0.30000000000000004) dengan
            // memformat ke presisi tinggi tetap lalu memangkas nol di belakang.
            $value = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');
        }

        return (string) $value;
    }
}
