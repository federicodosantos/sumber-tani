<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->date('expired_date')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropColumn('expired_date');
        });
    }
};
