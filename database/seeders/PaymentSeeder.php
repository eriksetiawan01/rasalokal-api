<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payment::create([
            'order_id'        => 2,
            'user_id'         => 2,
            'kode_pembayaran' => 'PMT-TEST-0001',
            'payment_method'  => 'cash',
            'total_amount'    => 22000,
            'amount_paid'     => 25000,
            'change_amount'   => 3000,
            'status'          => 'success'
        ]);
    }
}
