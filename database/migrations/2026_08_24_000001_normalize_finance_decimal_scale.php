<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menyelaraskan skala kolom ledger finance (invoice, pembayaran hutang,
     * detail alokasi, saldo kredit, dan harga khusus pelanggan) menjadi
     * DECIMAL(15,3) agar konsisten dengan harga/quantity pembelian dan
     * penjualan yang sudah mendukung 3 desimal.
     *
     * Data lama aman: MySQL/SQLite mengkonversi 100.25 -> 100.250.
     */
    public function up(): void
    {
        // 1. Invoice piutang
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('debts', 15, 3)->default(0)->change();
        });

        // 2. Pembayaran hutang
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->decimal('amount', 15, 3)->change();
            $table->decimal('credit_amount', 15, 3)->default(0)->change();
            $table->decimal('refund_amount', 15, 3)->default(0)->change();
            $table->decimal('credit_used', 15, 3)->default(0)->change();
        });

        // 3. Detail alokasi pembayaran (FIFO)
        Schema::table('debt_payment_details', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 3)->change();
            $table->decimal('debt_before', 15, 3)->change();
            $table->decimal('debt_after', 15, 3)->change();
        });

        // 4. Saldo kredit pelanggan
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('credit_balance', 15, 3)->default(0)->change();
        });

        // 5. Harga khusus pelanggan
        Schema::table('customer_product_prices', function (Blueprint $table) {
            $table->decimal('custom_price', 15, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * sengaja no-op. Menurunkan presisi ke 2 desimal akan membulatkan
     * (merusak) data 3 desimal yang sudah tersimpan. Rollback cukup dengan
     * tidak mengaplikasikan migrasi ini. Mengikuti preseden
     * change_sales_money_to_decimal_3.
     */
    public function down(): void
    {
        // no-op
    }
};
