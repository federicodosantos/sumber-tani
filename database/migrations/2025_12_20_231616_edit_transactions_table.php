<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('discount')->after('total_quantity')->default(0);

            $table
                ->enum('payment_method', ['Cash', 'Kredit', 'QRIS', 'Transfer'])
                ->default('Cash')
                ->after('discount');

            $table->boolean('is_paid')->default(true)->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('payment_method');
            $table->dropColumn('is_paid');
        });
    }
};
