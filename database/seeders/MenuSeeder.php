<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::create([
            'name' => 'Nasi Goreng Spesial',
            'description' => 'Nasi goreng dengan topping ayam, telur, dan kerupuk.',
            'price' => 25000,
            'stock' => 50,
            'photo' => 'nasi-goreng.jpg',
            'category_id' => 1, 
        ]);

        Menu::create([
            'name' => 'Es Teh Manis',
            'description' => 'Teh manis segar dengan es batu.',
            'price' => 8000,
            'stock' => 100,
            'photo' => 'es-teh.jpg',
            'category_id' => 2,
        ]);

        Menu::create([
            'name' => 'Ayam Geprek',
            'description' => 'Ayam goreng crispy dengan sambal bawang.',
            'price' => 22000,
            'stock' => 40,
            'photo' => 'ayam-geprek.jpg',
            'category_id' => 1,
        ]);
    }
}
