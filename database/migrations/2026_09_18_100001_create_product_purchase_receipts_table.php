<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris = satu kedatangan barang untuk satu baris item nota.
 *
 * Satu penerimaan melahirkan tepat satu batch stok. product_stock_id itulah
 * yang membuat pembatalan aman: kita tahu persis batch mana yang harus
 * diperiksa (masih utuh atau sudah terpakai kasir) sebelum dibalikkan.
 *
 * expired_date disimpan per penerimaan karena tiap kiriman bisa punya
 * kadaluarsa berbeda walau berasal dari baris nota yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchase_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_purchase_detail_id')
                ->constrained('product_purchase_details')
                ->cascadeOnDelete();

            // Batch yang dilahirkan penerimaan ini. Nullable supaya baris
            // penerimaan tidak ikut hilang kalau batch dihapus permanen.
            $table->foreignId('product_stock_id')
                ->nullable()
                ->constrained('product_stocks')
                ->nullOnDelete();

            $table->decimal('quantity', 10, 3);

            // Snapshot harga beli saat barang diterima.
            $table->decimal('unit_price', 15, 3)->default(0);

            $table->date('received_date');
            $table->date('expired_date')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['product_purchase_detail_id', 'received_date'], 'ppr_detail_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchase_receipts');
    }
};
