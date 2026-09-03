<?php

namespace App\Filament\Resources\ReceiveJournals;

use App\Filament\Resources\ReceiveJournals\Pages\CreateReceiveJournal;
use App\Filament\Resources\ReceiveJournals\Pages\ListReceiveJournals;

use App\Models\Account;
use App\Models\Country;
use App\Models\Journal;
use App\Models\SubsidiaryAccount;
use App\Models\CustomerRate;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;

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

class ReceiveJournalResource extends Resource
{
    protected static ?string $model = Journal::class;


    public const TYPE_RECEIVE = 3;
 
    protected static UnitEnum|string|null $navigationGroup = 'Journals';

    protected static ?string $navigationLabel = 'Receive';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Receive Journal';

    protected static ?string $pluralModelLabel = 'Receive Journals';

    protected static ?string $slug = 'receive-journals';

 
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-down-circle';
    }


    /**
     * Recalculate final customer dues.
     */
    protected static function calculateFinalDues(
        Set $set,
        Get $get
    ): void {

        /*
        * Original customer dues
        */
        $masterDue = (float) (
            $get('customer_master_due') ?? 0
        );

        $secondaryDue = (float) (
            $get('customer_secondary_due') ?? 0
        );

        /*
        * Payments
        */
        $masterPayment = (float) (
            $get('payment_master_currency') ?? 0
        );

        $secondaryPayment = (float) (
            $get('payment_secondary_currency') ?? 0
        );

        /*
        * Adjustments
        */
        $masterAdjustment = (float) (
            $get('master_adjustment_amount') ?? 0
        );

        $secondaryAdjustment = (float) (
            $get('secondary_adjustment_amount') ?? 0
        );

        /*
        * Final Master Due
        *
        * Due - Payment + Adjustment
        */
        $finalMasterDue =
            $masterDue
            - $masterPayment
            + $masterAdjustment;

        /*
        * Final Secondary Due
        *
        * Due - Payment + Adjustment
        */
        $finalSecondaryDue =
            $secondaryDue
            - $secondaryPayment
            + $secondaryAdjustment;

        /*
        * Update Final Due fields
        */
        $set(
            'master_currency_due_after_adjustment',
            number_format(
                $finalMasterDue,
                2,
                '.',
                ''
            )
        );

        $set(
            'secondary_currency_due_after_adjustment',
            number_format(
                $finalSecondaryDue,
                2,
                '.',
                ''
            )
        );
    }
 



    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            /*
             * ==========================================================
             * RETURN JOURNAL INFORMATION
             * ==========================================================
             */
            Section::make('Receive Journal Information')
                ->icon('heroicon-o-document-arrow-up')
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
                            self::TYPE_RECEIVE
                        ),


                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->placeholder(
                            'Enter return remarks'
                        )
                        ->rows(3)
                        ->columnSpanFull(),

 
 


                    /*
                     * HIDDEN CREDIT AMOUNT
                     */
                    Hidden::make('cr_amount')
                        ->required(),

                ]),

            /*
             * ==========================================================
             * DEBIT ACCOUNT
             * CUSTOMER
             * ==========================================================
             */
            Section::make('Debit Account')
                ->description(
                    'Select Customer and Customer Subsidiary Account.'
                )
                ->icon('heroicon-o-arrow-down-left')
                ->columns(2)
                ->schema([

                    /*
                     * CUSTOMER
                     */
 
                Select::make('dr_account')
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
    function (Set $set, Get $get, $state): void {

        /*
         * ======================================================
         * RESET SUBSIDIARY ACCOUNT
         * ======================================================
         */
        $set('dr_sub_account', null);

        /*
         * ======================================================
         * RESET PAYMENT
         * ======================================================
         */
        $set('payment_master_currency', null);
        $set('payment_secondary_currency', null);

        /*
         * ======================================================
         * RESET ADJUSTMENTS
         * ======================================================
         */
        $set('master_adjustment_amount', 0);
        $set('secondary_adjustment_amount', 0);

        /*
         * ======================================================
         * RESET FINAL DUES
         * ======================================================
         */
        $set('master_currency_due_after_adjustment', 0);
        $set('secondary_currency_due_after_adjustment', 0);

        /*
         * ======================================================
         * RESET ORIGINAL DUES
         * ======================================================
         */
        $set('customer_master_due', 0);
        $set('customer_secondary_due', 0);

        /*
         * No customer selected
         */
        if (! $state) {
            return;
        }


        /*
         * ======================================================
         * AUTO SELECT CUSTOMER SUBSIDIARY ACCOUNT
         * ======================================================
         *
         * Get all subsidiary accounts belonging to
         * the selected customer.
         *
         * If there is only one:
         *     select it automatically.
         *
         * If there are multiple:
         *     select the first/top account.
         *
         * If there are none:
         *     leave empty.
         */
        $customerSubAccount = SubsidiaryAccount::query()
            ->where('account_id', $state)
            ->where(
                'type_id',
                SubsidiaryAccount::TYPE_CUSTOMER
            )
            ->where(
                'account_type',
                SubsidiaryAccount::ACCOUNT_TYPE_USER
            )
            ->orderBy('id')
            ->first();

        if ($customerSubAccount) {
            $set(
                'dr_sub_account',
                $customerSubAccount->id
            );
        }


        /*
         * ======================================================
         * MASTER CURRENCY
         * currency_type = 3
         * ======================================================
         */
        $masterCurrency = Country::query()
            ->where('currency_type', 3)
            ->first();

        if ($masterCurrency) {

            $masterBalance = \App\Models\AccountBalance::query()
                ->where('account_id', $state)
                ->where('currency_id', $masterCurrency->id)
                ->value('balance');

            $masterBalance = (float) ($masterBalance ?? 0);

            /*
             * Original Master Due
             */
            $set(
                'customer_master_due',
                $masterBalance
            );

            /*
             * Initially:
             *
             * Final Master Due = Original Master Due
             */
            $set(
                'master_currency_due_after_adjustment',
                number_format(
                    $masterBalance,
                    2,
                    '.',
                    ''
                )
            );
        }


        /*
         * ======================================================
         * SECONDARY CURRENCY
         * currency_type = 2
         * ======================================================
         */
        $secondaryCurrency = Country::query()
            ->where('currency_type', 2)
            ->first();

        if ($secondaryCurrency) {

            $secondaryBalance = \App\Models\AccountBalance::query()
                ->where('account_id', $state)
                ->where('currency_id', $secondaryCurrency->id)
                ->value('balance');

            $secondaryBalance = (float) ($secondaryBalance ?? 0);

            /*
             * Original Secondary Due
             */
            $set(
                'customer_secondary_due',
                $secondaryBalance
            );

            /*
             * Initially:
             *
             * Final Secondary Due = Original Secondary Due
             */
            $set(
                'secondary_currency_due_after_adjustment',
                number_format(
                    $secondaryBalance,
                    2,
                    '.',
                    ''
                )
            );
        }
    }
)
 

 
                    ->required(),
 



                    /*
                     * CUSTOMER SUBSIDIARY ACCOUNT
                     */
                    Select::make('dr_sub_account')
                        ->label('Customer Account')
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
                        ->required(),






                /*
                * ==========================================================
                * CUSTOMER MASTER CURRENCY DUE
                * ==========================================================
                */
                TextInput::make('customer_master_due')
                    ->label('Master Currency Due')
                    ->numeric()
                    ->readOnly()
                    ->default(0)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, Get $get): void {
                        $accountId = $get('dr_account');

                        if (! $accountId) {
                            $component->state(0);
                            return;
                        }

                        $masterCurrency = Country::query()
                            ->where('currency_type', 3)
                            ->first();

                        if (! $masterCurrency) {
                            $component->state(0);
                            return;
                        }

                        $balance = \App\Models\AccountBalance::query()
                            ->where('account_id', $accountId)
                            ->where('currency_id', $masterCurrency->id)
                            ->value('balance');

                        $component->state((int) ($balance ?? 0));
                    })
                    ->live(),


                /*
                * ==========================================================
                * CUSTOMER SECONDARY CURRENCY DUE
                * ==========================================================
                */
                TextInput::make('customer_secondary_due')
                    ->label('Secondary Currency Due')
                    ->numeric()
                    ->readOnly()
                    ->default(0)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, Get $get): void {
                        $accountId = $get('dr_account');

                        if (! $accountId) {
                            $component->state(0);
                            return;
                        }

                        $secondaryCurrency = Country::query()
                            ->where('currency_type', 2)
                            ->first();

                        if (! $secondaryCurrency) {
                            $component->state(0);
                            return;
                        }

                        $balance = \App\Models\AccountBalance::query()
                            ->where('account_id', $accountId)
                            ->where('currency_id', $secondaryCurrency->id)
                            ->value('balance');

                        $component->state((int) ($balance ?? 0));
                    })
                    ->live(),

                    
 
