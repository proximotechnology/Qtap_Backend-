<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'img',
        'Brief',
        'Description',
        'Ingredients',
        'Calories',
        'Time',
        'Tax',
        'price',
        'discount_id',
        'categories_id',
        'brunch_id',
    ];
}
