<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('transaction_date')->nullable()->after('is_manual');
        });

        DB::statement('UPDATE transactions SET transaction_date = created_at WHERE transaction_date IS NULL');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('transaction_date')->nullable(false)->change();
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_date']);
            $table->dropColumn('transaction_date');
        });
    }
};