/*
 * ==========================================================
 * PAYMENT CURRENCY
 * ==========================================================
 */
Radio::make('payment_currency')
    ->label('Payment Currency')
    ->options([
        'master' => 'Master Currency',
        'secondary' => 'Secondary Currency',
    ])
    ->default('secondary')
    ->inline()
    ->live()
    ->required()
    
    ->afterStateUpdated(function (
        Set $set,
        Get $get,
        ?string $state
    ): void {

        /*
        * Clear both payment fields
        */
        $set('payment_master_currency', null);
        $set('payment_secondary_currency', null);

        /*
        * Reset both adjustments
        */
        $set('master_adjustment_amount', 0);
        $set('secondary_adjustment_amount', 0);

        /*
        * Reset Final Due back to original Customer Due
        */
        $set(
            'master_currency_due_after_adjustment',
            number_format(
                (float) ($get('customer_master_due') ?? 0),
                2,
                '.',
                ''
            )
        );

        $set(
            'secondary_currency_due_after_adjustment',
            number_format(
                (float) ($get('customer_secondary_due') ?? 0),
                2,
                '.',
                ''
            )
        );
    })
    

    ->columnSpanFull(),
 
/*
 * ==========================================================
 * PAYMENT MASTER CURRENCY
 * ==========================================================
 */
