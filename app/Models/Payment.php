<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method',
        'amount_paid',
        'change_amount',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
