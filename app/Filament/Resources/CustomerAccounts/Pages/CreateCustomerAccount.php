<?php

namespace App\Filament\Resources\CustomerAccounts\Pages;

use App\Filament\Resources\CustomerAccounts\CustomerAccountResource;
use App\Models\Account;
use App\Models\SubsidiaryAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerAccount extends CreateRecord
{
    protected static string $resource = CustomerAccountResource::class;
        protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        $customer = Account::query()
            ->where(
                'type_id',
                Account::TYPE_CUSTOMER
            )
            ->first();

        if (! $customer) {
            abort(
                500,
                'Customer account has not been configured.'
            );
        }


        /*
         * Automatically connect this
         * subsidiary account to Customer.
         */
        $data['account_id'] = $customer->id;


        /*
         * 3 = Customer Account
         */
        $data['type_id'] =
            SubsidiaryAccount::TYPE_CUSTOMER;


        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return CustomerAccountResource::getUrl('index');
    }
}
