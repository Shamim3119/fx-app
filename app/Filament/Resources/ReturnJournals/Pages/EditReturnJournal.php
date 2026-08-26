<?php

namespace App\Filament\Resources\ReturnJournals\Pages;

use App\Filament\Resources\ReturnJournals\ReturnJournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReturnJournal extends EditRecord
{
    protected static string $resource = ReturnJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
