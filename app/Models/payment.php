<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class payment extends Model
{
    use HasFactory;

    protected $fillable = ['brunch_id' , 'API_KEY' , 'Token1' , 'Token2' , 'Ifram'];

}
