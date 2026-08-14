<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah semua kolom quantity dari integer ke DECIMAL(10,3)
     * agar mendukung input angka desimal/koma di seluruh modul.
     *
     * Data lama aman: MySQL mengkonversi 10 → 10.000 secara otomatis.
     */
    public function up(): void
    {
        // 1. Jumlah barang per baris pembelian
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->change();
        });

        // 2. Total item di header pembelian
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->decimal('total_items', 10, 3)->change();
        });

        // 3. Stok fisik di gudang
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('stock_opname', 10, 3)->change();
        });

        // 4. Jumlah barang per baris transaksi kasir
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->decimal('quantity', 10, 3)->change();
        });

        // 5. Total item di header transaksi kasir
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('total_quantity', 10, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('product_purchases', function (Blueprint $table) {
            $table->unsignedInteger('total_items')->change();
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->integer('stock_opname')->change();
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('total_quantity')->change();
        });
    }
};
