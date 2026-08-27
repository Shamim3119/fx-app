<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRate extends Model
{
    protected $fillable = [
        'customer_id',
        'currency_id',

        'general_to_master',
        'master_to_general',

        'general_to_secondary',
        'secondary_to_general',
    ];

    protected $casts = [
        'general_to_master' => 'decimal:8',
        'master_to_general' => 'decimal:8',

        'general_to_secondary' => 'decimal:8',
        'secondary_to_general' => 'decimal:8',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'customer_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'currency_id'
        );
    }
}