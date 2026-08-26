<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\ManageCompany;
use App\Models\Account;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

use Filament\Schemas\Components\Section;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static UnitEnum|string|null $navigationGroup = 'Business Partners';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Company';

    protected static ?string $modelLabel = 'Company';

    protected static ?string $pluralModelLabel = 'Company';

    protected static ?string $slug = 'company';


    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            /*
             * COMPANY INFORMATION
             */
            Section::make('Company Information')
                ->description(
                    'Basic information about your company.'
                )
                ->icon('heroicon-o-building-office')
                ->columns(2)
                ->schema([

                    TextInput::make('name')
                        ->label('Company Name')
                        ->placeholder('Enter company name')
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
                        ->placeholder('company@example.com')
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
                        ->placeholder('Enter company address')
                        ->rows(4)
                        ->columnSpanFull(),

                ]),


            /*
             * COMPANY LOGO
             */
            Section::make('Company Logo')
                ->description(
                    'Upload your company logo.'
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


    public static function getPages(): array
    {
        return [
            'index' =>
                ManageCompany::route('/'),
        ];
    }
}