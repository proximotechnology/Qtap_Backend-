<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPricing extends Model
{
    use HasFactory;

        protected $fillable = ['client_id' , 'pricing_id' ,'status', 'image', 'ramin_order','expired_at','payment_methodes','pricing_way'];

    // app/Models/Campaign.php
    public function pricing()
    {
            return $this->belongsTo(pricing::class , 'pricing_id' , 'id');
    }

    public function client(){

        return $this->belongsTo(qtap_clients::class , 'client_id' , 'id');
    }


    public function SubscriptionChangeRequest()
    {
            return $this->hasMany(SubscriptionChangeRequest::class);
    }
}
