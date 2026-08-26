<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;

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

class VendorResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static UnitEnum|string|null $navigationGroup = 'Business Partners';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Vendor';

    protected static ?string $modelLabel = 'Vendor';

    protected static ?string $pluralModelLabel = 'Vendors';

    protected static ?string $slug = 'vendor';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-truck';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Vendor Information')
                ->description(
                    'Basic information about your vendor.'
                )
                ->icon('heroicon-o-truck')
                ->columns(2)
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
                        ->rows(4)
                        ->columnSpanFull(),

                ]),


            Section::make('Vendor Logo')
                ->description(
                    'Upload vendor logo.'
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
                        Account::TYPE_VENDOR
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
                    ->label('Vendor Name')
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

            'index' =>
                ListVendors::route('/'),

            'create' =>
                CreateVendor::route('/create'),

            'edit' =>
                EditVendor::route('/{record}/edit'),

        ];
    }
}