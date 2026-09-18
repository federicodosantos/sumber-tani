<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelacakan penerimaan barang per baris item nota pembelian.
 *
 * received_quantity adalah satu-satunya sumber kebenaran untuk "sudah masuk
 * stok berapa". Status (Belum Datang / Sebagian / Diterima / Ditutup) sengaja
 * TIDAK disimpan — dihitung di accessor dari received_quantity vs quantity
 * vs closed_at, supaya tidak ada dua angka yang bisa saling melenceng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->decimal('received_quantity', 10, 3)->default(0)->after('quantity');

            // Niat saat nota diinput ('direct' | 'pending'). Dipakai untuk
            // tampilan/audit saja, bukan untuk menghitung stok. String, bukan
            // enum, supaya tidak butuh doctrine/dbal kalau nilainya bertambah.
            $table->string('receipt_mode', 20)->default('direct')->after('received_quantity');

            $table->timestamp('closed_at')->nullable()->after('receipt_mode');
            $table->string('closed_reason', 255)->nullable()->after('closed_at');

            // Halaman Penerimaan Barang memfilter baris yang belum ditutup.
            $table->index('closed_at', 'ppd_closed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('product_purchase_details', function (Blueprint $table) {
            $table->dropIndex('ppd_closed_at_index');
            $table->dropColumn(['received_quantity', 'receipt_mode', 'closed_at', 'closed_reason']);
        });
    }
};
