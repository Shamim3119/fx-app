<?php

namespace App\Filament\Resources\SalesJournals;

use App\Filament\Resources\SalesJournals\Pages\CreateSalesJournal;
use App\Filament\Resources\SalesJournals\Pages\ListSalesJournals;

use App\Models\Account;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;
use App\Models\AccountRate;
use App\Models\VendorRate;
use App\Models\CustomerRate;


 

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
             | BASIC JOURNAL INFORMATION
             |--------------------------------------------------------------------------
             */

            Section::make('Sales Journal Information')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([

                    Placeholder::make('transaction_date_display')
                        ->hiddenLabel()
                        ->content(
                            'Transaction Date : ' . now()->format('d M, Y')
                        ),

                    Hidden::make('tan_date')
                        ->default(
                            now()
                        )
                        ->dehydrated(true),

                    Hidden::make('type_id')
                        ->default(
                            self::TYPE_SALES
                        ),

                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->rows(2)
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
                            function (
                                $state,
                                Get $get,
                                Set $set
                            ): void {

                                if (! $state) {

                                    $set('dr_master_rate', null);
                                    $set('dr_secondary_rate', null);

                                    $set('cr_master_rate', null);
                                    $set('cr_secondary_rate', null);

                                    return;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | VENDOR RATE
                                |--------------------------------------------------------------------------
                                */
                                $vendorId =
                                    $get('dr_account');

                                if ($vendorId) {

                                    $vendorRates =
                                        self::getVendorRates(
                                            (int) $vendorId,
                                            (int) $state
                                        );

                                    $set(
                                        'dr_master_rate',
                                        $vendorRates['master']
                                    );

                                    $set(
                                        'dr_secondary_rate',
                                        $vendorRates['secondary']
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | CUSTOMER RATE
                                |--------------------------------------------------------------------------
                                */
                                $customerId =
                                    $get('cr_account');

                                if ($customerId) {

                                    $customerRates =
                                        self::getCustomerRates(
                                            (int) $customerId,
                                            (int) $state
                                        );

                                    $set(
                                        'cr_master_rate',
                                        $customerRates['master']
                                    );

                                    $set(
                                        'cr_secondary_rate',
                                        $customerRates['secondary']
                                    );
                                }
                            }
                        )

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
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
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
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(
                    function (
                        $state,
                        Get $get,
                        Set $set
                    ): void {

                        $set('dr_sub_account', null);

                        $currencyId =
                            $get('transaction_currency');

                        if (! $state || ! $currencyId) {

                            $set('dr_master_rate', null);
                            $set('dr_secondary_rate', null);

                            return;
                        }

                        $rates = self::getVendorRates(
                            (int) $state,
                            (int) $currencyId
                        );

                        $set(
                            'dr_master_rate',
                            $rates['master']
                        );

                        $set(
                            'dr_secondary_rate',
                            $rates['secondary']
                        );
                    }
                )

                ->suffixAction(
                    Action::make('addVendor')
                        ->label('Add Vendor')
                        ->icon('heroicon-o-plus')
                        ->modalHeading('Add New Vendor')
                        ->modalSubmitActionLabel('Create Vendor')
                        ->modalWidth('2xl')

                        ->schema([

                            TextInput::make('name')
                                ->label('Vendor Name')
                                ->placeholder('Enter vendor name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('phone')
                                ->label('Phone')
                                ->placeholder('+880 1XXXXXXXXX')
                                ->tel()
                                ->maxLength(30),

                            TextInput::make('email')
                                ->label('Email')
                                ->placeholder('vendor@example.com')
                                ->email()
                                ->maxLength(255),

                            TextInput::make('website')
                                ->label('Website')
                                ->placeholder('https://example.com')
                                ->url()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Textarea::make('address')
                                ->label('Address')
                                ->placeholder('Enter vendor address')
                                ->rows(3)
                                ->columnSpanFull(),

                        ])

                        ->action(
                            function (
                                array $data,
                                Set $set
                            ): void {

                                $vendor = Account::create([
                                    'name' => $data['name'],
                                    'phone' => $data['phone'] ?? null,
                                    'email' => $data['email'] ?? null,
                                    'website' => $data['website'] ?? null,
                                    'address' => $data['address'] ?? null,

                                    'type_id' => Account::TYPE_VENDOR,
                                ]);

                                // Automatically select the new vendor
                                $set('dr_account', $vendor->id);

                                // Reset vendor subsidiary account
                                $set('dr_sub_account', null);
                               
                                if ($vendor) {
                                    // Automatically create the customer's subsidiary account
                                    SubsidiaryAccount::create([
                                        'account_id'   => $vendor->id,
                                        'name'         => $vendor->name,
                                        'account_type' => SubsidiaryAccount::ACCOUNT_TYPE_CASH,
                                        'type_id'      => SubsidiaryAccount::TYPE_VENDOR,
                                    ]);
                                }
                            }
                        )
                )

                ->required(),

                    
                /*
                |--------------------------------------------------------------------------
                | VENDOR SUBSIDIARY ACCOUNT
                |--------------------------------------------------------------------------
                */
                Select::make('dr_sub_account')
                    ->label('Vendor Account')

                    ->options(
                        function (Get $get) {

                            $accountId = $get('dr_account');

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
                                ->where(
                                    'account_type',
                                    SubsidiaryAccount::ACCOUNT_TYPE_USER
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    )

                    ->searchable()
                    ->preload()

                    ->suffixAction(
                        Action::make('addVendorAccount')
                            ->label('Add Account')
                            ->icon('heroicon-o-plus')
                            ->modalHeading('Add Vendor Account')
                            ->modalSubmitActionLabel('Create Account')
                            ->modalWidth('lg')

                            ->visible(
                                fn (Get $get): bool =>
                                    filled($get('dr_account'))
                            )

                            ->schema([

                                TextInput::make('name')
                                    ->label('Account Name')
                                    ->placeholder(
                                        'Example: Main Cash Account'
                                    )
                                    ->required()
                                    ->maxLength(255),

                                Hidden::make('account_type')
                                    ->default(
                                        SubsidiaryAccount::ACCOUNT_TYPE_CASH
                                    ),

                            ])

                            ->action(
                                function (
                                    array $data,
                                    Get $get,
                                    Set $set
                                ): void {

                                    $vendorId = $get('dr_account');

                                    if (! $vendorId) {
                                        return;
                                    }

                                    $vendorAccount =
                                        SubsidiaryAccount::create([
                                            'account_id' => $vendorId,

                                            'name' => $data['name'],

                                            'type_id' =>
                                                SubsidiaryAccount::TYPE_VENDOR,
                                        ]);

                                    // Automatically select new vendor account
                                    $set(
                                        'dr_sub_account',
                                        $vendorAccount->id
                                    );
                                }
                            )
                    )

                    ->required(),





                    /*
                    |--------------------------------------------------------------------------
                    | VENDOR EXCHANGE RATES
                    |--------------------------------------------------------------------------
                    */
                    Section::make('Vendor Exchange Rates')
                        ->icon('heroicon-o-currency-dollar')
                        ->columns(2)
                        ->visible(
                            fn (Get $get): bool =>
                                filled($get('dr_account')) &&
                                filled($get('transaction_currency'))
                        )
                        ->schema([

                            TextInput::make('dr_master_rate')
                                ->label(
                                    function (Get $get): string {

                                        $currency = Country::find(
                                            $get('transaction_currency')
                                        );

                                        $currencies =
                                            self::getRateCurrencies();

                                        return '1 ' .
                                            ($currency?->currency ?? 'GEN') .
                                            ' = ? ' .
                                            ($currencies['master']?->currency ?? 'MST');
                                    }
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->readOnly()
                                ->dehydrated()
                                ->required(),

                            TextInput::make('dr_secondary_rate')
                                ->label(
                                    function (Get $get): string {

                                        $currency = Country::find(
                                            $get('transaction_currency')
                                        );

                                        $currencies =
                                            self::getRateCurrencies();

                                        return '1 ' .
                                            ($currency?->currency ?? 'GEN') .
                                            ' = ? ' .
                                            ($currencies['secondary']?->currency ?? 'SEC');
                                    }
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->readOnly()
                                ->dehydrated()
                                ->required(),

                        ])
                        ->columnSpanFull(),

                    
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
                |--------------------------------------------------------------------------
                | CUSTOMER
                |--------------------------------------------------------------------------
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
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(
                        function (
                            $state,
                            Get $get,
                            Set $set
                        ): void {

                            $set('cr_sub_account', null);

                            $currencyId =
                                $get('transaction_currency');

                            if (! $state || ! $currencyId) {

                                $set('cr_master_rate', null);
                                $set('cr_secondary_rate', null);

                                return;
                            }

                            $rates = self::getCustomerRates(
                                (int) $state,
                                (int) $currencyId
                            );

                            $set(
                                'cr_master_rate',
                                $rates['master']
                            );

                            $set(
                                'cr_secondary_rate',
                                $rates['secondary']
                            );
                        }
                    )

                    ->suffixAction(
                        Action::make('addCustomer')
                            ->label('Add Customer')
                            ->icon('heroicon-o-plus')
                            ->modalHeading('Add New Customer')
                            ->modalSubmitActionLabel('Create Customer')
                            ->modalWidth('2xl')

                            ->schema([

                                TextInput::make('name')
                                    ->label('Customer Name')
                                    ->placeholder('Enter customer name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->placeholder('+880 1XXXXXXXXX')
                                    ->tel()
                                    ->maxLength(30),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->placeholder('customer@example.com')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('website')
                                    ->label('Website')
                                    ->placeholder('https://example.com')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Textarea::make('address')
                                    ->label('Address')
                                    ->placeholder('Enter customer address')
                                    ->rows(3)
                                    ->columnSpanFull(),

                            ])

                            ->action(
                                function (
                                    array $data,
                                    Set $set
                                ): void {

                                    $customer = Account::create([
                                        'name' => $data['name'],
                                        'phone' => $data['phone'] ?? null,
                                        'email' => $data['email'] ?? null,
                                        'website' => $data['website'] ?? null,
                                        'address' => $data['address'] ?? null,

                                        'type_id' => Account::TYPE_CUSTOMER,
                                    ]);

                                    /*
                                    * Automatically select
                                    * newly created customer.
                                    */
                                    $set( 'cr_account',  $customer->id );

                                    /*
                                    * Reset customer account.
                                    */
                                    $set( 'cr_sub_account', null );

                                if ($customer) {
                      
                                    SubsidiaryAccount::create([
                                        'account_id'   => $customer->id,
                                        'name'         => $customer->name,
                                        'account_type' => SubsidiaryAccount::ACCOUNT_TYPE_CASH,
                                        'type_id'      => SubsidiaryAccount::TYPE_CUSTOMER,
                                    ]);
                                }
                                }
                            )
                    )

                    ->required(),


                /*
                |--------------------------------------------------------------------------
                | CUSTOMER SUBSIDIARY ACCOUNT
                |--------------------------------------------------------------------------
                */
                Select::make('cr_sub_account')
                    ->label('Customer Account')

                    ->options(
                        function (Get $get) {

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
                                ->where(
                                    'account_type',
                                    SubsidiaryAccount::ACCOUNT_TYPE_USER
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    )

                    ->searchable()
                    ->preload()

                    ->suffixAction(
                        Action::make('addCustomerAccount')
                            ->label('Add Account')
                            ->icon('heroicon-o-plus')
                            ->modalHeading('Add Customer Account')
                            ->modalSubmitActionLabel('Create Account')
                            ->modalWidth('lg')

                            ->visible(
                                fn (Get $get): bool =>
                                    filled($get('cr_account'))
                            )

                            ->schema([

                                TextInput::make('name')
                                    ->label('Account Name')
                                    ->placeholder(
                                        'Example: Main Cash Account'
                                    )
                                    ->required()
                                    ->maxLength(255),

                                Hidden::make('account_type')
                                    ->default(
                                        SubsidiaryAccount::ACCOUNT_TYPE_CASH
                                    ),

                            ])

                            ->action(
                                function (
                                    array $data,
                                    Get $get,
                                    Set $set
                                ): void {

                                    $customerId =
                                        $get('cr_account');

                                    if (! $customerId) {
                                        return;
                                    }

                                    $customerAccount =
                                        SubsidiaryAccount::create([
                                            'account_id' =>
                                                $customerId,

                                            'name' =>
                                                $data['name'],

                                            'type_id' =>
                                                SubsidiaryAccount::TYPE_CUSTOMER,
                                        ]);

                                    /*
                                    * Automatically select
                                    * newly created customer account.
                                    */
                                    $set(
                                        'cr_sub_account',
                                        $customerAccount->id
                                    );
                                }
                            )
                    )
                    ->required(),




            /*
            |--------------------------------------------------------------------------
            | CUSTOMER EXCHANGE RATES
            |--------------------------------------------------------------------------
            */
            Section::make('Customer Exchange Rates')
                ->icon('heroicon-o-currency-dollar')
                ->columns(2)
                ->visible(
                    fn (Get $get): bool =>
                        filled($get('cr_account')) &&
                        filled($get('transaction_currency'))
                )
                ->schema([

                    TextInput::make('cr_master_rate')
                        ->label(
                            function (Get $get): string {

                                $currency = Country::find(
                                    $get('transaction_currency')
                                );

                                $currencies =
                                    self::getRateCurrencies();

                                return '1 ' .
                                    ($currency?->currency ?? 'GEN') .
                                    ' = ? ' .
                                    ($currencies['master']?->currency ?? 'MST');
                            }
                        )
                        ->numeric()
                        ->step('0.00000001')
                        ->readOnly()
                        ->dehydrated()
                        ->required(),

                    TextInput::make('cr_secondary_rate')
                        ->label(
                            function (Get $get): string {

                                $currency = Country::find(
                                    $get('transaction_currency')
                                );

                                $currencies =
                                    self::getRateCurrencies();

                                return '1 ' .
                                    ($currency?->currency ?? 'GEN') .
                                    ' = ? ' .
                                    ($currencies['secondary']?->currency ?? 'SEC');
                            }
                        )
                        ->numeric()
                        ->step('0.00000001')
                        ->readOnly()
                        ->dehydrated()
                        ->required(),

                ])
                ->columnSpanFull(),






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
                    ->numeric(decimalPlaces: 2),

                TextColumn::make('secondary_balance_profit')
                    ->label('Profit BDT')
                    ->numeric(decimalPlaces: 2),

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








    /*
    |--------------------------------------------------------------------------
    | GET MASTER / SECONDARY CURRENCY
    |--------------------------------------------------------------------------
    */
    protected static function getRateCurrencies(): array
    {
        $masterCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 3)
            ->first();

        $secondaryCurrency = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 2)
            ->first();

        return [
            'master' => $masterCurrency,
            'secondary' => $secondaryCurrency,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET VENDOR RATE
    |--------------------------------------------------------------------------
    |
    | Priority:
    |
    | 1. VendorRate
    | 2. AccountRate type Vendor
    | 3. Country default
    |
    */
    protected static function getVendorRates(
        ?int $vendorId,
        ?int $currencyId
    ): array {

        if (! $currencyId) {
            return [
                'master' => null,
                'secondary' => null,
            ];
        }

        $currency = Country::find($currencyId);

        if (! $currency) {
            return [
                'master' => null,
                'secondary' => null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 1. VENDOR-SPECIFIC RATE
        |--------------------------------------------------------------------------
        */
        if ($vendorId) {

            $vendorRate = VendorRate::query()
                ->where('vendor_id', $vendorId)
                ->where('currency_id', $currencyId)
                ->first();

            if ($vendorRate) {

                return [
                    'master' =>
                        $vendorRate->general_to_master,

                    'secondary' =>
                        $vendorRate->general_to_secondary,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. ACCOUNT RATE - VENDOR
        |--------------------------------------------------------------------------
        */
        $accountRate = AccountRate::query()
            ->where(
                'type_id',
                Account::TYPE_VENDOR
            )
            ->where(
                'currency_id',
                $currencyId
            )
            ->first();

        if ($accountRate) {

            return [
                'master' =>
                    $accountRate->general_to_master,

                'secondary' =>
                    $accountRate->general_to_secondary,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. COUNTRY DEFAULT RATE
        |--------------------------------------------------------------------------
        */
        return [
            'master' =>
                $currency->general_to_master,

            'secondary' =>
                $currency->general_to_secondary,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET CUSTOMER RATE
    |--------------------------------------------------------------------------
    |
    | Priority:
    |
    | 1. CustomerRate
    | 2. AccountRate type Customer
    | 3. Country default
    |
    */
    protected static function getCustomerRates(
        ?int $customerId,
        ?int $currencyId
    ): array {

        if (! $currencyId) {
            return [
                'master' => null,
                'secondary' => null,
            ];
        }

        $currency = Country::find($currencyId);

        if (! $currency) {
            return [
                'master' => null,
                'secondary' => null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 1. CUSTOMER-SPECIFIC RATE
        |--------------------------------------------------------------------------
        */
        if ($customerId) {

            $customerRate = CustomerRate::query()
                ->where('customer_id', $customerId)
                ->where('currency_id', $currencyId)
                ->first();

            if ($customerRate) {

                return [
                    'master' =>
                        $customerRate->general_to_master,

                    'secondary' =>
                        $customerRate->general_to_secondary,
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. ACCOUNT RATE - CUSTOMER
        |--------------------------------------------------------------------------
        */
        $accountRate = AccountRate::query()
            ->where(
                'type_id',
                Account::TYPE_CUSTOMER
            )
            ->where(
                'currency_id',
                $currencyId
            )
            ->first();

        if ($accountRate) {

            return [
                'master' =>
                    $accountRate->general_to_master,

                'secondary' =>
                    $accountRate->general_to_secondary,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 3. COUNTRY DEFAULT RATE
        |--------------------------------------------------------------------------
        */
        return [
            'master' =>
                $currency->general_to_master,

            'secondary' =>
                $currency->general_to_secondary,
        ];
    }
}