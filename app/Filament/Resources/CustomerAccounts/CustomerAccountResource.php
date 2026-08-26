<?php

namespace App\Filament\Resources\CustomerAccounts;

use App\Filament\Resources\CustomerAccounts\Pages\CreateCustomerAccount;
use App\Filament\Resources\CustomerAccounts\Pages\EditCustomerAccount;
use App\Filament\Resources\CustomerAccounts\Pages\ListCustomerAccounts;

use App\Models\SubsidiaryAccount;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Account;
use Filament\Forms\Components\Select;

use UnitEnum;

class CustomerAccountResource extends Resource
{
    protected static ?string $model = SubsidiaryAccount::class;

    protected static UnitEnum|string|null $navigationGroup =
        'Business Partners';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationParentItem =
        'Customer';

    protected static ?string $navigationLabel =
        'Customer Account';

    protected static ?string $modelLabel =
        'Customer Account';

    protected static ?string $pluralModelLabel =
        'Customer Accounts';

    protected static ?string $slug =
        'customer-account';


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
                        SubsidiaryAccount::TYPE_CUSTOMER
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
                ListCustomerAccounts::route('/'),

            'create' =>
                CreateCustomerAccount::route('/create'),

            'edit' =>
                EditCustomerAccount::route('/{record}/edit'),

        ];
    }
}