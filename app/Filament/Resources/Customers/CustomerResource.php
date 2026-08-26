<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;

use App\Models\Account;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static UnitEnum|string|null $navigationGroup = 'Business Partners';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Customer';

    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    protected static ?string $slug = 'Customer';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Customer Information')
                ->description(
                    'Basic information about your customer.'
                )
                ->icon('heroicon-o-truck')
                ->columns(2)
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
                        ->rows(4)
                        ->columnSpanFull(),

                ]),


            Section::make('Customer Logo')
                ->description(
                    'Upload customer logo.'
                )
                ->icon('heroicon-o-photo')
                ->schema([

                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->directory('logo')
                        ->imageEditor()
                        ->imagePreviewHeight('150')
                        ->maxSize(2048)
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/svg+xml',
                        ])
                        ->helperText(
                            'JPG, PNG, WEBP or SVG. Maximum 2MB.'
                        ),

                ]),

        ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Account::query()
                    ->where(
                        'type_id',
                        Account::TYPE_CUSTOMER
                    )
            )

            ->columns([

                TextColumn::make('id')
                    ->label('SL')
                    ->rowIndex()
                    ->sortable(),

                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),

                TextColumn::make('name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('website')
                    ->label('Website'),

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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