TextInput::make('payment_master_currency')
    ->label('Payment Master Currency')
    ->numeric()
    ->inputMode('decimal')
    ->step('0.01')
    ->default(null)
    ->live()
    ->disabled(
        fn (Get $get): bool =>
            $get('payment_currency') !== 'master'
    )
    ->afterStateUpdated(function (
        Set $set,
        Get $get,
        $state
    ): void {

        /*
         * This field works only when Master Currency
         * is selected.
         */
        if ($get('payment_currency') !== 'master') {
            return;
        }

        /*
         * If Master payment is empty,
         * clear Secondary payment and recalculate dues.
         */
        if ($state === null || $state === '') {

            $set('payment_secondary_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
         * Get selected customer
         */
        $customerId = $get('dr_account');

        if (! $customerId) {

            $set('payment_secondary_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
         * Get Secondary Currency
         * currency_type = 2
         */
        $secondaryCurrency = Country::query()
            ->where('currency_type', 2)
            ->first();

        if (! $secondaryCurrency) {

            $set('payment_secondary_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
         * Get Customer Rate
         *
         * Match BOTH:
         * customer_id
         * currency_id
         */
        $customerRate = CustomerRate::query()
            ->where('customer_id', $customerId)
            ->where('currency_id', $secondaryCurrency->id)
            ->first();

        if (! $customerRate) {

            $set('payment_secondary_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
         * MASTER -> SECONDARY
         *
         * Example:
         *
         * Master Payment = 100
         * master_to_general = 120
         *
         * Secondary Payment = 100 * 120
         *                    = 12,000
         */
        $rate = (float) $customerRate->master_to_general;

        if ($rate <= 0) {

            $set('payment_secondary_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        $masterAmount = (float) $state;

        $secondaryAmount =
            $masterAmount * $rate;

        /*
         * Set converted Secondary amount
         */
        $set(
            'payment_secondary_currency',
            number_format(
                $secondaryAmount,
                2,
                '.',
                ''
            )
        );

        /*
         * Recalculate BOTH final dues
         */
        self::calculateFinalDues($set, $get);
    })
    ->dehydrated(true),
 


/*
 * ==========================================================
 * PAYMENT SECONDARY CURRENCY
 * ==========================================================
 */
TextInput::make('payment_secondary_currency')
    ->label('Payment Secondary Currency')
    ->numeric()
    ->inputMode('decimal')
    ->step('0.01')
    ->default(null)
    ->live()
    ->disabled(
        fn (Get $get): bool =>
            $get('payment_currency') !== 'secondary'
    )
    
    ->afterStateUpdated(function (
        Set $set,
        Get $get,
        $state
    ): void {

        if ($get('payment_currency') !== 'secondary') {
            return;
        }

        if ($state === null || $state === '') {
            $set('payment_master_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        $customerId = $get('dr_account');

        if (! $customerId) {
            $set('payment_master_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
        * Get Secondary Currency
        */
        $secondaryCurrency = Country::query()
            ->where('currency_type', 2)
            ->first();

        if (! $secondaryCurrency) {
            $set('payment_master_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
        * Get Customer Rate
        */
        $customerRate = CustomerRate::query()
            ->where('customer_id', $customerId)
            ->where('currency_id', $secondaryCurrency->id)
            ->first();

        if (! $customerRate) {
            $set('payment_master_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        /*
        * Secondary -> Master
        */
        $rate = (float) $customerRate->general_to_master;

        if ($rate <= 0) {
            $set('payment_master_currency', null);

            self::calculateFinalDues($set, $get);

            return;
        }

        $secondaryAmount = (float) $state;

        $masterAmount =
            $secondaryAmount * $rate;

        $set(
            'payment_master_currency',
            number_format(
                $masterAmount,
                2,
                '.',
                ''
            )
        );

        /*
        * Recalculate BOTH final dues
        */
        self::calculateFinalDues($set, $get);
    })

    ->dehydrated(true),
 
 
 
/*
 * ==========================================================
 * MASTER ADJUSTMENT AMOUNT
 * ==========================================================
 */
TextInput::make('master_adjustment_amount')
    ->label('Master Adjustment Amount')
    ->numeric()
    ->inputMode('decimal')
    ->step('0.01')
    ->default(0)
    ->live()
    ->afterStateUpdated(function (
        Set $set,
        Get $get
    ): void {

        $masterDue = (float) ($get('customer_master_due') ?? 0);
        $payment = (float) ($get('payment_master_currency') ?? 0);
        $adjustment = (float) ($get('master_adjustment_amount') ?? 0);

        $finalDue =
            $masterDue
            - $payment
            + $adjustment;

        $set(
            'master_currency_due_after_adjustment',
            number_format($finalDue, 2, '.', '')
        );
    })
    ->dehydrated(true),


 
 


 
/*
 * ==========================================================
 * SECONDARY ADJUSTMENT AMOUNT
 * ==========================================================
 */
TextInput::make('secondary_adjustment_amount')
    ->label('Secondary Adjustment Amount')
    ->numeric()
    ->inputMode('decimal')
    ->step('0.01')
    ->default(0)
    ->live()

    ->afterStateUpdated(function (
        Set $set,
        Get $get
    ): void {

        $secondaryDue = (float) ($get('customer_secondary_due') ?? 0);
        $payment = (float) ($get('payment_secondary_currency') ?? 0);
        $adjustment = (float) ($get('secondary_adjustment_amount') ?? 0);

        $finalDue =
            $secondaryDue
            - $payment
            + $adjustment;

        $set(
            'secondary_currency_due_after_adjustment',
            number_format($finalDue, 2, '.', '')
        );
    })
    ->dehydrated(true),


 
 

/*
 * ==========================================================
 * MASTER CURRENCY DUE
 * ==========================================================
 */
TextInput::make('master_currency_due_after_adjustment')
    ->label('Final Master Due')
    ->numeric()
    ->readOnly()
    ->default(0)
    ->dehydrated(false),


/*
 * ==========================================================
 * SECONDARY CURRENCY DUE
 * ==========================================================
 */
TextInput::make('secondary_currency_due_after_adjustment')
    ->label('Final Secondary Due')
    ->numeric()
    ->readOnly()
    ->default(0)
    ->dehydrated(false),
 







                        Section::make('Vendor Recent Transactions')
                            ->description('Last 10 sales journal records for the selected vendor.')
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
                                           // ->where('type_id', self::TYPE_RECEIVE)
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
                                            ->limit(10)
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
                        self::TYPE_RECEIVE
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

            'index' =>
                ListReceiveJournals::route('/'),

            'create' =>
                CreateReceiveJournal::route('/create'),

 

        ];
    }
}