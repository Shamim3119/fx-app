<?php

namespace App\Filament\Resources\ReturnJournals\Pages;

use App\Filament\Resources\ReturnJournals\ReturnJournalResource;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;

use Filament\Resources\Pages\CreateRecord;

use Illuminate\Support\Facades\DB;

class CreateReturnJournal extends CreateRecord
{
    protected static string $resource =
        ReturnJournalResource::class;


    /*
     * ==========================================================
     * PREPARE JOURNAL DATA
     * ==========================================================
     */
    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        /*
         * MASTER CURRENCY
         */
        $masterCurrency =
            Country::query()
                ->where('inactive', 0)
                ->where('currency_type', 3)
                ->firstOrFail();


        /*
         * SECONDARY CURRENCY
         */
        $secondaryCurrency =
            Country::query()
                ->where('inactive', 0)
                ->where('currency_type', 2)
                ->firstOrFail();


        /*
         * AMOUNTS
         */
        $drAmount =
            (float) $data['dr_amount'];

        $crAmount =
            (float) $data['cr_amount'];


        /*
         * USER ADJUSTABLE RATES
         */
        $masterRate =
            (float) $data['master_rate_input'];

        $secondaryRate =
            (float) $data['secondary_rate_input'];


        /*
         * RETURN JOURNAL TYPE = 2
         */
        $data['type_id'] =
            ReturnJournalResource::TYPE_RETURN;


        /*
         * ======================================================
         * MASTER CALCULATIONS
         * ======================================================
         */
        $data['master_currency'] =
            $masterCurrency->id;


        $data['dr_master_rate'] =
            $masterRate;

        $data['cr_master_rate'] =
            $masterRate;


        $data['dr_master_amount'] =
            $drAmount * $masterRate;

        $data['cr_master_amount'] =
            $crAmount * $masterRate;


        /*
         * ======================================================
         * SECONDARY CALCULATIONS
         * ======================================================
         */
        $data['secondary_currency'] =
            $secondaryCurrency->id;


        $data['dr_secondary_rate'] =
            $secondaryRate;

        $data['cr_secondary_rate'] =
            $secondaryRate;


        $data['dr_secondary_amount'] =
            $drAmount * $secondaryRate;

        $data['cr_secondary_amount'] =
            $crAmount * $secondaryRate;


        /*
         * These are only form helper fields.
         * They don't exist in the journals table.
         */
        unset(
            $data['master_rate_input'],
            $data['secondary_rate_input']
        );


