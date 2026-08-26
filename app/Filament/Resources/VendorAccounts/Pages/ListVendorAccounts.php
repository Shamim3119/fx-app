<?php

namespace App\Filament\Resources\VendorAccounts\Pages;

use App\Filament\Resources\VendorAccounts\VendorAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorAccounts extends ListRecords
{
    protected static string $resource = VendorAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
