<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class pricing extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['name' , 'description', 'specific_number', 'feature', 'monthly_price', 'yearly_price', 'is_active'] ;

}
