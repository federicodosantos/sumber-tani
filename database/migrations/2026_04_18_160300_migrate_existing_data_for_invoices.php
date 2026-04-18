<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Backfill inv_code for all existing invoices (purchasement type)
        $invoices = DB::table('invoices')
            ->whereNull('inv_code')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $dailyCounts = [];

        foreach ($invoices as $invoice) {
            $date = Carbon::parse($invoice->created_at);
            $dateKey = $date->format('dmy');

            if (!isset($dailyCounts[$dateKey])) {
                $dailyCounts[$dateKey] = 0;
            }
            $dailyCounts[$dateKey]++;

            $invCode = 'PRC' . $dateKey . '-' . str_pad($dailyCounts[$dateKey], 3, '0', STR_PAD_LEFT);

            DB::table('invoices')->where('id', $invoice->id)->update([
                'inv_code' => $invCode,
                'type' => 'purchasement',
            ]);
        }

        // Step 2: Migrate existing debt_payments to the new structure
        // Note: This migration runs AFTER the refactor migration which already
        // dropped invoice_id and added customer_id/payment_invoice_id.
        // The old debt_payments data is already gone at this point.
        // This migration only handles inv_code backfilling.
        // If you have existing debt_payment records that need migration,
        // they should be exported before running the refactor migration.
    }

    public function down(): void
    {
        // Remove backfilled inv_codes
        DB::table('invoices')->update(['inv_code' => null, 'type' => 'purchasement']);
    }
};
