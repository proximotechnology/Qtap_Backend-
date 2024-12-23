<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class setting_payment extends Model
{
    use HasFactory, SoftDeletes;
   protected $fillable = ['API_KEY' , 'Token1' , 'Token2' , 'Ifram'];
}
