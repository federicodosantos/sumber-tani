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
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->decimal('ppn_percent', 6, 3)->change();
            $table->decimal('discount_percent', 6, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->decimal('ppn_percent', 5, 2)->change();
            $table->decimal('discount_percent', 5, 2)->change();
        });
    }
};
