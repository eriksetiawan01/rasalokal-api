<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'customer_name',
        'table_number',
        'order_type',
        'total_amount',
        'status',
        'note',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasOne(Payment::class);
    }
}
