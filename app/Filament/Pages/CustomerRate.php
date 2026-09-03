<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Country;
use App\Models\CustomerRate as CustomerRateModel;

use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Forms\Components\Hidden;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;

use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use App\Models\AccountRate;

use UnitEnum;

class CustomerRate extends Page
{
    protected static ?string $navigationLabel = 'Customer Rate';

    protected static ?string $title = 'Customer Rate';

    protected static UnitEnum|string|null $navigationGroup = 'Parameters';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug ='customer-rate';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected string $view = 'filament.pages.customer-rate';

    public ?array $data = [];


    public function mount(): void
    {
        $this->loadDefaultRates();
        /*
        $this->form->fill([
            'customer_id' => null,
            'rates' => [],
        ]);
        */
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

              
                    Repeater::make('rates')
                        ->label('Customer Exchange Rates')
                    ->schema([

                        Hidden::make('currency_id'),

                        /*
                        * CURRENCY CODES
                        * Used only for dynamic labels
                        */
                        Hidden::make('general_currency_code')
                            ->dehydrated(false),

                        Hidden::make('master_currency_code')
                            ->dehydrated(false),

                        Hidden::make('secondary_currency_code')
                            ->dehydrated(false),


                        /*
                        * GENERAL CURRENCY
                        */
                        TextInput::make('currency_name')
                            ->label('General Currency')
                            ->disabled()
                            ->dehydrated(false),


                        /*
                        * GENERAL TO MASTER
                        */
                        TextInput::make('general_to_master')
                            ->label(
                                fn (Get $get): string =>
                                    '1 ' .
                                    ($get('general_currency_code') ?? 'GEN') .
                                    ' = ? ' .
                                    ($get('master_currency_code') ?? 'MST')
                            )
                            ->numeric()
                            ->step('0.00000001')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function ($state, Set $set): void {

                                    $rate = (float) $state;

                                    if ($rate > 0) {

                                        $set(
                                            'master_to_general',
                                            number_format(
                                                1 / $rate,
                                                8,
                                                '.',
                                                ''
                                            )
                                        );

                                    } else {

                                        $set(
                                            'master_to_general',
                                            null
                                        );
                                    }
                                }
                            )
                            ->required(),


                        /*
                        * MASTER TO GENERAL
                        */
                        TextInput::make('master_to_general')
                            ->label(
                                fn (Get $get): string =>
                                    '1 ' .
                                    ($get('master_currency_code') ?? 'MST') .
                                    ' = ? ' .
                                    ($get('general_currency_code') ?? 'GEN')
                            )
                            ->numeric()
                            ->step('0.00000001')
                            ->disabled()
                            ->dehydrated()
                            ->required(),


                        /*
                        * GENERAL TO SECONDARY
                        */
                        TextInput::make('general_to_secondary')
                            ->label(
                                fn (Get $get): string =>
                                    '1 ' .
                                    ($get('general_currency_code') ?? 'GEN') .
                                    ' = ? ' .
                                    ($get('secondary_currency_code') ?? 'SEC')
                            )
                            ->numeric()
                            ->step('0.00000001')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function ($state, Set $set): void {

                                    $rate = (float) $state;

                                    if ($rate > 0) {

                                        $set(
                                            'secondary_to_general',
                                            number_format(
                                                1 / $rate,
                                                8,
                                                '.',
                                                ''
                                            )
                                        );

                                    } else {

                                        $set(
                                            'secondary_to_general',
                                            null
                                        );
                                    }
                                }
                            )
                            ->required(),


                        /*
                        * SECONDARY TO GENERAL
                        */
                        TextInput::make('secondary_to_general')
                            ->label(
                                fn (Get $get): string =>
                                    '1 ' .
                                    ($get('secondary_currency_code') ?? 'SEC') .
                                    ' = ? ' .
                                    ($get('general_currency_code') ?? 'GEN')
                            )
                            ->numeric()
                            ->step('0.00000001')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                    ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull(),

            ])
            ->statePath('data');
    }


    /*
     * LOAD CUSTOMER RATES
     */
 
    protected function loadDefaultRates(): void
    {
        $currencies = Country::query()
            ->where('inactive', 0)
            ->whereIn('currency_type', [1, 2])
            ->orderBy('currency_type', 'desc')
            ->get();

        $currencyLabels = $this->getRateCurrencyLabels();

        $masterCode = $currencyLabels['master'];

        $secondaryCode = $currencyLabels['secondary'];

        /*
        * GET SAVED CUSTOMER DEFAULT RATES
        */
        $accountRates = AccountRate::query()
            ->where(
                'type_id',
                Account::TYPE_CUSTOMER
            )
            ->get()
            ->keyBy('currency_id');

        $rates = [];

        foreach ($currencies as $currency) {

            /*
            * GET CUSTOMER DEFAULT RATE
            * IF EXISTS
            */
            $accountRate =
                $accountRates->get(
                    $currency->id
                );

            $rates[] = [

                'currency_id' =>
                    $currency->id,

                'currency_name' =>
                    $currency->name .
                    ' (' .
                    $currency->currency .
                    ')',

                /*
                * DYNAMIC CURRENCY CODES
                */
                'general_currency_code' =>
                    $currency->currency,

                'master_currency_code' =>
                    $masterCode,

                'secondary_currency_code' =>
                    $secondaryCode,

                /*
                * FIRST CHECK ACCOUNT RATES
                *
                * IF NO CUSTOMER DEFAULT EXISTS,
                * FALL BACK TO COUNTRY RATE
                */
                'general_to_master' =>
                    $accountRate?->general_to_master
                    ?? $currency->general_to_master,

                'master_to_general' =>
                    $accountRate?->master_to_general
                    ?? $currency->master_to_general,

                'general_to_secondary' =>
                    $accountRate?->general_to_secondary
                    ?? $currency->general_to_secondary,

                'secondary_to_general' =>
                    $accountRate?->secondary_to_general
                    ?? $currency->secondary_to_general,

            ];
        }

        $this->form->fill([
            'rates' => $rates,
        ]);
    }


    /*
     * SAVE CUSTOMER RATES
     */
    public function save(): void
    {
        $data = $this->form->getState();

        /*
        * GET ALL CUSTOMERS
        */
        $customers = Account::query()
            ->where(
                'type_id',
                Account::TYPE_CUSTOMER
            )
            ->get();

        foreach ($data['rates'] as $rate) {

            /*
            * SAVE CUSTOMER DEFAULT RATE
            * IN ACCOUNT_RATES
            */
            AccountRate::updateOrCreate(

                [
                    'type_id' =>
                        Account::TYPE_CUSTOMER,

                    'currency_id' =>
                        $rate['currency_id'],
                ],

                [
                    'general_to_master' =>
                        $rate['general_to_master'],

                    'master_to_general' =>
                        $rate['master_to_general'],

                    'general_to_secondary' =>
                        $rate['general_to_secondary'],

                    'secondary_to_general' =>
                        $rate['secondary_to_general'],
                ]

            );


            /*
            * APPLY SAME DEFAULT RATE
            * TO ALL INDIVIDUAL CUSTOMERS
            */
            foreach ($customers as $customer) {

                CustomerRateModel::updateOrCreate(

                    [
                        'customer_id' =>
                            $customer->id,

                        'currency_id' =>
                            $rate['currency_id'],
                    ],

                    [
                        'general_to_master' =>
                            $rate['general_to_master'],

                        'master_to_general' =>
                            $rate['master_to_general'],

                        'general_to_secondary' =>
                            $rate['general_to_secondary'],

                        'secondary_to_general' =>
                            $rate['secondary_to_general'],
                    ]

                );
            }
        }

        Notification::make()
            ->title(
                'Customer default rates updated and applied to all customers successfully.'
            )
            ->success()
            ->send();

        /*
        * RELOAD FROM ACCOUNT_RATES
        */
        $this->loadDefaultRates();
    }


    public function getHeaderActions(): array
    {
        return [

            Action::make('customerRates')
                ->label('Edit Customer Rate')
                ->icon('heroicon-o-user')
                ->color('primary')
                ->modalHeading('Edit Individual Customer Exchange Rates')
                ->modalDescription(
                    'Select a customer and customize exchange rates for that customer only. These changes will not affect the global default rates or other customers.'
                )
                ->modalSubmitActionLabel('Save Customer Rates')

                ->schema([

                    /*
                    * SELECT CUSTOMER
                    */
                    Select::make('customer_id')
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
                        ->required()

                        ->afterStateUpdated(
                            function (
                                $state,
                                Set $set
                            ): void {

                                if (! $state) {

                                    $set(
                                        'customer_rates',
                                        []
                                    );

                                    return;
                                }


                                /*
                                * GET GENERAL CURRENCIES
                                */
                                $currencies = Country::query()
                                    ->where('inactive', 0)
                                    ->whereIn('currency_type', [1, 2])
                                    ->orderBy('currency_type', 'desc')
                                    ->get();


                                /*
                                * GET MASTER AND SECONDARY CODES
                                */
                                $currencyLabels =
                                    $this->getRateCurrencyLabels();

                                $masterCode =
                                    $currencyLabels['master'];

                                $secondaryCode =
                                    $currencyLabels['secondary'];


                                /*
                                * PREPARE CUSTOMER RATES
                                */
                                $rates = [];


                                foreach (
                                    $currencies as $currency
                                ) {

                                    /*
                                    * GET EXISTING
                                    * CUSTOMER-SPECIFIC RATE
                                    */
                                    $customerRate =
                                        CustomerRateModel::query()
                                            ->where(
                                                'customer_id',
                                                $state
                                            )
                                            ->where(
                                                'currency_id',
                                                $currency->id
                                            )
                                            ->first();


                                    $rates[] = [

                                        'currency_id' =>
                                            $currency->id,

                                        'currency_name' =>
                                            $currency->name .
                                            ' (' .
                                            $currency->currency .
                                            ')',

                                        /*
                                        * DYNAMIC LABEL CODES
                                        */
                                        'general_currency_code' =>
                                            $currency->currency,

                                        'master_currency_code' =>
                                            $masterCode,

                                        'secondary_currency_code' =>
                                            $secondaryCode,


                                        /*
                                        * CUSTOMER RATE
                                        *
                                        * FALL BACK TO DEFAULT
                                        * COUNTRY RATE
                                        */
                                        'general_to_master' =>
                                            $customerRate
                                                ?->general_to_master
                                            ?? $currency
                                                ->general_to_master,

                                        'master_to_general' =>
                                            $customerRate
                                                ?->master_to_general
                                            ?? $currency
                                                ->master_to_general,

                                        'general_to_secondary' =>
                                            $customerRate
                                                ?->general_to_secondary
                                            ?? $currency
                                                ->general_to_secondary,

                                        'secondary_to_general' =>
                                            $customerRate
                                                ?->secondary_to_general
                                            ?? $currency
                                                ->secondary_to_general,

                                    ];
                                }


                                /*
                                * FILL CUSTOMER RATES
                                */
                                $set(
                                    'customer_rates',
                                    $rates
                                );
                            }
                        )
                        ->columnSpanFull(),


                    /*
                    * CUSTOMER RATES
                    */
                    Repeater::make('customer_rates')
                        ->label('Customer Exchange Rates')
                        ->schema([

                            Hidden::make('currency_id'),

                            Hidden::make(
                                'general_currency_code'
                            )
                                ->dehydrated(false),

                            Hidden::make(
                                'master_currency_code'
                            )
                                ->dehydrated(false),

                            Hidden::make(
                                'secondary_currency_code'
                            )
                                ->dehydrated(false),


                            /*
                            * GENERAL CURRENCY
                            */
                            TextInput::make('currency_name')
                                ->label('General Currency')
                                ->disabled()
                                ->dehydrated(false),


                            /*
                            * GENERAL TO MASTER
                            */
                            TextInput::make(
                                'general_to_master'
                            )
                                ->label(
                                    fn (Get $get): string =>
                                        '1 ' .
                                        (
                                            $get(
                                                'general_currency_code'
                                            )
                                            ?? 'GEN'
                                        ) .
                                        ' = ? ' .
                                        (
                                            $get(
                                                'master_currency_code'
                                            )
                                            ?? 'MST'
                                        )
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->live(onBlur: true)
                                ->afterStateUpdated(
                                    function (
                                        $state,
                                        Set $set
                                    ): void {

                                        $rate =
                                            (float) $state;

                                        $set(
                                            'master_to_general',
                                            $rate > 0
                                                ? number_format(
                                                    1 / $rate,
                                                    8,
                                                    '.',
                                                    ''
                                                )
                                                : null
                                        );
                                    }
                                )
                                ->required(),


                            /*
                            * MASTER TO GENERAL
                            */
                            TextInput::make(
                                'master_to_general'
                            )
                                ->label(
                                    fn (Get $get): string =>
                                        '1 ' .
                                        (
                                            $get(
                                                'master_currency_code'
                                            )
                                            ?? 'MST'
                                        ) .
                                        ' = ? ' .
                                        (
                                            $get(
                                                'general_currency_code'
                                            )
                                            ?? 'GEN'
                                        )
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->disabled()
                                ->dehydrated()
                                ->required(),


                            /*
                            * GENERAL TO SECONDARY
                            */
                            TextInput::make(
                                'general_to_secondary'
                            )
                                ->label(
                                    fn (Get $get): string =>
                                        '1 ' .
                                        (
                                            $get(
                                                'general_currency_code'
                                            )
                                            ?? 'GEN'
                                        ) .
                                        ' = ? ' .
                                        (
                                            $get(
                                                'secondary_currency_code'
                                            )
                                            ?? 'SEC'
                                        )
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->live(onBlur: true)
                                ->afterStateUpdated(
                                    function (
                                        $state,
                                        Set $set
                                    ): void {

                                        $rate =
                                            (float) $state;

                                        $set(
                                            'secondary_to_general',
                                            $rate > 0
                                                ? number_format(
                                                    1 / $rate,
                                                    8,
                                                    '.',
                                                    ''
                                                )
                                                : null
                                        );
                                    }
                                )
                                ->required(),


                            /*
                            * SECONDARY TO GENERAL
                            */
                            TextInput::make(
                                'secondary_to_general'
                            )
                                ->label(
                                    fn (Get $get): string =>
                                        '1 ' .
                                        (
                                            $get(
                                                'secondary_currency_code'
                                            )
                                            ?? 'SEC'
                                        ) .
                                        ' = ? ' .
                                        (
                                            $get(
                                                'general_currency_code'
                                            )
                                            ?? 'GEN'
                                        )
                                )
                                ->numeric()
                                ->step('0.00000001')
                                ->disabled()
                                ->dehydrated()
                                ->required(),

                        ])
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->visible(
                            fn (Get $get): bool =>
                                filled(
                                    $get('customer_id')
                                )
                        ),

                ])

                /*
                * SAVE INDIVIDUAL CUSTOMER RATE
                */
                ->action(
                    function (
                        array $data
                    ): void {

                        foreach (
                            $data['customer_rates'] ?? []
                            as $rate
                        ) {

                            CustomerRateModel::updateOrCreate(

                                [
                                    'customer_id' =>
                                        $data['customer_id'],

                                    'currency_id' =>
                                        $rate['currency_id'],
                                ],

                                [

                                    'general_to_master' =>
                                        $rate[
                                            'general_to_master'
                                        ],

                                    'master_to_general' =>
                                        $rate[
                                            'master_to_general'
                                        ],

                                    'general_to_secondary' =>
                                        $rate[
                                            'general_to_secondary'
                                        ],

                                    'secondary_to_general' =>
                                        $rate[
                                            'secondary_to_general'
                                        ],

                                ]

                            );
                        }


                        Notification::make()
                            ->title(
                                'Customer rates updated successfully.'
                            )
                            ->success()
                            ->send();
                    }
                ),

        ];
    }

    protected function getRateCurrencyLabels(): array
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
            'master' => $masterCurrency?->currency ?? 'MST',
            'secondary' => $secondaryCurrency?->currency ?? 'SEC',
        ];
    } 
    

}