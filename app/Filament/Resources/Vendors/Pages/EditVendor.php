<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Account;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    protected static string $resource =
        VendorResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {

        $data['type_id'] =
            Account::TYPE_VENDOR;

        return $data;
    }
}