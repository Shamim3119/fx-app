<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
        protected $fillable = [
        'account_id',
        'balance',
        'currency_id',
 
    ];
}