        return $data;
    }


    /*
     * ==========================================================
     * CREATE JOURNAL AND UPDATE BALANCES
     * ==========================================================
     */
    protected function handleRecordCreation(
        array $data
    ): Journal {

        return DB::transaction(
            function () use ($data) {


                /*
                 * ==================================================
                 * LOCK MAIN ACCOUNTS
                 * ==================================================
                 */
                Account::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['dr_account']
                    );


                Account::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['cr_account']
                    );


                /*
                 * ==================================================
                 * LOCK SUBSIDIARY ACCOUNTS
                 * ==================================================
                 */
                SubsidiaryAccount::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['dr_sub_account']
                    );


                SubsidiaryAccount::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['cr_sub_account']
                    );


                /*
                 * ==================================================
                 * GET MASTER CURRENCY
                 * ==================================================
                 */
                $masterCurrency =
                    Country::query()
                        ->where('inactive', 0)
                        ->where(
                            'currency_type',
                            3
                        )
                        ->firstOrFail();


                /*
                 * ==================================================
                 * GET SECONDARY CURRENCY
                 * ==================================================
                 */
                $secondaryCurrency =
                    Country::query()
                        ->where('inactive', 0)
                        ->where(
                            'currency_type',
                            2
                        )
                        ->firstOrFail();


                /*
                 * ==================================================
                 * GET DR MASTER BALANCE
                 * ==================================================
                 */
                $drMasterBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['dr_account']
                        )
                        ->where(
                            'currency_id',
                            $masterCurrency->id
                        )
                        ->lockForUpdate()
                        ->first();


                $drMstBal =
                    $drMasterBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * GET CR MASTER BALANCE
                 * ==================================================
                 */
                $crMasterBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['cr_account']
                        )
                        ->where(
                            'currency_id',
                            $masterCurrency->id
                        )
                        ->lockForUpdate()
                        ->first();


                $crMstBal =
                    $crMasterBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * GET DR SECONDARY BALANCE
                 * ==================================================
                 */
                $drSecondaryBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['dr_account']
                        )
                        ->where(
                            'currency_id',
                            $secondaryCurrency->id
                        )
                        ->lockForUpdate()
                        ->first();


                $drSecBal =
                    $drSecondaryBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * GET CR SECONDARY BALANCE
                 * ==================================================
                 */
                $crSecondaryBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['cr_account']
                        )
                        ->where(
                            'currency_id',
                            $secondaryCurrency->id
                        )
                        ->lockForUpdate()
                        ->first();


                $crSecBal =
                    $crSecondaryBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * GET DR GENERAL BALANCE
                 * ==================================================
                 */
                $drGeneralBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['dr_account']
                        )
                        ->where(
                            'currency_id',
                            $data['transaction_currency']
                        )
                        ->lockForUpdate()
                        ->first();


                $drGenBal =
                    $drGeneralBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * GET CR GENERAL BALANCE
                 * ==================================================
                 */
                $crGeneralBalance =
                    AccountBalance::query()
                        ->where(
                            'account_id',
                            $data['cr_account']
                        )
                        ->where(
                            'currency_id',
                            $data['transaction_currency']
                        )
                        ->lockForUpdate()
                        ->first();


                $crGenBal =
                    $crGeneralBalance?->balance ?? 0;


                /*
                 * ==================================================
                 * CALCULATE DEBIT BALANCES
                 *
                 * RETURN:
                 * DR = CUSTOMER
                 *
                 * Debit decreases balance.
                 * ==================================================
                 */
                $data['dr_master_balance'] =
                    (float) $drMstBal
                    - (float) $data['dr_master_amount'];


                $data['dr_secondary_balance'] =
                    (float) $drSecBal
                    - (float) $data['dr_secondary_amount'];


                $data['dr_balance'] =
                    (float) $drGenBal
                    - (float) $data['dr_amount'];


                /*
                 * ==================================================
                 * CALCULATE CREDIT BALANCES
                 *
                 * RETURN:
                 * CR = VENDOR
                 *
                 * Credit increases balance.
                 * ==================================================
                 */
                $data['cr_master_balance'] =
                    (float) $crMstBal
                    + (float) $data['cr_master_amount'];


                $data['cr_secondary_balance'] =
                    (float) $crSecBal
                    + (float) $data['cr_secondary_amount'];


                $data['cr_balance'] =
                    (float) $crGenBal
                    + (float) $data['cr_amount'];


                /*
                 * ==================================================
                 * PROFIT / DIFFERENCE
                 * ==================================================
                 */
                $data['master_balance_profit'] =
                    (float) $data['cr_master_amount']
                    - (float) $data['dr_master_amount'];


                $data['secondary_balance_profit'] =
                    (float) $data['cr_secondary_amount']
                    - (float) $data['dr_secondary_amount'];


                /*
                 * ==================================================
                 * CREATE JOURNAL
                 * ==================================================
                 */
                $journal =
                    Journal::create($data);


                /*
                 * ==================================================
                 * UPDATE DR MASTER BALANCE
                 * ==================================================
                 */
                if (! $drMasterBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['dr_account'],

                        'currency_id' =>
                            $masterCurrency->id,

                        'balance' =>
                            $data['dr_master_balance'],

                    ]);

                } else {

                    $drMasterBalance->update([

                        'balance' =>
                            $data['dr_master_balance'],

                    ]);
                }


                /*
                 * ==================================================
                 * UPDATE CR MASTER BALANCE
                 * ==================================================
                 */
                if (! $crMasterBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['cr_account'],

                        'currency_id' =>
                            $masterCurrency->id,

                        'balance' =>
                            $data['cr_master_balance'],

                    ]);

                } else {

                    $crMasterBalance->update([

                        'balance' =>
                            $data['cr_master_balance'],

                    ]);
                }


                /*
                 * ==================================================
                 * UPDATE DR SECONDARY BALANCE
                 * ==================================================
                 */
                if (! $drSecondaryBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['dr_account'],

                        'currency_id' =>
                            $secondaryCurrency->id,

                        'balance' =>
                            $data['dr_secondary_balance'],

                    ]);

                } else {

                    $drSecondaryBalance->update([

                        'balance' =>
                            $data['dr_secondary_balance'],

                    ]);
                }


                /*
                 * ==================================================
                 * UPDATE CR SECONDARY BALANCE
                 * ==================================================
                 */
                if (! $crSecondaryBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['cr_account'],

                        'currency_id' =>
                            $secondaryCurrency->id,

                        'balance' =>
                            $data['cr_secondary_balance'],

                    ]);

                } else {

                    $crSecondaryBalance->update([

                        'balance' =>
                            $data['cr_secondary_balance'],

                    ]);
                }


                /*
                 * ==================================================
                 * UPDATE DR GENERAL BALANCE
                 * ==================================================
                 */
                if (! $drGeneralBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['dr_account'],

                        'currency_id' =>
                            $data['transaction_currency'],

                        'balance' =>
                            $data['dr_balance'],

                    ]);

                } else {

                    $drGeneralBalance->update([

                        'balance' =>
                            $data['dr_balance'],

                    ]);
                }


                /*
                 * ==================================================
                 * UPDATE CR GENERAL BALANCE
                 * ==================================================
                 */
                if (! $crGeneralBalance) {

                    AccountBalance::create([

                        'account_id' =>
                            $data['cr_account'],

                        'currency_id' =>
                            $data['transaction_currency'],

                        'balance' =>
                            $data['cr_balance'],

                    ]);

                } else {

                    $crGeneralBalance->update([

                        'balance' =>
                            $data['cr_balance'],

                    ]);
                }


                return $journal;

            }
        );
    }
}