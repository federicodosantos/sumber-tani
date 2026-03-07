<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan product_id ke detail pembelian
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->foreignId('product_id')
                  ->nullable()
                  ->after('product_purchase_id')
                  ->constrained('products')
                  ->nullOnDelete();
        });

        // Tambahkan discount_type ke pembelian (persen / nominal)
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'nominal'])
                  ->default('percent')
                  ->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('product_purchases', function (Blueprint $table) {
            $table->dropColumn('discount_type');
        });
    }
};
