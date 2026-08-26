<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Account;

use Filament\Resources\Pages\EditRecord;


class ManageCompany extends EditRecord
{
    protected static string $resource =
        CompanyResource::class;


    public function mount(int|string $record = null): void
    {
        $company = Account::query()
            ->where('type_id', Account::TYPE_COMPANY)
            ->first();


        if (! $company) {

            abort(404, 'Company account not found.');

        }


        parent::mount($company->getKey());
    }


    protected function getHeaderActions(): array
    {
        return [];
    }


    public function getTitle(): string
    {
        return 'Company';
    }
}