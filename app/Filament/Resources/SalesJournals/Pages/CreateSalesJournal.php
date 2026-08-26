<?php

namespace App\Filament\Resources\SalesJournals\Pages;

use App\Filament\Resources\SalesJournals\SalesJournalResource;
use App\Models\Account;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;
use App\Models\AccountBalance;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSalesJournal extends CreateRecord
{
    protected static string $resource = SalesJournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $masterCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 3)
            ->firstOrFail();

        $secondaryCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 2)
            ->firstOrFail();

        $drAmount = (float) $data['dr_amount'];
        $crAmount = (float) $data['cr_amount'];

        $masterRate = (float) $data['master_rate_input'];
        $secondaryRate = (float) $data['secondary_rate_input'];

        $data['type_id'] = 1;

        $data['master_currency'] = $masterCurrency->id;
        $data['dr_master_rate'] = $masterRate;
        $data['cr_master_rate'] = $masterRate;
        $data['dr_master_amount'] = $drAmount * $masterRate;
        $data['cr_master_amount'] = $crAmount * $masterRate;

        $data['secondary_currency'] = $secondaryCurrency->id;
        $data['dr_secondary_rate'] = $secondaryRate;
        $data['cr_secondary_rate'] = $secondaryRate;
        // $data['dr_secondary_amount'] = $drAmount * $secondaryRate;
        
        $data['cr_secondary_amount'] = $crAmount * $secondaryRate;
        $data['dr_secondary_amount'] = $data['dr_master_amount'] * $secondaryCurrency->general_to_master;
        $data['dr_master_amount'] = $data['dr_secondary_amount'] * $secondaryCurrency->master_to_general;

        unset(
            $data['master_rate_input'],
            $data['secondary_rate_input']
        );

        return $data;
    }

    protected function handleRecordCreation(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            // Lock Accounts
            Account::query()->lockForUpdate()->findOrFail($data['dr_account']);
            Account::query()->lockForUpdate()->findOrFail($data['cr_account']);

            SubsidiaryAccount::query()->lockForUpdate()->findOrFail($data['dr_sub_account']);
            SubsidiaryAccount::query()->lockForUpdate()->findOrFail($data['cr_sub_account']);

            $masterCurrency = Country::query()
                ->where('inactive', 0)
                ->where('currency_type', 3)
                ->firstOrFail();

            $secondaryCurrency = Country::query()
                ->where('inactive', 0)
                ->where('currency_type', 2)
                ->firstOrFail();

            // FIXED: Use first() instead of firstOrFail() so missing rows don't crash
            $drMasterBalance = AccountBalance::query()
                ->where('account_id', $data['dr_account'])
                ->where('currency_id', $masterCurrency->id)
                ->first();
            $drMstBal = $drMasterBalance?->balance ?? 0;

            $crMasterBalance = AccountBalance::query()
                ->where('account_id', $data['cr_account'])
                ->where('currency_id', $masterCurrency->id)
                ->first();
            $crMstBal = $crMasterBalance?->balance ?? 0;

            $drSecondaryBalance = AccountBalance::query()
                ->where('account_id', $data['dr_account'])
                ->where('currency_id', $secondaryCurrency->id)
                ->first();
            $drSecBal = $drSecondaryBalance?->balance ?? 0;

            $crSecondaryBalance = AccountBalance::query()
                ->where('account_id', $data['cr_account'])
                ->where('currency_id', $secondaryCurrency->id)
                ->first();
            $crSecBal = $crSecondaryBalance?->balance ?? 0;

            $drGeneralBalance = AccountBalance::query()
                ->where('account_id', $data['dr_account'])
                ->where('currency_id', $data['transaction_currency'])
                ->first();
            $drGenBal = $drGeneralBalance?->balance ?? 0;

            $crGeneralBalance = AccountBalance::query()
                ->where('account_id', $data['cr_account'])
                ->where('currency_id', $data['transaction_currency'])
                ->first();
            $crGenBal = $crGeneralBalance?->balance ?? 0;

            // Calculations
            $data['dr_master_balance'] = (float) $drMstBal - (float) $data['dr_master_amount'];
            $data['dr_secondary_balance'] = (float) $drSecBal - (float) $data['dr_secondary_amount'];
            $data['dr_balance'] = (float) $drGenBal - (float) $data['dr_amount'];

            $data['cr_master_balance'] = (float) $crMstBal + (float) $data['cr_master_amount'];
            $data['cr_secondary_balance'] = (float) $crSecBal + (float) $data['cr_secondary_amount'];
            // Note: If credit balance should increase, change '-' to '+' below if needed
            $data['cr_balance'] = (float) $crGenBal + (float) $data['cr_amount'];


            $data['master_balance_profit'] = $data['cr_master_amount'] - $data['dr_master_amount'];
            $data['secondary_balance_profit'] = $data['cr_secondary_amount'] - $data['dr_secondary_amount'];

 
            // Create Journal FIRST
            $journal = Journal::create($data);

            // ==============================
            // UPDATE / CREATE ACCOUNT BALANCES
            // ==============================

            // DR Master Balance
            if (!$drMasterBalance) {
                AccountBalance::create([
                    'account_id' => $data['dr_account'],
                    'currency_id' => $masterCurrency->id,
                    'balance' => $data['dr_master_balance'],
                ]);
            } else {
                $drMasterBalance->update(['balance' => $data['dr_master_balance']]);
            }

            // CR Master Balance (FIXED variable name)
            if (!$crMasterBalance) {
                AccountBalance::create([
                    'account_id' => $data['cr_account'],
                    'currency_id' => $masterCurrency->id,
                    'balance' => $data['cr_master_balance'],
                ]);
            } else {
                $crMasterBalance->update(['balance' => $data['cr_master_balance']]);
            }

            // DR Secondary Balance
            if (!$drSecondaryBalance) {
                AccountBalance::create([
                    'account_id' => $data['dr_account'],
                    'currency_id' => $secondaryCurrency->id,
                    'balance' => $data['dr_secondary_balance'],
                ]);
            } else {
                $drSecondaryBalance->update(['balance' => $data['dr_secondary_balance']]);
            }

            // CR Secondary Balance (FIXED variable name)
            if (!$crSecondaryBalance) {
                AccountBalance::create([
                    'account_id' => $data['cr_account'],
                    'currency_id' => $secondaryCurrency->id,
                    'balance' => $data['cr_secondary_balance'],
                ]);
            } else {
                $crSecondaryBalance->update(['balance' => $data['cr_secondary_balance']]);
            }

            // DR General Balance
            if (!$drGeneralBalance) {
                AccountBalance::create([
                    'account_id' => $data['dr_account'],
                    'currency_id' => $data['transaction_currency'],
                    'balance' => $data['dr_balance'],
                ]);
            } else {
                $drGeneralBalance->update(['balance' => $data['dr_balance']]);
            }

            // CR General Balance (FIXED variable name)
            if (!$crGeneralBalance) {
                AccountBalance::create([
                    'account_id' => $data['cr_account'],
                    'currency_id' => $data['transaction_currency'],
                    'balance' => $data['cr_balance'],
                ]);
            } else {
                $crGeneralBalance->update(['balance' => $data['cr_balance']]);
            }

            return $journal;
        });
    }
}