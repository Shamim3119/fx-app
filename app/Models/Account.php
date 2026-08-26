<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'type_id',
        'name',
        'address',
        'phone',
        'email',
        'website',
        'logo',
    ];


    /*
     * Account Types
     */
    public const TYPE_COMPANY = 1;

    public const TYPE_VENDOR = 2;

    public const TYPE_CUSTOMER = 3;


    /*
     * Get readable account type
     */
    public function getTypeNameAttribute(): string
    {
        return match ((int) $this->type_id) {

            self::TYPE_COMPANY => 'Company',

            self::TYPE_VENDOR => 'Vendor',

            self::TYPE_CUSTOMER => 'Customer',

            default => 'Unknown',

        };
    }

    public function subsidiaryAccounts(): HasMany
    {
        return $this->hasMany(
            SubsidiaryAccount::class,
            'account_id'
        );
    }
}