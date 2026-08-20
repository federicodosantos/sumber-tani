<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah kolom uang di sisi penjualan (kasir) dari DECIMAL(15,2) ke DECIMAL(15,3)
     * agar presisi 3 desimal di sisi pembelian/stok tetap konsisten sampai ke transaksi
     * penjualan, COGS/HPP, dan laporan keuangan.
     *
     * Data lama aman: MySQL mengkonversi nilai ke presisi lebih tinggi tanpa kehilangan data.
     */
    public function up(): void
    {
        // 1. Header Transaksi Penjualan
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('total_price', 15, 3)->change();
            $table->decimal('discount', 15, 3)->default(0)->change();
            $table->decimal('cash_received', 15, 3)->nullable()->change();
            $table->decimal('change_amount', 15, 3)->nullable()->change();
        });

        // 2. Detail Transaksi Penjualan
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->decimal('product_price', 15, 3)->change();
            $table->decimal('total_price', 15, 3)->change();
            $table->decimal('buying_price', 15, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * sengaja no-op. Website sudah berjalan di production dengan banyak data;
     * menurunkan presisi ke 2 desimal akan membulatkan (merusak) data 3 desimal
     * yang sudah tersimpan. Rollback cukup dengan tidak mengaplikasikan migrasi ini.
     */
    public function down(): void
    {
        // no-op
    }
};
