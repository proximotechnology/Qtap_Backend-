<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class meals_categories extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'image',
        'cover',
        'brunch_id',


    ];
}
