<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsidiaryAccountBalance extends Model
{

    protected $fillable = [
        'subsidiary_account_id',
        'balance',
        'currency_id',
 
    ];

}
