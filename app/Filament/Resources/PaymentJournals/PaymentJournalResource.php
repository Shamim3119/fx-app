<?php

namespace App\Filament\Resources\PaymentJournals;

use App\Filament\Resources\PaymentJournals\Pages\CreatePaymentJournal;
use App\Filament\Resources\PaymentJournals\Pages\ListPaymentJournals;
 
use App\Models\Account;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Utilities\Get;

use UnitEnum;

class PaymentJournalResource extends Resource
{
    protected static ?string $model = Journal::class;


    public const TYPE_PAYMENT = 4;
 
    protected static UnitEnum|string|null $navigationGroup = 'Journals';

    protected static ?string $navigationLabel = 'Payment';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Payment Journal';

    protected static ?string $pluralModelLabel = 'Payment Journals';

    protected static ?string $slug = 'payment-journals';

 
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-circle';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([


            /*
             * ==========================================================
             * DEBIT ACCOUNT
             * CUSTOMER
             * ==========================================================
             */
            Section::make('Debit Account')
                ->description(
                    'Select Company and Company Subsidiary Account.'
                )
                ->icon('heroicon-o-arrow-down-left')
                ->columns(2)
                ->schema([

                    /*
                     * CUSTOMER
                     */
                    Select::make('dr_account')
                        ->label('Company')
                        ->options(
                            Account::query()
                                ->where(
                                    'type_id',
                                    Account::TYPE_COMPANY
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(
                            function (Set $set): void {

                                /*
                                 * Clear previous subsidiary account
                                 * when customer changes.
                                 */
                                $set(
                                    'dr_sub_account',
                                    null
                                );
                            }
                        )
                        ->required(),


                    /*
                     * Company SUBSIDIARY ACCOUNT
                     */
                    Select::make('dr_sub_account')
                        ->label('Company Account')
                        ->options(
                            function (callable $get) {

                                $accountId =
                                    $get('dr_account');

                                if (! $accountId) {
                                    return [];
                                }

                                return SubsidiaryAccount::query()
                                    ->where(
                                        'account_id',
                                        $accountId
                                    )
                                    ->where(
                                        'type_id',
                                        SubsidiaryAccount::TYPE_COMPANY
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required(),


                                                Section::make('Vendor Recent Transactions')
                            ->description('Last 5 sales journal records for the selected vendor.')
                            ->icon('heroicon-o-arrow-up-circle')
                            ->visible(fn (Get $get): bool => filled($get('dr_account')))
                            ->schema([

                                Placeholder::make('vendor_recent_transactions')
                                    ->label('')
                                    ->content(function (Get $get): HtmlString {

                                        $accountId = $get('dr_account');

                                        if (! $accountId) {
                                            return new HtmlString('');
                                        }

                                        $journals = Journal::query()
                                            ->where('type_id', self::TYPE_PAYMENT)
                                            ->where(function ($query) use ($accountId) {
                                                $query
                                                    ->where('dr_account', $accountId)
                                                    ->orWhere('cr_account', $accountId);
                                            })
                                            ->with([
                                                'transactionCurrency',
                                                'masterCurrency',
                                            ])
                                            ->orderByDesc('id')
                                            ->limit(5)
                                            ->get();

                                        if ($journals->isEmpty()) {
                                            return new HtmlString(
                                                '<div class="text-sm text-gray-500">
                                                    No previous sales transactions found.
                                                </div>'
                                            );
                                        }

                                        $rows = '';

                                        foreach ($journals as $index => $journal) {

                                            /*
                                            * Selected vendor may be DR or CR.
                                            */
                                            $isDebit =
                                                (int) $journal->dr_account === (int) $accountId;

                                            if ($isDebit) {
                                                $type = 'DR';
                                                $amount = $journal->dr_amount;
                                                $balance = $journal->dr_master_balance;
                                            } else {
                                                $type = 'CR';
                                                $amount = $journal->cr_amount;
                                                $balance = $journal->cr_master_balance;
                                            }

                                            $transactionCurrency =
                                                $journal->transactionCurrency?->currency ?? '-';

                                            $masterCurrency =
                                                $journal->masterCurrency?->currency ?? '-';

                                            $date = $journal->tan_date
                                                ? $journal->tan_date->format('d-m-Y')
                                                : '-';

                                                $rows .= '
                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                        <td class="px-3 py-2 text-left">
                                                            ' . ($index + 1) . '
                                                        </td>

                                                        <td class="px-3 py-2 text-left" style="text-align: center !important;">
                                                            ' . e(date('d M, y', strtotime($date))) . '
                                                        </td>

                                                        <td class="px-3 py-2 text-center" style="text-align: center !important;">
                                                            ' . e($type) . '
                                                        </td>

                                                        <td class="px-3 py-2 text-right" style="text-align: right !important;">
                                                            ' . number_format((float) abs($amount), 2) . ' 
                                                            <span class="text-xs text-gray-500">' . e($transactionCurrency) . '</span>
                                                        </td>

                                                        <td class="px-3 py-2 text-right" style="text-align: right !important;">
                                                            ' . number_format((float) abs($balance), 2) . ' 
                                                            <span class="text-xs text-gray-500">' . e($masterCurrency) . '</span>
                                                        </td>
                                                    </tr>
                                                ';
                                        }

                                        return new HtmlString(
                                            '
                                            <div style="width: 100% !important; display: block;" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                                <table style="width: 100% !important; table-layout: auto;" class="text-sm text-left">
                                                    <thead class="border-b border-gray-200 dark:border-gray-700">
                                                        <tr>
                                                            <th class="px-3 py-2 text-left">
                                                                SL
                                                            </th>

                                                            <th class="px-3 py-2 text-left" style="text-align: center !important;">
                                                                Date
                                                            </th>

                                                            <th class="px-3 py-2 text-center" style="text-align: center !important;">
                                                                Type
                                                            </th>

                                                            <th class="px-3 py-2 text-right" style="text-align: right !important;">
                                                                Amount
                                                            </th>

                                                            <th class="px-3 py-2 text-right" style="text-align: right !important;">
                                                                Master Balance
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        ' . $rows . '
                                                    </tbody>
                                                </table>
                                            </div>
                                            '
                                        );
                                    })
                                    ->columnSpanFull(),

                                ])
                                ->columnSpanFull(),

                ]),


            /*
             * ==========================================================
             * CREDIT ACCOUNT
             * VENDOR
             * ==========================================================
             */
            Section::make('Credit Account')
                ->description(
                    'Select Vendor and Vendor Subsidiary Account.'
                )
                ->icon('heroicon-o-arrow-up-right')
                ->columns(2)
                ->schema([

                    /*
                     * Vendor
                     */
                    Select::make('cr_account')
                        ->label('Vendor')
                        ->options(
                            Account::query()
                                ->where(
                                    'type_id',
                                    Account::TYPE_VENDOR
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(
                            function (Set $set): void {

                                /*
                                 * Clear previous subsidiary account
                                 * when vendor changes.
                                 */
                                $set(
                                    'cr_sub_account',
                                    null
                                );
                            }
                        )
                        ->required(),


                    /*
                     * COMPANY SUBSIDIARY ACCOUNT
                     */
                    Select::make('cr_sub_account')
                        ->label('Vendor Account')
                        ->options(
                            function (callable $get) {

                                $accountId =
                                    $get('cr_account');

                                if (! $accountId) {
                                    return [];
                                }

                                return SubsidiaryAccount::query()
                                    ->where(
                                        'account_id',
                                        $accountId
                                    )
                                    ->where(
                                        'type_id',
                                        SubsidiaryAccount::TYPE_VENDOR
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                ]),


                                    /*
                    * LAST 5 CUSTOMER TRANSACTIONS
                    */
 
                    Section::make('Customer Recent Transactions')
                        ->description('Last 5 sales journal records for the selected customer.')
                        ->icon('heroicon-o-arrow-down-circle')
                        ->visible(fn (Get $get): bool => filled($get('cr_account')))
                        ->schema([

                            Placeholder::make('customer_recent_transactions')
                                ->label('')
                                ->content(function (Get $get): HtmlString {

                                    $accountId = $get('cr_account');

                                    if (! $accountId) {
                                        return new HtmlString('');
                                    }

                                    $journals = Journal::query()
                                        ->where('type_id', self::TYPE_PAYMENT)
                                        ->where(function ($query) use ($accountId) {
                                            $query
                                                ->where('dr_account', $accountId)
                                                ->orWhere('cr_account', $accountId);
                                        })
                                        ->with([
                                            'transactionCurrency',
                                            'secondaryCurrency',
                                        ])
                                        ->orderByDesc('id')
                                        ->limit(5)
                                        ->get();

                                    if ($journals->isEmpty()) {
                                        return new HtmlString(
                                            '<div class="text-sm text-gray-500">
                                                No previous sales transactions found.
                                            </div>'
                                        );
                                    }

                                    $rows = '';

                                    foreach ($journals as $index => $journal) {

                                        /*
                                        * Selected customer may be DR or CR.
                                        */
                                        $isDebit =
                                            (int) $journal->dr_account === (int) $accountId;

                                        if ($isDebit) {
                                            $type = 'DR';
                                            $amount = $journal->dr_amount;
                                            $balance = $journal->dr_secondary_balance;
                                        } else {
                                            $type = 'CR';
                                            $amount = $journal->cr_amount;
                                            $balance = $journal->cr_secondary_balance;
                                        }

                                        $transactionCurrency =
                                            $journal->transactionCurrency?->currency ?? '-';

                                        $secondaryCurrency =
                                            $journal->secondaryCurrency?->currency ?? '-';

                                        $date = $journal->tan_date
                                            ? $journal->tan_date->format('d-m-Y')
                                            : '-';

                                        $rows .= '
                                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                                <td class="px-3 py-2">
                                                    ' . ($index + 1) . '
                                                </td>

                                                <td class="px-3 py-2" style="text-align: center !important;">
                                                    ' . e(date('d M, y', strtotime($date))) . '
                                                </td>

                                                <td class="px-3 py-2 text-center" style="text-align: center !important;">
                                                    ' . e($type) . '
                                                </td>

                                                <td class="px-3 py-2 text-right" style="text-align: right !important;">
                                                    ' . number_format((float) abs($amount), 2) . '
                                                    ' . e($transactionCurrency) . '
                                                </td>

                                                <td class="px-3 py-2 text-right" style="text-align: right !important;">
                                                    ' . number_format((float) abs($balance), 2) . '
                                                    ' . e($secondaryCurrency) . '
                                                </td>
                                            </tr>
                                        ';
                                    }

                                    return new HtmlString(
                                        '
                                        <div style="width: 100% !important; display: block;" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                            <table style="width: 100% !important; table-layout: auto;" class="text-sm text-left">
                                                <thead class="border-b border-gray-200 dark:border-gray-700">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left">
                                                            SL
                                                        </th>

                                                        <th class="px-3 py-2 text-left" style="text-align: center !important;">
                                                            Date
                                                        </th>

                                                        <th class="px-3 py-2 text-center" style="text-align: center !important;">
                                                            Type
                                                        </th>

                                                        <th class="px-3 py-2 text-right" style="text-align: right !important;">
                                                            Amount
                                                        </th>

                                                        <th class="px-3 py-2 text-right" style="text-align: right !important;">
                                                            Secondary Balance
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    ' . $rows . '
                                                </tbody>
                                            </table>
                                        </div>
                                        '
                                    );
                                })
                                ->columnSpanFull(),

                        ])
                        ->columnSpanFull(),


            /*
             * ==========================================================
             * RETURN JOURNAL INFORMATION
             * ==========================================================
             */
            Section::make('Return Journal Information')
                ->icon('heroicon-o-document-arrow-up')
                ->columns(2)
                ->schema([

                    DatePicker::make('tan_date')
                        ->label('Transaction Date')
                        ->default(now())
                        ->required(),


                    Hidden::make('type_id')
                        ->default(
                            self::TYPE_PAYMENT
                        ),


                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder(
                            'Enter return remarks'
                        )
                        ->rows(3)
                        ->columnSpanFull(),


                    /*
                     * TRANSACTION CURRENCY
                     */
                    Select::make('transaction_currency')
                        ->label('Transaction Currency')
                        ->options(
                            Country::query()
                                ->where('inactive', 0)
                                ->where(
                                    'currency_type',
                                    3
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(
                            function (
                                $state,
                                Set $set
                            ): void {

                                $currency =
                                    Country::find($state);

                                if (! $currency) {
                                    return;
                                }

                                /*
                                 * General → Master
                                 */
                                $set(
                                    'master_rate_input',
                                    $currency->general_to_master
                                );


                                /*
                                 * General → Secondary
                                 */
                                $set(
                                    'secondary_rate_input',
                                    $currency->general_to_secondary
                                );
                            }
                        )
                        ->required(),


                    /*
                     * MASTER RATE
                     */
                    TextInput::make('master_rate_input')
                        ->label('General → Master Rate')
                        ->numeric()
                        ->step('0.00000001')
                        ->readOnly()
                        ->required(),


                    /*
                     * SECONDARY RATE
                     */
                    TextInput::make('secondary_rate_input')
                        ->label('General → Secondary Rate')
                        ->numeric()
                        ->step('0.00000001')
                        ->required(),


                    /*
                     * AMOUNT
                     */
                    TextInput::make('dr_amount')
                        ->label('Amount')
                        ->numeric()
                        ->step('0.00000001')
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            function (
                                $state,
                                Set $set
                            ): void {

                                /*
                                 * DR and CR amount
                                 * must always be equal.
                                 */
                                $set(
                                    'cr_amount',
                                    $state
                                );
                            }
                        )
                        ->required(),


                    /*
                     * HIDDEN CREDIT AMOUNT
                     */
                    Hidden::make('cr_amount')
                        ->required(),

                ]),

        ]);
    }


    public static function table(
        Table $table
    ): Table {

        return $table

            ->query(
                Journal::query()
                    ->where(
                        'type_id',
                        self::TYPE_PAYMENT
                    )
            )

            ->columns([

                TextColumn::make('id')
                    ->label('SL')
                    ->rowIndex(),


                TextColumn::make('tan_date')
                    ->label('Date')
                    ->date(),


                /*
                 * DEBIT = CUSTOMER
                 */
                TextColumn::make('drAccount.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),


                TextColumn::make('drSubAccount.name')
                    ->label('Customer Account')
                    ->searchable()
                    ->sortable(),


                /*
                 * CREDIT = VENDOR
                 */
                TextColumn::make('crAccount.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),


                TextColumn::make('crSubAccount.name')
                    ->label('Vendor Account')
                    ->searchable()
                    ->sortable(),


                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->searchable(),


                TextColumn::make('dr_amount')
                    ->label('Amount')
                    ->numeric(
                        decimalPlaces: 2
                    ),


                /*
                |--------------------------------------------------------------------------
                | MASTER BALANCE
                |--------------------------------------------------------------------------
                |
                | Vendor is DR side in Sales Journal:
                | Show dr_master_balance
                |
                | Customer is CR side:
                | Show cr_master_balance
                |
                */
                TextColumn::make('master_balance')
                    ->label('Due. AUD')
                    ->state(function (Journal $record) {
                        return abs((float) (
                            $record->dr_master_balance ??
                            $record->cr_master_balance ??
                            0
                        ));
                    })
                    ->numeric(decimalPlaces: 2),

                /*
                |--------------------------------------------------------------------------
                | SECONDARY BALANCE
                |--------------------------------------------------------------------------
                |
                | Vendor is DR side:
                | Show dr_secondary_balance
                |
                | Customer is CR side:
                | Show cr_secondary_balance
                |
                */
                TextColumn::make('secondary_balance')
                    ->label('Rec. BDT')
                    ->state(function (Journal $record) {
                        return abs((float) (
                            $record->dr_secondary_balance ??
                            $record->cr_secondary_balance ??
                            0
                        ));
                    })
                    ->numeric(decimalPlaces: 2),

            ])

            ->defaultSort(
                'id',
                'desc'
            );
    }


    public static function getPages(): array
    {
        return [

            'index' => ListPaymentJournals::route('/'),
            'create' => CreatePaymentJournal::route('/create'),

 

        ];
    }
}
 
