<?php

namespace App\Filament\Resources\CompanyAccounts\Pages;


use App\Filament\Resources\CompanyAccounts\CompanyAccountResource;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyAccounts extends ListRecords
{
    protected static string $resource =
        CompanyAccountResource::class;


    protected function getHeaderActions(): array
    {
        return [

        //    CreateAction::make()
        //        ->label('Add Company Account')
        //        ->icon('heroicon-o-plus'),

        ];
    }
}