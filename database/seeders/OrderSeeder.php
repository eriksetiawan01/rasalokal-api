<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Order 1
        $order1 = Order::create([
            'order_number'   => 'ORD-20250101-0001',
            'customer_name'  => 'Budi',
            'customer_phone' => '08123456789',
            'table_number'   => 'A1',
            'order_type'     => 'dine_in',
            'total_amount'   => 35000,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'note'           => 'Pedas dikit'
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'menu_id'  => 1, // pastikan menu id ada
            'quantity' => 1,
            'price'    => 25000,
            'subtotal' => 25000,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'menu_id'  => 2,
            'quantity' => 1,
            'price'    => 10000,
            'subtotal' => 10000,
        ]);

        // Order 2
        $order2 = Order::create([
            'order_number'   => 'ORD-20250101-0002',
            'customer_name'  => 'Siti',
            'customer_phone' => '08987654321',
            'table_number'   => null,
            'order_type'     => 'take_away',
            'total_amount'   => 22000,
            'status'         => 'processing',
            'payment_status' => 'paid',
            'note'           => null
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'menu_id'  => 3,
            'quantity' => 1,
            'price'    => 22000,
            'subtotal' => 22000,
        ]);
    }
}
