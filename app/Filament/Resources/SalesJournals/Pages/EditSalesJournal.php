<?php

namespace App\Filament\Resources\SalesJournals\Pages;

use App\Filament\Resources\SalesJournals\SalesJournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalesJournal extends EditRecord
{
    protected static string $resource = SalesJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
