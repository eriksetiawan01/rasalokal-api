<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'photo',
        'category_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

