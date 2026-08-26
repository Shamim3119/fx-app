<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;


class AccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::updateOrCreate(
            [
                'type_id' => Account::TYPE_COMPANY,
            ],
            [
                'name' => 'Defaulter Company',

                'address' => null,

                'phone' => null,

                'email' => null,

                'website' => null,

                'logo' => null,
            ]
        );
    }
}