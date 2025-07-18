<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionChangeRequest extends Model
{
    use HasFactory;
        protected $fillable = [
        'client_pricing_id',
        'action_type',
        'new_pricing_id',
        'requested_at',
        'status',
        'payment_methodes',
        'pricing_way',
        'status',

    ];


    public function ClientPricing()
    {
        return $this->belongsTo(ClientPricing::class , 'client_pricing_id');

    }

    public function Pricing()
    {
        return $this->belongsTo(pricing::class , 'new_pricing_id');

    }

}
