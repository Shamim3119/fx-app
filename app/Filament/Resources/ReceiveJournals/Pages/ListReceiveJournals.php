<?php

namespace App\Filament\Resources\ReceiveJournals\Pages;

use App\Filament\Resources\ReceiveJournals\ReceiveJournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceiveJournals extends ListRecords
{
    protected static string $resource = ReceiveJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
