<?php

namespace App\Filament\Resources\VendorAccounts\Pages;

use App\Filament\Resources\VendorAccounts\VendorAccountResource;
use App\Models\Account;
use App\Models\SubsidiaryAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateVendorAccount extends CreateRecord
{
    protected static string $resource = VendorAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $vendor = Account::query()
            ->where(
                'type_id',
                Account::TYPE_VENDOR
            )
            ->first();

        if (!$vendor) {
            abort(
                500,
                'Vendor account has not been configured.'
            );
        }

        $data['account_id'] = $vendor->id;

        $data['type_id'] = SubsidiaryAccount::TYPE_VENDOR;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VendorAccountResource::getUrl('index');
    }
}