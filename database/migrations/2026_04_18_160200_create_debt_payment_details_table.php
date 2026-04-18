<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_payment_id')->constrained('debt_payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('debt_before', 15, 2);
            $table->decimal('debt_after', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payment_details');
    }
};
