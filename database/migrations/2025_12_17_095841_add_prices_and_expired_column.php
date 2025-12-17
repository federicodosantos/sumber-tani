<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('price_consument', 15, 2)->change();
            $table->decimal('price_r1', 15, 2)->default(0)->after('price_consument');
            $table->decimal('price_r2', 15, 2)->default(0)->after('price_r1');
            $table->unsignedInteger('batch')->after('price_r2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropColumn(['price_r1', 'price_r2', 'batch']);
        });
    }
};
