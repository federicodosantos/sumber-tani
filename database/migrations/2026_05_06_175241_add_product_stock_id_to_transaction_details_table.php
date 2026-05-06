<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->unsignedBigInteger('product_stock_id')->nullable()->after('product_id');
            $table->foreign('product_stock_id')
                ->references('id')->on('product_stocks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropForeign(['product_stock_id']);
            $table->dropColumn('product_stock_id');
        });
    }
};
