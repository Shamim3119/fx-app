<?php

namespace App\Filament\Resources\ReceiveJournals\Pages;

use App\Filament\Resources\ReceiveJournals\ReceiveJournalResource;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;

use Filament\Resources\Pages\CreateRecord;

use Illuminate\Support\Facades\DB;

class CreateReceiveJournal extends CreateRecord
{
    protected static string $resource =
        ReceiveJournalResource::class;


    /*
     * PREPARE JOURNAL DATA
     */
    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {

        /*
         * MASTER CURRENCY
         */
        $masterCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 3)
            ->firstOrFail();


        /*
         * SECONDARY CURRENCY
         */
        $secondaryCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 2)
            ->firstOrFail();


        $drAmount = (float) $data['dr_amount'];

        $crAmount = (float) $data['cr_amount'];


        /*
         * RECEIVE JOURNAL
         */
        $data['type_id'] = 3;


        /*
         * RECEIVE IS SECONDARY CURRENCY.
         *
         * transaction_currency = Secondary Currency
         *
         * Secondary amount is therefore 1:1.
         */
        $data['secondary_currency'] =
            $secondaryCurrency->id;

        $data['dr_secondary_rate'] = 1;

        $data['cr_secondary_rate'] = 1;

        $data['dr_secondary_amount'] =
            $drAmount;

        $data['cr_secondary_amount'] =
            $crAmount;


        /*
         * SECONDARY -> MASTER RATE
         *
         * User enters this rate.
         */
        $masterRate = (float)
            $data['master_rate_input'];

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
         * Remove temporary form fields.
         */
        unset(
            $data['master_rate_input'],
            $data['secondary_rate_input']
        );


        return $data;
    }


    protected function handleRecordCreation(
        array $data
    ): Journal {

        return DB::transaction(
            function () use ($data) {

                /*
                 * =====================================
                 * LOCK PARENT ACCOUNTS
                 * =====================================
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
                 * =====================================
                 * LOCK SUB ACCOUNTS
                 * =====================================
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
                 * =====================================
                 * GET MASTER CURRENCY
                 * =====================================
                 */

                $masterCurrency =
                    Country::query()
                        ->where(
                            'inactive',
                            0
                        )
                        ->where(
                            'currency_type',
                            3
                        )
                        ->firstOrFail();


                /*
                 * =====================================
                 * GET SECONDARY CURRENCY
                 * =====================================
                 */

                $secondaryCurrency =
                    Country::query()
                        ->where(
                            'inactive',
                            0
                        )
                        ->where(
                            'currency_type',
                            2
                        )
                        ->firstOrFail();


                /*
                 * =====================================
                 * GET DEBIT CUSTOMER BALANCES
                 * =====================================
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
                        ->first();

                $drMstBal =
                    $drMasterBalance?->balance ?? 0;


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
                        ->first();

                $drSecBal =
                    $drSecondaryBalance?->balance ?? 0;


                /*
                 * =====================================
                 * GET CREDIT COMPANY BALANCES
                 * =====================================
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
                        ->first();

                $crMstBal =
                    $crMasterBalance?->balance ?? 0;


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
                        ->first();

                $crSecBal =
                    $crSecondaryBalance?->balance ?? 0;


                /*
                 * =====================================
                 * GENERAL / TRANSACTION BALANCES
                 * =====================================
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
                        ->first();

                $drGenBal =
                    $drGeneralBalance?->balance ?? 0;


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
                        ->first();

                $crGenBal =
                    $crGeneralBalance?->balance ?? 0;


                /*
                 * =====================================
                 * CALCULATE DEBIT SIDE
                 * CUSTOMER
                 * =====================================
                 */

                $data['dr_master_balance'] =
                    (float) $drMstBal
                    -
                    (float) $data['dr_master_amount'];


                $data['dr_secondary_balance'] =
                    (float) $drSecBal
                    -
                    (float) $data['dr_secondary_amount'];


                $data['dr_balance'] =
                    (float) $drGenBal
                    -
                    (float) $data['dr_amount'];


                /*
                 * =====================================
                 * CALCULATE CREDIT SIDE
                 * COMPANY
                 * =====================================
                 */

                $data['cr_master_balance'] =
                    (float) $crMstBal
                    +
                    (float) $data['cr_master_amount'];


                $data['cr_secondary_balance'] =
                    (float) $crSecBal
                    +
                    (float) $data['cr_secondary_amount'];


                $data['cr_balance'] =
                    (float) $crGenBal
                    +
                    (float) $data['cr_amount'];


                /*
                 * =====================================
                 * PROFIT
                 * =====================================
                 */

                $data['master_balance_profit'] =
                    (float) $data['cr_master_amount']
                    -
                    (float) $data['dr_master_amount'];


                $data['secondary_balance_profit'] =
                    (float) $data['cr_secondary_amount']
                    -
                    (float) $data['dr_secondary_amount'];


                /*
                 * =====================================
                 * CREATE JOURNAL
                 * =====================================
                 */

                $journal =
                    Journal::create($data);


                /*
                 * =====================================
                 * UPDATE DEBIT CUSTOMER
                 * MASTER BALANCE
                 * =====================================
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
                 * DEBIT CUSTOMER
                 * SECONDARY BALANCE
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
                 * DEBIT CUSTOMER
                 * TRANSACTION BALANCE
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
                 * =====================================
                 * UPDATE CREDIT COMPANY
                 * MASTER BALANCE
                 * =====================================
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
                 * CREDIT COMPANY
                 * SECONDARY BALANCE
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
                 * CREDIT COMPANY
                 * TRANSACTION BALANCE
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