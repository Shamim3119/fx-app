<?php

namespace App\Filament\Resources\PaymentJournals\Pages;

use App\Filament\Resources\PaymentJournals\PaymentJournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentJournal extends CreateRecord
{
    protected static string $resource = PaymentJournalResource::class;
}
