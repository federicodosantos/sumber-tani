<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->date('purchase_date')
                  ->nullable()
                  ->after('id');
        });

        DB::table('product_purchases')
            ->whereNull('purchase_date')
            ->update(['purchase_date' => now()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('product_purchases', function (Blueprint $table) {
            $table->dropColumn('purchase_date');
        });
    }
};
