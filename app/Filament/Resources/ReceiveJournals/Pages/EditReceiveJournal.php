<?php

namespace App\Filament\Resources\ReceiveJournals\Pages;

use App\Filament\Resources\ReceiveJournals\ReceiveJournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceiveJournal extends EditRecord
{
    protected static string $resource = ReceiveJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
