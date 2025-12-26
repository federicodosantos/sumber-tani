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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('is_paid');
        });

        // transaction_details
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('product_id');
        });

        // products
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
            $table->index('item_category_id');
        });

        // item_categories
        Schema::table('item_categories', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['is_paid']);
        });

        // transaction_details
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['product_id']);
        });

        // products
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['item_category_id']);
        });

        // item_categories
        Schema::table('item_categories', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
