<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ DROP FOREIGN KEY JIKA ADA
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropForeign('product_purchase_details_product_id_foreign');
        });

        // 2️⃣ DROP COLUMN
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained('products')
                  ->nullOnDelete()
                  ->after('product_purchase_id');
        });
    }
};
