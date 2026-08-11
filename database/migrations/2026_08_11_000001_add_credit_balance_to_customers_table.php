<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Stores the overpayment credit available for this customer.
            // Incremented when a debt payment results in an overpayment the user
            // chose to save, and decremented when the credit is used or refunded.
            $table->decimal('credit_balance', 15, 2)->default(0)->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('credit_balance');
        });
    }
};
