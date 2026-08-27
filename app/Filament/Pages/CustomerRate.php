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

use UnitEnum;

class CustomerRate extends Page
{
    protected static ?string $navigationLabel =
        'Customer Rate';

    protected static ?string $title =
        'Customer Rate';

    protected static UnitEnum|string|null $navigationGroup =
        'Parameters';

    protected static ?int $navigationSort =
        10;

    protected static ?string $slug =
        'customer-rate';

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-banknotes';

    protected string $view =
        'filament.pages.customer-rate';

    public ?array $data = [];


    public function mount(): void
    {
        $this->form->fill([
            'customer_id' => null,
            'rates' => [],
        ]);
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                 * CUSTOMER
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
                        function ($state): void {
                            $this->loadCustomerRates(
                                $state
                            );
                        }
                    )
                    ->columnSpanFull(),


                /*
                 * CUSTOMER CURRENCY RATES
                 */
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
 
    protected function loadCustomerRates(
        mixed $customerId
    ): void {

        if (! $customerId) {

            $this->form->fill([
                'customer_id' => null,
                'rates' => [],
            ]);

            return;
        }


        /*
        * GET ONLY GENERAL CURRENCIES
        */
        $currencies = Country::query()
            ->where('inactive', 0)
            ->where('currency_type', 1)
            ->orderBy('name')
            ->get();


        /*
        * GET MASTER AND SECONDARY CURRENCY LABELS
        */
        $currencyLabels = $this->getRateCurrencyLabels();

        $masterCode = $currencyLabels['master'];

        $secondaryCode = $currencyLabels['secondary'];


        /*
        * PREPARE RATES
        */
        $rates = [];


        /*
        * LOOP THROUGH ALL GENERAL CURRENCIES
        */
        foreach ($currencies as $currency) {

            /*
            * GET EXISTING CUSTOMER RATE
            */
            $customerRate = CustomerRateModel::query()
                ->where(
                    'customer_id',
                    $customerId
                )
                ->where(
                    'currency_id',
                    $currency->id
                )
                ->first();


            /*
            * ADD RATE ROW
            */
            $rates[] = [

                'currency_id' => $currency->id,

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
                * CUSTOMER RATE
                * OR DEFAULT COUNTRY RATE
                */
                'general_to_master' =>
                    $customerRate?->general_to_master
                    ?? $currency->general_to_master,

                'master_to_general' =>
                    $customerRate?->master_to_general
                    ?? $currency->master_to_general,

                'general_to_secondary' =>
                    $customerRate?->general_to_secondary
                    ?? $currency->general_to_secondary,

                'secondary_to_general' =>
                    $customerRate?->secondary_to_general
                    ?? $currency->secondary_to_general,

            ];
        }


        /*
        * REFILL FORM
        */
        $this->form->fill([
            'customer_id' => $customerId,
            'rates' => $rates,
        ]);
    }


    /*
     * SAVE CUSTOMER RATES
     */
    public function save(): void
    {
        $data = $this->form->getState();

        $customerId = $data['customer_id'];

        foreach ($data['rates'] as $rate) {

            /*
             * IMPORTANT:
             * Again use CustomerRateModel.
             */
            CustomerRateModel::updateOrCreate(

                [
                    'customer_id' => $customerId,
                    'currency_id' => $rate['currency_id'],
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


        Notification::make()
            ->title(
                'Customer rates saved successfully.'
            )
            ->success()
            ->send();
    }


    public function getHeaderActions(): array
    {
        return [

            Action::make('applyAllRates')
                ->label('Apply All Customer Rates')
                ->icon('heroicon-o-users')
                ->color('warning')
                ->modalHeading('Apply Exchange Rates to All Customers')
                ->modalDescription(
                    'These rates will be applied to every customer. Existing customer rates will be updated and missing rates will be created.'
                )
                ->modalSubmitActionLabel('Apply to All Customers')
                ->schema([

                Repeater::make('all_rates')
                    ->label('Exchange Rates')
                    ->schema([

                        Hidden::make('currency_id'),

                        /*
                        * Currency codes for dynamic labels
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

                /*
                * LOAD DEFAULT COUNTRY RATES
                * WHEN MODAL OPENS
                */
                ->mountUsing(
                    function ($form): void {

                        /*
                        * GET GENERAL CURRENCIES
                        */
                        $currencies = Country::query()
                            ->where('inactive', 0)
                            ->where('currency_type', 1)
                            ->orderBy('name')
                            ->get();


                        /*
                        * GET MASTER AND SECONDARY CURRENCY CODES
                        */
                        $currencyLabels = $this->getRateCurrencyLabels();

                        $masterCode = $currencyLabels['master'];

                        $secondaryCode = $currencyLabels['secondary'];


                        /*
                        * INITIALIZE RATES ARRAY
                        */
                        $rates = [];


                        /*
                        * ADD ALL GENERAL CURRENCY RATES
                        */
                        foreach ($currencies as $currency) {

                            $rates[] = [

                                'currency_id' => $currency->id,

                                'currency_name' =>
                                    $currency->name .
                                    ' (' .
                                    $currency->currency .
                                    ')',


                                /*
                                * CURRENCY CODES
                                * USED FOR DYNAMIC LABELS
                                */
                                'general_currency_code' =>
                                    $currency->currency,

                                'master_currency_code' =>
                                    $masterCode,

                                'secondary_currency_code' =>
                                    $secondaryCode,


                                /*
                                * DEFAULT COUNTRY RATES
                                */
                                'general_to_master' =>
                                    $currency->general_to_master,

                                'master_to_general' =>
                                    $currency->master_to_general,

                                'general_to_secondary' =>
                                    $currency->general_to_secondary,

                                'secondary_to_general' =>
                                    $currency->secondary_to_general,

                            ];
                        }


                        /*
                        * FILL MODAL FORM
                        */
                        $form->fill([
                            'all_rates' => $rates,
                        ]);
                    }
                )

                /*
                * APPLY TO ALL CUSTOMERS
                */
                ->action(
                    function (array $data): void {

                        /*
                        * GET ALL CUSTOMERS
                        */
                        $customers = Account::query()
                            ->where(
                                'type_id',
                                Account::TYPE_CUSTOMER
                            )
                            ->get();


                        /*
                        * LOOP THROUGH EACH GENERAL CURRENCY
                        */
                        foreach ($data['all_rates'] as $rate) {

                            /*
                            * UPDATE GLOBAL DEFAULT RATE
                            * IN COUNTRIES TABLE
                            */
                            Country::query()
                                ->where(
                                    'id',
                                    $rate['currency_id']
                                )
                                ->update([

                                    'general_to_master' =>
                                        $rate['general_to_master'],

                                    'master_to_general' =>
                                        $rate['master_to_general'],

                                    'general_to_secondary' =>
                                        $rate['general_to_secondary'],

                                    'secondary_to_general' =>
                                        $rate['secondary_to_general'],

                                ]);


                            /*
                            * APPLY UPDATED RATE
                            * TO EVERY CUSTOMER
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
                                'Default rates updated and applied to all customers successfully.'
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