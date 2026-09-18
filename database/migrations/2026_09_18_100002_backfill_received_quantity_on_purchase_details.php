<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Semua nota yang sudah ada dibuat dengan perilaku lama: simpan nota = stok
 * langsung bertambah. Jadi seluruh baris lama sudah diterima penuh.
 *
 * Sengaja TIDAK mengarang baris product_purchase_receipts untuk data lama —
 * tanggal kedatangan dan batch asalnya tidak pernah tercatat, dan memalsukan
 * keduanya lebih berbahaya daripada mengakui datanya tidak ada. UI menampilkan
 * baris semacam ini sebagai "diterima sebelum fitur penerimaan ada".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_purchase_details')->update([
            'received_quantity' => DB::raw('quantity'),
            'receipt_mode' => 'direct',
        ]);
    }

    public function down(): void
    {
        DB::table('product_purchase_details')->update([
            'received_quantity' => 0,
        ]);
    }
};
