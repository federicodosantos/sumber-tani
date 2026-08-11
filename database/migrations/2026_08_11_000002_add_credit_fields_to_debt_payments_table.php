<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            // The portion of this payment that was overpaid and saved as credit balance.
            $table->decimal('credit_amount', 15, 2)->default(0)->after('amount');

            // The portion of this payment that was overpaid and refunded as cash.
            $table->decimal('refund_amount', 15, 2)->default(0)->after('credit_amount');

            // The amount of existing credit balance that was consumed in this payment.
            // Used to correctly reverse the customer's credit_balance on rollback.
            $table->decimal('credit_used', 15, 2)->default(0)->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropColumn(['credit_amount', 'refund_amount', 'credit_used']);
        });
    }
};
