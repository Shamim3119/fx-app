<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRate extends Model
{
    protected $fillable = [
        'type_id',
        'currency_id',
        'general_to_master',
        'master_to_general',
        'general_to_secondary',
        'secondary_to_general',
    ];

    protected $casts = [
        'type_id' => 'integer',

        'general_to_master' => 'decimal:8',
        'master_to_general' => 'decimal:8',

        'general_to_secondary' => 'decimal:8',
        'secondary_to_general' => 'decimal:8',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'currency_id'
        );
    }
}