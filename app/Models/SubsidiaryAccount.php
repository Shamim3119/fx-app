<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubsidiaryAccount extends Model
{
    protected $fillable = [
        'account_id',
        'name',
        'account_type',
        'type_id',        
    ];


    /*
     * Account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'account_id'
        );
    }


    /*
     * Account Types
     */
    public const TYPE_COMPANY = 1;

    public const TYPE_VENDOR = 2;

    public const TYPE_CUSTOMER = 3;


    /*
     * Account Type: Cash / Bank
     */
    public const ACCOUNT_TYPE_CASH = 1;

    public const ACCOUNT_TYPE_BANK = 2;


    public function getAccountTypeNameAttribute(): string
    {
        return match ((int) $this->account_type) {

            self::ACCOUNT_TYPE_CASH => 'Cash',

            self::ACCOUNT_TYPE_BANK => 'Bank',

            default => 'Unknown',

        };
    }


    public function getTypeNameAttribute(): string
    {
        return match ((int) $this->type_id) {

            self::TYPE_COMPANY => 'Company Account',

            self::TYPE_VENDOR => 'Vendor Account',

            self::TYPE_CUSTOMER => 'Customer Account',

            default => 'Unknown',

        };
    }
}