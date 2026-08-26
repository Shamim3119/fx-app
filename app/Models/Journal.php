<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    use HasFactory;

    protected $table = 'journals';

    public $timestamps = false;

    protected $fillable = [
        'tan_date',
        'remarks',
        'type_id',

        'dr_account',
        'cr_account',

        'dr_sub_account',
        'cr_sub_account',

        'dr_amount',
        'cr_amount',

        'dr_balance',
        'cr_balance',

        'transaction_currency',

        'dr_master_rate',
        'cr_master_rate',

        'dr_master_amount',
        'cr_master_amount',

        'dr_master_balance',
        'cr_master_balance',

        'master_currency',
        'master_balance_profit',

        'dr_secondary_rate',
        'cr_secondary_rate',

        'dr_secondary_amount',
        'cr_secondary_amount',

        'dr_secondary_balance',
        'cr_secondary_balance',

        'secondary_currency',
        'secondary_balance_profit',
    ];



    /*
     * Dr Account
     */
    public function drAccount(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'dr_account'
        );
    }

    /*
     * Dr Account
     */
    public function crAccount(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'cr_account'
        );
    }


    /*
     * Transaction Currency
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'transaction_currency'
        );
    }

    /*
     * Master Currency
     */
    public function masterCurrency(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'master_currency'
        );
    }

    /*
     * Secondary Currency
     */
    public function secondaryCurrency(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'secondary_currency'
        );
    }


    /*
     * Dr Sub Account
     */
    public function drSubAccount(): BelongsTo
    {
        return $this->belongsTo(
            SubsidiaryAccount::class,
            'dr_sub_account'
        );
    }

    /*
     * Dr Account
     */
    public function crSubAccount(): BelongsTo
    {
        return $this->belongsTo(
            SubsidiaryAccount::class,
            'cr_sub_account'
        );
    }

    protected $casts = [
        'tan_date' => 'datetime',

        'dr_amount' => 'decimal:8',
        'cr_amount' => 'decimal:8',

        'dr_balance' => 'decimal:8',
        'cr_balance' => 'decimal:8',

        'dr_master_rate' => 'decimal:8',
        'cr_master_rate' => 'decimal:8',

        'dr_master_amount' => 'decimal:8',
        'cr_master_amount' => 'decimal:8',

        'dr_master_balance' => 'decimal:8',
        'cr_master_balance' => 'decimal:8',

        'master_balance_profit' => 'decimal:8',

        'dr_secondary_rate' => 'decimal:8',
        'cr_secondary_rate' => 'decimal:8',

        'dr_secondary_amount' => 'decimal:8',
        'cr_secondary_amount' => 'decimal:8',

        'dr_secondary_balance' => 'decimal:8',
        'cr_secondary_balance' => 'decimal:8',

        'secondary_balance_profit' => 'decimal:8',
    ];
}