<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class restaurant_staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_id',
        'brunch_id',
    ];
}
