<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Account;
use Filament\Resources\Pages\CreateRecord;
use App\Models\SubsidiaryAccount;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {

        $data['type_id'] =
            Account::TYPE_CUSTOMER;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Get the newly created customer Account
        $customer = $this->record;

        // Automatically create the customer's subsidiary account
        SubsidiaryAccount::create([
            'account_id'   => $customer->id,
            'name'         => $customer->name,
            'account_type' => SubsidiaryAccount::ACCOUNT_TYPE_CASH,
            'type_id'      => SubsidiaryAccount::TYPE_CUSTOMER,
        ]);
    }
}

 