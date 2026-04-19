<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->decimal('het_price', 15, 2)->after('unit')->default(0);
            $table->decimal('basic_discount', 15, 2)->after('het_price')->default(0);
            $table->decimal('additional_discount', 15, 2)->after('basic_discount')->default(0);
            $table->decimal('net_price', 15, 2)->after('additional_discount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropColumn(['het_price', 'basic_discount', 'additional_discount', 'net_price']);
        });
    }
};
