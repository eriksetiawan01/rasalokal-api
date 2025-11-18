<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
        'name' => 'Admin',
        'email' =>'admin@example.com',
        'password' => bcrypt('admin123'),
        'role' => 'admin'
       ]);

       User::create([
        'name' => 'Kasir',
        'email' =>'kasir@example.com',
        'password' => bcrypt('kasir123'),
        'role' => 'kasir'
       ]);
    }
}
