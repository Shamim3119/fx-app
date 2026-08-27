<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Models\Country;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|UnitEnum|null $navigationGroup = 'Parameters';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Currency';

    protected static ?string $modelLabel = 'Currency';

    protected static ?string $pluralModelLabel = 'Currencies';

    protected static ?string $slug = 'currency';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
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
                    ->sortable()
                    ->rowIndex(),
 

                TextColumn::make('currency')
                    ->label('Currency'),
                    
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

/*
                TextColumn::make('code')
                    ->label('Code')
                    ->badge(),
*/
                ImageColumn::make('img')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->getStateUsing(function (Country $record) {
                        // Takes the prefix, converts to lowercase, and appends .png
                        // Assuming the column storing the filename/prefix is 'prefix'
                        return 'country/' . strtolower($record->prefix) . '.png';
                    }),

                TextColumn::make('currency_type')
                    ->label('Currency Type')
                    ->formatStateUsing(function ($state) {

                        return match ((int) $state) {

                            3 => 'Master Currency',

                            2 => 'Secondary Currency',

                            default => 'General Currency',

                        };

                    })
                    ->badge(),

 

            ])

            ->recordActions([

                Action::make('editCurrency')

                    ->label('Edit')

                    ->icon('heroicon-o-pencil-square')

                    ->color('warning')

                    ->schema([

                        Radio::make('currency_type')

                            ->label('Currency Type')

                            ->options([
                                3 => 'Master Currency',
                                2 => 'Secondary Currency',
                                1 => 'General Currency',
                            ])

                            ->required()

                            ->inline()

                            ->default(
                                fn (Country $record) =>
                                    (int) $record->currency_type
                            ),

                    ])

                    ->fillForm(
                        fn (Country $record): array => [
                            'currency_type' => (int) $record->currency_type,
                        ]
                    )

                    ->modalHeading(
                        fn (Country $record) =>
                            'Edit Currency - ' . $record->name
                    )

                    ->modalSubmitActionLabel('Update')

                    ->action(function (
                        Country $record,
                        array $data
                    ) {

                        $type = (int) $data['currency_type'];


                        /*
                         * Master Currency
                         *
                         * Only one Master Currency
                         */
                        if ($type === 3) {

                            Country::query()

                                ->where('currency_type', 3)

                                ->where('id', '!=', $record->id)

                                ->update([
                                    'currency_type' => 1,
                                ]);
                        }


                        /*
                         * Secondary Currency
                         *
                         * Only one Secondary Currency
                         */
                        if ($type === 2) {

                            Country::query()

                                ->where('currency_type', 2)

                                ->where('id', '!=', $record->id)

                                ->update([
                                    'currency_type' => 1,
                                ]);
                        }


                        /*
                         * Update selected currency
                         */
                        $record->update([
                            'currency_type' => $type,
                        ]);


                        Notification::make()

                            ->title('Currency Updated')

                            ->body(
                                $record->name .
                                ' currency type has been updated.'
                            )

                            ->success()

                            ->send();
                    })


                    ->requiresConfirmation(false),


                /*
                 * DELETE / DEACTIVATE
                 */
                Action::make('deleteCurrency')

                    ->label('Delete')

                    ->icon('heroicon-o-trash')

                    ->color('danger')

                    ->requiresConfirmation()

                    ->modalHeading(
                        fn (Country $record) =>
                            'Delete ' . $record->name . '?'
                    )

                    ->modalDescription(
                        'This currency will be removed from the active currency list. The country will not be permanently deleted.'
                    )

                    ->modalSubmitActionLabel('Yes, Delete')

                    ->action(function (Country $record) {

                        /*
                         * Don't delete the country.
                         *
                         * Just make it inactive.
                         */
                        $record->update([
                            'inactive' => 1,
                            'currency_type' => 1,
                        ]);


                        Notification::make()

                            ->title('Currency Deleted')

                            ->body(
                                $record->name .
                                ' has been removed from active currencies.'
                            )

                            ->success()

                            ->send();
                    }),

            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
        ];
    }
}