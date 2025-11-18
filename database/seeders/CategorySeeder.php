<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Makanan',
            'description' => 'Semua jenis hidangan makanan yang disajikan.',
        ]);

        Category::create([
            'name' => 'Minuman',
            'description' => 'Semua jenis minuman, kopi, dan non-kopi.',
        ]);
    }
}
