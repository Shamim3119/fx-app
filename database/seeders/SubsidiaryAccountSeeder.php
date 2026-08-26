<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\SubsidiaryAccount;
use Illuminate\Database\Seeder;


class SubsidiaryAccountSeeder extends Seeder
{
    public function run(): void
    {
        SubsidiaryAccount::updateOrCreate(
            [
                'account_type' => SubsidiaryAccount::ACCOUNT_TYPE_CASH,
        
                'type_id' => Account::TYPE_COMPANY,
        
                'name' => 'Company Cash',

                'account_id' => 1,
            ]
        );
    }
}