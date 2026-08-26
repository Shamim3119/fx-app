<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Account;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource =
        VendorResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        $data['type_id'] =
            Account::TYPE_VENDOR;

        return $data;
    }
}