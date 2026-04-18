<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Since we are resetting the debt tracking system and have truncated existing 
        // payments (only 2 records previously), we reconstruct the table to ensure 
        // consistency across environments where partial migrations might have occurred.
        Schema::dropIfExists('debt_payments');
        
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('payment_invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('Cash');
            $table->timestamp('payment_date')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
        
        // Restore original structure
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('payment_date');
            $table->timestamps();
        });
    }
};
