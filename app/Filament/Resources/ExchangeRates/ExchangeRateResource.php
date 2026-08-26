<?php

namespace App\Filament\Resources\ExchangeRates;

use App\Filament\Resources\ExchangeRates\Pages\ListExchangeRates;
use App\Models\Country;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

use UnitEnum;


class ExchangeRateResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static UnitEnum|string|null $navigationGroup = 'Parameters';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Exchange Rate';

    protected static ?string $modelLabel = 'Exchange Rate';

    protected static ?string $pluralModelLabel = 'Exchange Rates';

    protected static ?string $slug = 'exchange-rate';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrows-right-left';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }


    public static function table(Table $table): Table
    {
        return $table

            ->query(
                Country::query()
                    ->where('inactive', 0)
                    ->orderBy('currency_type', 'desc')
            )

            ->columns([

                TextColumn::make('id')
                    ->label('SL')
                    ->rowIndex()
                    ->sortable(),

/*
                TextColumn::make('name')
                    ->label('Currency')
                    ->searchable()
                    ->sortable(),
*/

                ImageColumn::make('img')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->getStateUsing(function (Country $record) {

                        return 'country/' .
                            strtolower($record->prefix) .
                            '.png';

                    }),

/*
                TextColumn::make('prefix')
                    ->label('Prefix'),
*/

                TextColumn::make('currency_type')
                    ->label('Type')
                    ->formatStateUsing(function ($state) {

                        return match ((int) $state) {

                            3 => 'Master',

                            2 => 'Secondary',

                            1 => 'General',

                            default => 'General',

                        };

                    })
                    ->badge(),


                /*
                 * MASTER CONVERSION
                 *
                 * 1 Master Currency = X Currency
                 */
                TextColumn::make('master_to_general')
                    ->label('Mst to Gen')
                    ->numeric(
                        decimalPlaces: 5
                    )
                    ->alignRight(),


                /*
                 * GENERAL CONVERSION
                 *
                 * 1 General Currency = X Master Currency
                 */
                TextColumn::make('general_to_master')
                    ->label('Gen to Mst')
                    ->numeric(
                        decimalPlaces: 5
                    )
                    ->alignRight(),


                TextColumn::make('secondary_to_general')
                    ->label('Sec to Gen')
                    ->numeric(
                        decimalPlaces: 5
                    )
                    ->alignRight(),

                TextColumn::make('general_to_secondary')
                    ->label('Gen to Sec')
                    ->numeric(
                        decimalPlaces: 5
                    )
                    ->alignRight(),

            ])


            ->actions([

    /*
     * MASTER CONVERSION
     *
     * 1 Master Currency = X Currency
     */
        Action::make('mstToGen')

        ->label(
            fn (?Country $record): string =>
                (int) ($record?->currency_type ?? 1) === 2
                    ? 'Mst to Sec'
                    : 'Mst to Gen'
        )

        ->icon('heroicon-o-arrow-right')

        ->color('success')

        /*
         * Master currency itself cannot be edited.
         */
        ->disabled(
            fn (Country $record): bool =>
                (int) $record->currency_type === 3
        )

        ->modalHeading(
            fn (Country $record) =>
                'Master Conversion — ' . $record->name
        )

        ->modalDescription(
            fn (Country $record) =>
                '1 Master Currency = how many ' .
                $record->name . '?'
        )

        ->fillForm(
            fn (Country $record): array => [

                'master_to_general' => $record->master_to_general,

            ]
        )

        ->form([

            TextInput::make('master_to_general')

                ->label('1 Master Currency =')

                ->numeric()

                ->required()

                ->minValue(0.00000001)

                ->step('0.00000001')

                ->suffix(
                    fn (Country $record) =>
                        $record->prefix
                ),

        ])

        ->action(
            function (
                Country $record,
                array $data
            ): void {

                $masterRate = (float) $data['master_to_general'];

                if ($masterRate <= 0) {
                    return;
                }

                /*
                 * Master currency itself is always 1 : 1
                 */
                if ((int) $record->currency_type === 3) {

                    $record->update([
                        'master_to_general' => 1,
                        'general_to_master' => 1,
                    ]);

                    return;
                }

                /*
                 * Calculate reciprocal rate.
                 *
                 * Example:
                 *
                 * 150
                 * 1 / 150 = 0.006666...
                 *
                 * Stored as 4 decimal places:
                 * 0.0067
                 */
                $generalRate = round(
                    1 / $masterRate,
                    8
                );

                $record->update([

                    'master_to_general' =>
                        $masterRate,

                    'general_to_master' =>
                        $generalRate,

                ]);

                Notification::make()

                    ->title('Exchange Rate Updated')

                    ->body(
                        '1 Master = ' .
                        $masterRate . ' ' .
                        $record->prefix .
                        ' | 1 ' .
                        $record->prefix .
                        ' = ' .
                        $generalRate .
                        ' Master'
                    )

                    ->success()

                    ->send();
            }
        )

        ->modalSubmitActionLabel(
            'Save Master to General Rate'
        ),


    /*
     * GENERAL CONVERSION
     *
     * 1 General Currency = X Master Currency
     */
        Action::make('genToMst')

 
                ->label(
                    fn (?Country $record): string =>
                        (int) ($record?->currency_type ?? 1) === 2
                            ? 'Sec to Mst'
                            : 'Gen to Mst'
                )

                ->icon('heroicon-o-arrow-left')

                ->color('primary')

                /*
                * Master currency cannot be edited.
                */
                ->disabled(
                    fn (Country $record): bool =>
                        (int) $record->currency_type === 3
                )

    

                ->modalHeading(
                    fn (Country $record) =>
                        'General Conversion — ' . $record->name
                )

                ->modalDescription(
                    fn (Country $record) =>
                        '1 ' .
                        $record->name .
                        ' = how many Master Currency?'
                )

                ->fillForm(
                    fn (Country $record): array => [

                        'general_to_master' => $record->general_to_master,

                    ]
                )

                ->form([

                    TextInput::make('general_to_master')

                        ->label('1 General Currency =')

                        ->numeric()

                        ->required()

                        ->minValue(0.00000001)

                        ->step('0.00000001')

                        ->suffix('Master'),

                ])

                ->action(
                    function (
                        Country $record,
                        array $data
                    ): void {

                        $generalRate = (float) $data['general_to_master'];

                        if ($generalRate <= 0) {
                            return;
                        }

                        /*
                        * Master currency is always 1 : 1
                        */
                        if ((int) $record->currency_type === 3) {

                            $record->update([
                                'master_to_general' => 1,
                                'general_to_master' => 1,
                            ]);

                            return;
                        }

                        /*
                        * Reciprocal:
                        *
                        * general_to_master = 0.0066
                        *
                        * master_to_general =
                        * 1 / 0.0066
                        */
                        $masterRate = round(
                            1 / $generalRate,
                            8
                        );

                        $record->update([

                            'general_to_master' =>
                                $generalRate,

                            'master_to_general' =>
                                $masterRate,

                        ]);

                        Notification::make()

                            ->title('Exchange Rate Updated')

                            ->body(
                                '1 ' .
                                $record->prefix .
                                ' = ' .
                                $generalRate .
                                ' Master | 1 Master = ' .
                                $masterRate .
                                ' ' .
                                $record->prefix
                            )

                            ->success()

                            ->send();
                    }
                )

                ->modalSubmitActionLabel(
                    'Save General to Master Rate'
                ),

        
            /*
     * SECONDARY CONVERSION
     *
     * 1 Secondary Currency = X Currency
     */
        Action::make('secToGen')

        ->label('Sec to Gen')

        ->icon('heroicon-o-arrow-right')

        ->color('success')

        /*
         * Secondary currency itself cannot be edited.
         */
        ->disabled(fn (Country $record) =>
            in_array((int) $record->currency_type, [2, 3])
        )

        ->modalHeading(
            fn (Country $record) =>
                'Secondary Conversion — ' . $record->name
        )

        ->modalDescription(
            fn (Country $record) =>
                '1 Secondary Currency = how many ' .
                $record->name . '?'
        )

        ->fillForm(
            fn (Country $record): array => [

                'secondary_to_general' => $record->secondary_to_general,

            ]
        )

        ->form([

            TextInput::make('secondary_to_general')

                ->label('1 Secondary Currency =')

                ->numeric()

                ->required()

                ->minValue(0.00000001)

                ->step('0.00000001')

                ->suffix(
                    fn (Country $record) =>
                        $record->prefix
                ),

        ])

        ->action(
            function (
                Country $record,
                array $data
            ): void {

                $secondaryRate = (float) $data['secondary_to_general'];

                if ($secondaryRate <= 0) {
                    return;
                }

                /*
                 * Secondary currency itself is always 1 : 1
                 */
                if ((int) $record->currency_type === 3) {

                    $record->update([
                        'secondary_to_general' => 1,
                        'general_to_secondary' => 1,
                    ]);

                    return;
                }

                /*
                 * Calculate reciprocal rate.
                 *
                 * Example:
                 *
                 * 150
                 * 1 / 150 = 0.006666...
                 *
                 * Stored as 4 decimal places:
                 * 0.0067
                 */
                $generalRate = round(
                    1 / $secondaryRate,
                    8
                );

                $record->update([

                    'secondary_to_general' =>
                        $secondaryRate,

                    'general_to_secondary' =>
                        $generalRate,

                ]);

                Notification::make()

                    ->title('Exchange Rate Updated')

                    ->body(
                        '1 Secondary = ' .
                        $secondaryRate . ' ' .
                        $record->prefix .
                        ' | 1 ' .
                        $record->prefix .
                        ' = ' .
                        $generalRate .
                        ' Secondary'
                    )

                    ->success()

                    ->send();
            }
        )

        ->modalSubmitActionLabel(
            'Save Secondary to General Rate'
        ),


    /*
     * GENERAL CONVERSION
     *
     * 1 General Currency = X Secondary Currency
     */
        Action::make('genToSec')

                ->label('Gen to Sec')

                ->icon('heroicon-o-arrow-left')

                ->color('primary')

                /*
                * Secondary currency cannot be edited.
                */
                ->disabled(fn (Country $record) =>
                        in_array((int) $record->currency_type, [2, 3])
                    )

                ->modalHeading(
                    fn (Country $record) =>
                        'General Conversion — ' . $record->name
                )

                ->modalDescription(
                    fn (Country $record) =>
                        '1 ' .
                        $record->name .
                        ' = how many Secondary Currency?'
                )

                ->fillForm(
                    fn (Country $record): array => [

                        'general_to_secondary' => $record->general_to_secondary,

                    ]
                )

                ->form([

                    TextInput::make('general_to_secondary')

                        ->label('1 General Currency =')

                        ->numeric()

                        ->required()

                        ->minValue(0.00000001)

                        ->step('0.00000001')

                        ->suffix('Secondary'),

                ])

                ->action(
                    function (
                        Country $record,
                        array $data
                    ): void {

                        $generalRate = (float) $data['general_to_secondary'];

                        if ($generalRate <= 0) {
                            return;
                        }

                        /*
                        * Secondary currency is always 1 : 1
                        */
                        if ((int) $record->currency_type === 3) {

                            $record->update([
                                'secondary_to_general' => 1,
                                'general_to_secondary' => 1,
                            ]);

                            return;
                        }

                        /*
                        * Reciprocal:
                        *
                        * general_to_secondary = 0.0066
                        *
                        * secondary_to_general =
                        * 1 / 0.0066
                        */
                        $secondaryRate = round(
                            1 / $generalRate,
                            8
                        );

                        $record->update([

                            'general_to_secondary' =>
                                $generalRate,

                            'secondary_to_general' =>
                                $secondaryRate,

                        ]);

                        Notification::make()

                            ->title('Exchange Rate Updated')

                            ->body(
                                '1 ' .
                                $record->prefix .
                                ' = ' .
                                $generalRate .
                                ' Secondary | 1 Secondary = ' .
                                $secondaryRate .
                                ' ' .
                                $record->prefix
                            )

                            ->success()

                            ->send();
                    }
                )

                ->modalSubmitActionLabel(
                    'Save General to Secondary Rate'
                ),

        
        

           

                
        
        


        
        ]);

    }


    public static function getPages(): array
    {
        return [

            'index' =>
                ListExchangeRates::route('/'),

        ];
    }
}