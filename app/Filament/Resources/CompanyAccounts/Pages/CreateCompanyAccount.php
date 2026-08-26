<?php

namespace App\Filament\Resources\CompanyAccounts\Pages;

use App\Filament\Resources\CompanyAccounts\CompanyAccountResource;
use App\Models\Account;
use App\Models\SubsidiaryAccount;

use Filament\Resources\Pages\CreateRecord;

class CreateCompanyAccount extends CreateRecord
{
    protected static string $resource =  CompanyAccountResource::class;


    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        $company = Account::query()
            ->where(
                'type_id',
                Account::TYPE_COMPANY
            )
            ->first();

        if (! $company) {
            abort(
                500,
                'Company account has not been configured.'
            );
        }


        /*
         * Automatically connect this
         * subsidiary account to Company.
         */
        $data['account_id'] = $company->id;


        /*
         * 1 = Company Account
         */
        $data['type_id'] =
            SubsidiaryAccount::TYPE_COMPANY;


        return $data;
    }
}