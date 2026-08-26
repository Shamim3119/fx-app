<?php

namespace App\Filament\Resources\SalesJournals\Pages;

use App\Filament\Resources\SalesJournals\SalesJournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalesJournals extends ListRecords
{
    protected static string $resource = SalesJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
