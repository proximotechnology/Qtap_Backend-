<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class orders extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "phone",
        "comments",
        "city",
        "address",
        "latitude",
        "longitude",
        "table_id",
        "type",
        "status",
        "discount_code",
        "tax",
        "reference_number",
        "total_price",
        "meal_id",
        "variants",
        "extras",
        "size_id",
        "quantity",
        "payment_way",
        "size",
        "brunch_id"
    ];


    public function orders_processing(){
        return $this->hasMany(orders_processing::class , 'order_id');
    }

    public function meal(){
        return $this->belongsTo(meals::class , 'meal_id' , 'id');
    }
}
