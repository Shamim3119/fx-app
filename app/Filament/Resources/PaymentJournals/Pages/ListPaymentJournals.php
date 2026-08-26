<?php

namespace App\Filament\Resources\PaymentJournals\Pages;

use App\Filament\Resources\PaymentJournals\PaymentJournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentJournals extends ListRecords
{
    protected static string $resource = PaymentJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
