<?php

namespace App\Filament\Resources\CustomerAccounts\Pages;

use App\Filament\Resources\CustomerAccounts\CustomerAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerAccounts extends ListRecords
{
    protected static string $resource = CustomerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
