<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountSummaryWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-home';
    }

    public function getWidgets(): array
    {
        return [
            AccountSummaryWidget::class,
        ];
    }
}