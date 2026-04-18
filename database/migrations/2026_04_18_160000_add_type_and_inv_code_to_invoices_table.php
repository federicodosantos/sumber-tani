<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type')->default('purchasement')->after('id');
            $table->string('inv_code')->nullable()->after('type');

            // Make transaction_id nullable for debt_payment invoices
            $table->unsignedBigInteger('transaction_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['type', 'inv_code']);
            $table->unsignedBigInteger('transaction_id')->nullable(false)->change();
        });
    }
};
