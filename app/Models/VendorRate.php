<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorRate extends Model
{
    protected $fillable = [
        'vendor_id',
        'general_to_master',
        'master_to_general',
        
        'general_to_secondary',
        'secondary_to_general',         
    ];
}
