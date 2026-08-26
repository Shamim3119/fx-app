<?php

namespace App\Filament\Resources\CustomerAccounts\Pages;

use App\Filament\Resources\CustomerAccounts\CustomerAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerAccount extends EditRecord
{
    protected static string $resource = CustomerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
