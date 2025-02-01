<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class qtap_clients_brunchs extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'contact_info_id',
        'currency_id',
        'pricing_id',
        'discount_id',
        'business_name',
        'business_country',
        'business_city',
        'latitude',
        'longitude',
        'business_format',
        'payment_method',
        'menu_design',
        'default_mode',
        'payment_time',
        'call_waiter',
    ];


    public function pricing(){
        return $this->belongsTo(pricing::class , 'pricing_id' , 'id');
    }

}
