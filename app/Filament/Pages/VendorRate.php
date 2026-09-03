<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\Country;
use App\Models\AccountRate;
use App\Models\VendorRate as VendorRateModel;

use BackedEnum;
use UnitEnum;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
 
 

class VendorRate extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Vendor Rate';

    protected static ?string $title = 'Vendor Rate';

    protected static UnitEnum|string|null $navigationGroup = 'Parameters';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'vendor-rate';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected string $view = 'filament.pages.vendor-rate';
 

    public ?array $data = [];


    public function mount(): void
    {
        $this->loadDefaultRates();
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                /*
                 * GLOBAL / DEFAULT VENDOR RATES
                 */
                Repeater::make('rates')
                    ->label('Default Vendor Exchange Rates')
                    ->schema([

                        Hidden::make('currency_id'),

                        /*
                         * CURRENCY CODES
                         * USED FOR DYNAMIC LABELS
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
                                function (
                                    $state,
                                    Set $set
                                ): void {

                                    $rate = (float) $state;

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
                                function (
                                    $state,
                                    Set $set
                                ): void {

                                    $rate = (float) $state;

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
     * LOAD GLOBAL DEFAULT RATES
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
                Account::TYPE_VENDOR
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
     * SAVE GLOBAL DEFAULT RATES
     * AND APPLY TO ALL VENDORS
     */
    public function save(): void
    {
        $data = $this->form->getState();

        $vendors = Account::query()
            ->where(
                'type_id',
                Account::TYPE_VENDOR
            )
            ->get();

        foreach ($data['rates'] as $rate) {

            /*
            * SAVE VENDOR DEFAULT RATE
            */
            AccountRate::updateOrCreate(

                [
                    'type_id' =>
                        Account::TYPE_VENDOR,

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
            * APPLY TO ALL VENDORS
            */
            foreach ($vendors as $vendor) {

                VendorRateModel::updateOrCreate(

                    [
                        'vendor_id' =>
                            $vendor->id,

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
                'Vendor default rates updated and applied to all vendors successfully.'
            )
            ->success()
            ->send();

        $this->loadDefaultRates();
    }


    /*
     * HEADER ACTIONS
     */
    public function getHeaderActions(): array
    {
        return [

            Action::make('vendorRates')
                ->label('Edit Vendor Rate')
                ->icon('heroicon-o-user')
                ->color('primary')
                ->modalHeading(
                    'Edit Individual Vendor Exchange Rates'
                )
                ->modalDescription(
                    'Select a vendor and customize exchange rates for that vendor only. These changes will not affect the global default rates or other vendors.'
                )
                ->modalSubmitActionLabel(
                    'Save Vendor Rates'
                )

                ->schema([

                    /*
                     * SELECT VENDOR
                     */
                    Select::make('vendor_id')
                        ->label('Vendor')
                        ->options(
                            Account::query()
                                ->where(
                                    'type_id',
                                    Account::TYPE_VENDOR
                                )
                                ->orderBy('name')
                                ->pluck(
                                    'name',
                                    'id'
                                )
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

                                /*
                                 * RESET IF NO VENDOR
                                 */
                                if (! $state) {

                                    $set(
                                        'vendor_rates',
                                        []
                                    );

                                    return;
                                }


                                /*
                                 * GET GENERAL CURRENCIES
                                 */
                                $currencies =
                                    Country::query()
                                        ->where(
                                            'inactive',
                                            0
                                        )
                                        ->whereIn('currency_type', [1, 2])
                                        ->orderBy('currency_type', 'desc')
                                        ->get();


                                /*
                                 * GET MASTER AND
                                 * SECONDARY CURRENCY CODES
                                 */
                                $currencyLabels =
                                    $this->getRateCurrencyLabels();

                                $masterCode =
                                    $currencyLabels['master'];

                                $secondaryCode =
                                    $currencyLabels['secondary'];


                                $rates = [];


                                /*
                                 * PREPARE VENDOR RATES
                                 */
                                foreach (
                                    $currencies as $currency
                                ) {

                                    /*
                                     * GET EXISTING
                                     * VENDOR-SPECIFIC RATE
                                     */
                                    $vendorRate =
                                        VendorRateModel::query()
                                            ->where(
                                                'vendor_id',
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
                                         * VENDOR RATE
                                         *
                                         * FALL BACK TO
                                         * DEFAULT COUNTRY RATE
                                         */
                                        'general_to_master' =>
                                            $vendorRate
                                                ?->general_to_master
                                            ?? $currency
                                                ->general_to_master,

                                        'master_to_general' =>
                                            $vendorRate
                                                ?->master_to_general
                                            ?? $currency
                                                ->master_to_general,

                                        'general_to_secondary' =>
                                            $vendorRate
                                                ?->general_to_secondary
                                            ?? $currency
                                                ->general_to_secondary,

                                        'secondary_to_general' =>
                                            $vendorRate
                                                ?->secondary_to_general
                                            ?? $currency
                                                ->secondary_to_general,

                                    ];
                                }


                                /*
                                 * FILL VENDOR RATES
                                 */
                                $set(
                                    'vendor_rates',
                                    $rates
                                );
                            }
                        )
                        ->columnSpanFull(),


                    /*
                     * VENDOR RATES
                     */
                    Repeater::make('vendor_rates')
                        ->label(
                            'Vendor Exchange Rates'
                        )
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
                            TextInput::make(
                                'currency_name'
                            )
                                ->label(
                                    'General Currency'
                                )
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
                                    $get('vendor_id')
                                )
                        ),

                ])


                /*
                 * SAVE INDIVIDUAL VENDOR RATE
                 */
                ->action(
                    function (
                        array $data
                    ): void {

                        foreach (
                            $data['vendor_rates'] ?? []
                            as $rate
                        ) {

                            VendorRateModel::updateOrCreate(

                                [
                                    'vendor_id' =>
                                        $data['vendor_id'],

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
                                'Vendor rates updated successfully.'
                            )
                            ->success()
                            ->send();
                    }
                ),

        ];
    }


    /*
     * GET MASTER AND SECONDARY
     * CURRENCY LABELS
     */
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

            'master' =>
                $masterCurrency?->currency ?? 'MST',

            'secondary' =>
                $secondaryCurrency?->currency ?? 'SEC',

        ];
    }
}