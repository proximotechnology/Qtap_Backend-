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
        'status',
        'order_id',
        'affiliate_code',
    ];

    public function client(){

        return $this->belongsTo(qtap_clients::class , 'client_id' , 'id');
    }


    public function role(){

        return $this->hasMany(role::class ,  'brunch_id', 'id')->select('id' , 'name' , 'brunch_id');
    }

    public function pricing(){
        return $this->belongsTo(pricing::class , 'pricing_id' , 'id');
    }


    public function workschedule(){

        return $this->hasMany(workschedule::class, 'brunch_id');
    }
    public function contact_info(){

        return $this->hasMany(contact_info::class, 'brunch_id');
    }
    public function serving_ways(){

        return $this->hasMany(serving_ways::class, 'brunch_id');
    }
    public function payment_services(){

        return $this->hasMany(payment_services::class, 'brunch_id');
    }

    public function revenue(){

        return $this->hasMany(Revenue::class , 'client_id' , 'client_id');
    }

}
