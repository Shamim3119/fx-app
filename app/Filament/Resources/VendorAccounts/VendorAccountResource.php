<?php

namespace App\Filament\Resources\VendorAccounts;

use App\Filament\Resources\VendorAccounts\Pages\CreateVendorAccount;
use App\Filament\Resources\VendorAccounts\Pages\EditVendorAccount;
use App\Filament\Resources\VendorAccounts\Pages\ListVendorAccounts;

use App\Models\SubsidiaryAccount;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Account;
use Filament\Forms\Components\Select;

use UnitEnum;

class VendorAccountResource extends Resource
{
    protected static ?string $model = SubsidiaryAccount::class;

    protected static UnitEnum|string|null $navigationGroup =
        'Business Partners';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationParentItem =
        'Vendor';

    protected static ?string $navigationLabel =
        'Vendor Account';

    protected static ?string $modelLabel =
        'Vendor Account';

    protected static ?string $pluralModelLabel =
        'Vendor Accounts';

    protected static ?string $slug =
        'vendor-account';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-wallet';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Company Account Information')
                ->description(
                    'Create or update a company cash or bank account.'
                )
                ->icon('heroicon-o-building-office')
                ->columns(2)
                ->schema([

                    /*
                    * PARENT COMPANY
                    */
                    Select::make('account_id')
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
                        ->required()
                        ->placeholder('Select company'),

                    /*
                    * SUBSIDIARY ACCOUNT NAME
                    */
                    TextInput::make('name')
                        ->label('Account Name')
                        ->placeholder('Example: Main Cash Account')
                        ->required()
                        ->maxLength(255),

                    /*
                    * CASH / BANK
                    */
                    Radio::make('account_type')
                        ->label('Account Type')
                        ->options([
                            SubsidiaryAccount::ACCOUNT_TYPE_CASH => 'Cash',
                          //  SubsidiaryAccount::ACCOUNT_TYPE_BANK => 'Bank',
                        ])
                        ->default(
                            SubsidiaryAccount::ACCOUNT_TYPE_CASH
                        )
                        ->inline()
                        ->required(),

                ]),

        ]);
    }

        public static function table(Table $table): Table
    {
        return $table

            ->query(
                SubsidiaryAccount::query()
                    ->where(
                        'type_id',
                        SubsidiaryAccount::TYPE_VENDOR
                    )
                    ->with([
                        'account',
                    ])
            )

            ->columns([

                TextColumn::make('id')
                    ->label('SL')
                    ->rowIndex()
                    ->sortable(),

                /*
                * TYPE
                */
                TextColumn::make('type_id')
                    ->label('Type')
                    ->formatStateUsing(
                        fn ($state) => match ((int) $state) {
                            SubsidiaryAccount::TYPE_COMPANY => 'Company',
                            SubsidiaryAccount::TYPE_VENDOR => 'Vendor',
                            SubsidiaryAccount::TYPE_CUSTOMER => 'Customer',
                            default => 'Unknown',
                        }
                    )
                    ->badge(),

                /*
                * PARENT ACCOUNT
                */
                TextColumn::make('account.name')
                    ->label('Parent')
                    ->searchable()
                    ->sortable(),

                /*
                * SUBSIDIARY ACCOUNT NAME
                */
                TextColumn::make('name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable(),

                /*
                * CASH / BANK
                */
                TextColumn::make('account_type')
                    ->label('Account Type')
                    ->formatStateUsing(
                        fn ($state) => match ((int) $state) {
                            SubsidiaryAccount::ACCOUNT_TYPE_CASH => 'Cash',
                        //    SubsidiaryAccount::ACCOUNT_TYPE_BANK => 'Bank',
                            default => 'Unknown',
                        }
                    )
                    ->badge(),

            ])

            ->actions([

                \Filament\Actions\EditAction::make(),

                \Filament\Actions\DeleteAction::make(),

            ])

            ->defaultSort('id', 'desc');
    }


 

    public static function getPages(): array
    {
        return [

            'index' =>
                ListVendorAccounts::route('/'),

            'create' =>
                CreateVendorAccount::route('/create'),

            'edit' =>
                EditVendorAccount::route('/{record}/edit'),

        ];
    }
}