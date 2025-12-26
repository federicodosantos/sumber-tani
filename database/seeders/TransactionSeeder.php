<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('transaction_details')->truncate();
        DB::table('transactions')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $products = DB::table('products')->pluck('id');

        if ($products->isEmpty()) {
            $this->command->warn('Products table is empty!');
            return;
        }

        for ($i = 0; $i < 10000; $i++) {

            $createdAt = Carbon::now()
                ->subDays(rand(0, 365))
                ->setTime(rand(8, 22), rand(0, 59));

            // Insert transaction
            $transactionId = DB::table('transactions')->insertGetId([
                'offline_uuid' => uniqid('trx_'),
                'total_quantity' => 0,
                'discount' => rand(0, 10000),
                'payment_method' => collect(['Cash', 'Kredit', 'QRIS', 'Transfer'])->random(),
                'is_paid' => 1,
                'total_price' => 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $totalQty   = 0;
            $totalPrice = 0;

            $itemsCount = rand(1, 5);

            for ($j = 0; $j < $itemsCount; $j++) {
                $qty   = rand(1, 5);
                $price = rand(5_000, 50_000);
                $sub   = $qty * $price;

                DB::table('transaction_details')->insert([
                    'transaction_id' => $transactionId,
                    'product_id' => $products->random(),
                    'product_price' => $price,
                    'quantity' => $qty,
                    'total_price' => $sub,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $totalQty   += $qty;
                $totalPrice += $sub;
            }

            DB::table('transactions')
                ->where('id', $transactionId)
                ->update([
                    'total_quantity' => $totalQty,
                    'total_price' => max(0, $totalPrice - rand(0, 5000)),
                ]);
        }
    }
}
