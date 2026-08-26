<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Country;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountSummaryWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        /*
         |--------------------------------------------------------------------------
         | MASTER CURRENCY
         |--------------------------------------------------------------------------
         */

        $masterCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 3)
            ->first();


        /*
         |--------------------------------------------------------------------------
         | SECONDARY CURRENCY
         |--------------------------------------------------------------------------
         */

        $secondaryCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 2)
            ->first();


        /*
         |--------------------------------------------------------------------------
         | ACCOUNT RECEIVABLE
         |
         | Customer Accounts
         | Secondary Currency
         |--------------------------------------------------------------------------
         */

        $accountReceivable = 0;

        if ($secondaryCurrency) {

            $accountReceivable = AccountBalance::query()
                ->where(
                    'currency_id',
                    $secondaryCurrency->id
                )
                ->whereIn(
                    'account_id',
                    Account::query()
                        ->where(
                            'type_id',
                            Account::TYPE_CUSTOMER
                        )
                        ->pluck('id')
                )
                ->sum('balance');

        }


        /*
         |--------------------------------------------------------------------------
         | ACCOUNT PAYABLE
         |
         | Vendor Accounts
         | Master Currency
         |--------------------------------------------------------------------------
         */

        $accountPayable = 0;

        if ($masterCurrency) {

            $accountPayable = AccountBalance::query()
                ->where(
                    'currency_id',
                    $masterCurrency->id
                )
                ->whereIn(
                    'account_id',
                    Account::query()
                        ->where(
                            'type_id',
                            Account::TYPE_VENDOR
                        )
                        ->pluck('id')
                )
                ->sum('balance');

        }


        /*
         |--------------------------------------------------------------------------
         | CURRENCY LABELS
         |--------------------------------------------------------------------------
         */

        $secondaryCode =
            $secondaryCurrency?->currency
            ?? $secondaryCurrency?->code
            ?? '';

        $masterCode =
            $masterCurrency?->currency
            ?? $masterCurrency?->code
            ?? '';


        return [

            Stat::make(
                'Account Receivable',
                number_format(
                    (float) abs($accountReceivable),
                    2
                ) . ' ' . $secondaryCode
            )
                ->description(
                    'Total customer balance'
                )
                ->descriptionIcon(
                    'heroicon-m-arrow-trending-up'
                )
                ->icon(
                    'heroicon-o-banknotes'
                ),


            Stat::make(
                'Account Payable',
                number_format(
                    (float) abs($accountPayable),
                    2
                ) . ' ' . $masterCode
            )
                ->description(
                    'Total vendor balance'
                )
                ->descriptionIcon(
                    'heroicon-m-arrow-trending-down'
                )
                ->icon(
                    'heroicon-o-credit-card'
                ),

        ];
    }
}