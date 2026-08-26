<?php

namespace App\Filament\Resources\CompanyAccounts\Pages;

use App\Filament\Resources\CompanyAccounts\CompanyAccountResource;
use App\Models\Account;
use App\Models\SubsidiaryAccount;

use Filament\Resources\Pages\EditRecord;

class EditCompanyAccount extends EditRecord
{
    protected static string $resource =
        CompanyAccountResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {

        $company = Account::query()
            ->where(
                'type_id',
                Account::TYPE_COMPANY
            )
            ->firstOrFail();

        $data['account_id'] =
            $company->id;

        $data['type_id'] =
            SubsidiaryAccount::TYPE_COMPANY;

        return $data;
    }
}