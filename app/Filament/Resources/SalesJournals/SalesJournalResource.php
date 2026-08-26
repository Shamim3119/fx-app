<?php

namespace App\Filament\Resources\SalesJournals;

use App\Filament\Resources\SalesJournals\Pages\CreateSalesJournal;
use App\Filament\Resources\SalesJournals\Pages\ListSalesJournals;

use App\Models\Account;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;

use Filament\Actions\Action;
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
use Filament\Forms\Components\FileUpload;

use UnitEnum;

class SalesJournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    public const TYPE_SALES = 1;

    protected static UnitEnum|string|null $navigationGroup = 'Journals';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sales';

    protected static ?string $modelLabel = 'Sales Journal';

    protected static ?string $pluralModelLabel = 'Sales Journals';

    protected static ?string $slug = 'sales';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shopping-cart';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([


                    /*
             |--------------------------------------------------------------------------
             | DR ACCOUNT
             |--------------------------------------------------------------------------
             */

            Section::make('Debit Account')
                ->description(
                    'Select Vendor and Vendor Subsidiary Account.'
                )
                ->icon('heroicon-o-arrow-down-left')
                ->columns(2)
                ->schema([

                    /*
                    * VENDOR
                    */
                    Select::make('dr_account')
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
                        ->live()
                        ->afterStateUpdated(
                            function (Set $set): void {
                                /*
                                * Reset subsidiary account
                                * when vendor changes.
                                */
                                $set('dr_sub_account', null);
                            }
                        )
                        ->required(),

                    /*
                    * VENDOR SUBSIDIARY ACCOUNT
                    */
                    Select::make('dr_sub_account')
                        ->label('Vendor Account')
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
                                        SubsidiaryAccount::TYPE_VENDOR
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }
                        )
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
                                            ->where('type_id', self::TYPE_SALES)
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
             |--------------------------------------------------------------------------
             | CR ACCOUNT
             |--------------------------------------------------------------------------
             */

            Section::make('Credit Account')
                ->description(
                    'Select Customer and Customer Subsidiary Account.'
                )
                ->icon('heroicon-o-arrow-up-right')
                ->columns(2)
                ->schema([

                    /*
                    * CUSTOMER
                    */
                    Select::make('cr_account')
                        ->label('Customer')
                        ->options(
                            Account::query()
                                ->where(
                                    'type_id',
                                    Account::TYPE_CUSTOMER
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->live()
                        ->afterStateUpdated(
                            function (Set $set): void {

                                /*
                                * Reset subsidiary account
                                * when customer changes.
                                */
                                $set('cr_sub_account', null);
                            }
                        )
                        ->required(),

                    /*
                    * CUSTOMER SUBSIDIARY ACCOUNT
                    */
                    Select::make('cr_sub_account')
                        ->label('Customer Account')
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
                                        SubsidiaryAccount::TYPE_CUSTOMER
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }
                        )
                        ->required(),

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
                                        ->where('type_id', self::TYPE_SALES)
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


                ]),

            /*
             |--------------------------------------------------------------------------
             | BASIC JOURNAL INFORMATION
             |--------------------------------------------------------------------------
             */

            Section::make('Sales Journal Information')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([

                    DatePicker::make('tan_date')
                        ->label('Transaction Date')
                        ->default(now())
                        ->required(),

                    Hidden::make('type_id')
                        ->default(
                            self::TYPE_SALES
                        ),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->rows(3)
                        ->columnSpanFull(),


                    /*
                     |--------------------------------------------------------------------------
                     | TRANSACTION CURRENCY
                     |--------------------------------------------------------------------------
                     */

                    Select::make('transaction_currency')
                        ->label('Transaction Currency')

                        ->options(
                            Country::query()
                                ->where('inactive', 0)
                                ->where('currency_type', 1)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                        )

                        ->searchable()
                        ->preload()
                        ->live()

                        ->afterStateUpdated(
                            function ($state, Set $set): void {

                                $currency =
                                    Country::find($state);

                                if (! $currency) {
                                    return;
                                }

                                $set(
                                    'master_rate_input',
                                    $currency->general_to_master
                                );

                                $set(
                                    'secondary_rate_input',
                                    $currency->general_to_secondary
                                );
                            }
                        )

                        ->required(),


                    /*
                     |--------------------------------------------------------------------------
                     | MASTER RATE
                     |--------------------------------------------------------------------------
                     */

                    TextInput::make('master_rate_input')
                        ->label('General → Master Rate')
                        ->numeric()
                        ->step('0.00000001')
                        ->required(),


                    /*
                     |--------------------------------------------------------------------------
                     | SECONDARY RATE
                     |--------------------------------------------------------------------------
                     */

                    TextInput::make('secondary_rate_input')
                        ->label('General → Secondary Rate')
                        ->numeric()
                        ->step('0.00000001')
                        ->required(),



                TextInput::make('dr_amount')
                    ->label('Amount')
                    ->numeric()
                    ->step('0.00000001')
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        function ($state, Set $set): void {

                            $set('cr_amount', $state);

                        }
                    )
                    ->required(),


                Hidden::make('cr_amount')
                    ->label('Credit Amount')
          
  
                    ->live(onBlur: true)
                    ->afterStateUpdated(
                        function ($state, Set $set): void {

                            $set('dr_amount', $state);

                        }
                    )
                    ->required(),


                ]),


 

        
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Journal::query()
                    ->where('type_id', 1)
            )

            ->columns([

                TextColumn::make('id')
                    ->label('SL')
                    ->rowIndex(),

                TextColumn::make('tan_date')
                    ->label('Date')
                    ->date(),

                TextColumn::make('drAccount.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('drSubAccount.name')
                    ->label('Dr. Account')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('crAccount.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('crSubAccount.name')
                    ->label('Cr. Account')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->searchable(),

                TextColumn::make('dr_amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 2),

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

                TextColumn::make('master_balance_profit')
                    ->label('Profit AE')
                    ->numeric(decimalPlaces: 5),

                TextColumn::make('secondary_balance_profit')
                    ->label('Profit BDT')
                    ->numeric(decimalPlaces: 5),

            ])

            ->defaultSort('id', 'desc');
    }


    public static function getPages(): array
    {
        return [

            'index' =>
                ListSalesJournals::route('/'),

            'create' =>
                CreateSalesJournal::route('/create'),

        ];
    }
}