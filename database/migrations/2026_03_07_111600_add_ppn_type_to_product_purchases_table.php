<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->enum('ppn_type', ['percent', 'nominal'])->default('percent')->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->dropColumn('ppn_type');
        });
    }
};
