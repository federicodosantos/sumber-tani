<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah kolom harga, diskon, ppn, dan subtotal dari DECIMAL(15,2) ke DECIMAL(15,3)
     * agar mendukung presisi 3 digit desimal tanpa pembulatan.
     */
    public function up(): void
    {
        // 1. Header Pembelian Produk
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 3)->change();
            $table->decimal('discount_value', 15, 3)->default(0)->change();
            $table->decimal('ppn_value', 15, 3)->default(0)->change();
            $table->decimal('grand_total', 15, 3)->change();
        });

        // 2. Detail Pembelian Produk
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->decimal('het_price', 15, 3)->default(0)->change();
            $table->decimal('basic_discount', 15, 3)->default(0)->change();
            $table->decimal('additional_discount', 15, 3)->default(0)->change();
            $table->decimal('net_price', 15, 3)->default(0)->change();
            $table->decimal('price', 15, 3)->change();
            $table->decimal('subtotal', 15, 3)->change();
        });

        // 3. Stok Produk (Batch harga beli & jual)
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 3)->default(0)->change();
            $table->decimal('price_consument', 15, 3)->default(0)->change();
            $table->decimal('price_r1', 15, 3)->default(0)->change();
            $table->decimal('price_r2', 15, 3)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->change();
            $table->decimal('discount_value', 15, 2)->default(0)->change();
            $table->decimal('ppn_value', 15, 2)->default(0)->change();
            $table->decimal('grand_total', 15, 2)->change();
        });

        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->decimal('het_price', 15, 2)->default(0)->change();
            $table->decimal('basic_discount', 15, 2)->default(0)->change();
            $table->decimal('additional_discount', 15, 2)->default(0)->change();
            $table->decimal('net_price', 15, 2)->default(0)->change();
            $table->decimal('price', 15, 2)->change();
            $table->decimal('subtotal', 15, 2)->change();
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->default(0)->change();
            $table->decimal('price_consument', 15, 2)->default(0)->change();
            $table->decimal('price_r1', 15, 2)->default(0)->change();
            $table->decimal('price_r2', 15, 2)->default(0)->change();
        });
    }
};
