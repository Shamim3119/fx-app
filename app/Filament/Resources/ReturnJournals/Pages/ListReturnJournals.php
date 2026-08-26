<?php

namespace App\Filament\Resources\ReturnJournals\Pages;

use App\Filament\Resources\ReturnJournals\ReturnJournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReturnJournals extends ListRecords
{
    protected static string $resource = ReturnJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
