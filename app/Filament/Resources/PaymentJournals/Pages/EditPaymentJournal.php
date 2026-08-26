<?php

namespace App\Filament\Resources\PaymentJournals\Pages;

use App\Filament\Resources\PaymentJournals\PaymentJournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentJournal extends EditRecord
{
    protected static string $resource = PaymentJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
