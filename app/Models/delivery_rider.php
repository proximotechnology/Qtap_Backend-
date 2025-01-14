<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class delivery_rider extends Model
{
    use HasFactory;
    protected $fillable = ['brunch_id' , 'delivery_areas_id' , 'name' , 'phone' , 'pin' , 'orders' , 'status'];
}
