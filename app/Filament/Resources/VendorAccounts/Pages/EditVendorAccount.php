<?php

namespace App\Filament\Resources\VendorAccounts\Pages;

use App\Filament\Resources\VendorAccounts\VendorAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorAccount extends EditRecord
{
    protected static string $resource = VendorAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
