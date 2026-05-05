<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = DB::table('item_categories')->pluck('id');

        if ($categories->isEmpty()) {
            return;
        }

        $products = [
            ['name' => 'Pupuk Urea 50kg', 'cat_index' => 0],
            ['name' => 'Pupuk NPK Mutiara 1kg', 'cat_index' => 0],
            ['name' => 'Roundup 1L', 'cat_index' => 1],
            ['name' => 'Gramoxone 500ml', 'cat_index' => 1],
            ['name' => 'Benih Jagung Pertiwi', 'cat_index' => 2],
            ['name' => 'Benih Padi Ciherang', 'cat_index' => 2],
            ['name' => 'Cangkul Baja', 'cat_index' => 3],
            ['name' => 'Sprayer Elektrik 16L', 'cat_index' => 3],
        ];

        foreach ($products as $p) {
            DB::table('products')->insert([
                'code_id' => strtoupper(Str::random(8)),
                'name' => $p['name'],
                'description' => 'Deskripsi untuk ' . $p['name'],
                'item_category_id' => $categories[$p['cat_index']],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
