<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('item_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('item_categories')->insert([
            ['name' => 'Pupuk', 'description' => 'Kategori Pupuk', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pestisida', 'description' => 'Kategori Pestisida', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Benih', 'description' => 'Kategori Benih', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alat Tani', 'description' => 'Kategori Alat Pertanian', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
