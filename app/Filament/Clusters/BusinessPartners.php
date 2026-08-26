<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use UnitEnum;

class BusinessPartners extends Cluster
{
    protected static UnitEnum|string|null $navigationGroup = 'Business Partners';
    
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-building-office-2';
    }
}