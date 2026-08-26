<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'name',
        'currency_type',
        'general_to_master',
        'master_to_general',
        
        'general_to_secondary',
        'secondary_to_general',

        'currency',
        'prefix',
        'code',
        'order_id',
        'img',
        'inactive',
    ];

 

    protected $casts = [
        'currency_type' => 'integer',
        'general_to_master' => 'decimal:8',
        'master_to_general' => 'decimal:8',

        'general_to_secondary' => 'decimal:8',
        'secondary_to_general' => 'decimal:8',

        'inactive' => 'boolean',
    ];
}