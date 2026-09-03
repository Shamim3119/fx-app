<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Account;
use App\Models\SubsidiaryAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set Account type = Vendor
        $data['type_id'] = Account::TYPE_VENDOR;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Newly created Vendor Account
        $vendor = $this->record;

        // Create the default Vendor Subsidiary Account
        SubsidiaryAccount::firstOrCreate(
            [
                'account_id' => $vendor->id,
                'type_id'    => SubsidiaryAccount::TYPE_VENDOR,
            ],
            [
                'name'         => $vendor->name,
                'account_type' => SubsidiaryAccount::ACCOUNT_TYPE_CASH,
            ]
        );
    }
}

