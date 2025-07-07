<?php

namespace App\Filament\Operatiu\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    // Aquí podemos personalizar el dashboard si es necesario
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inici';
    protected static ?string $navigationGroup = null;
    protected static ?int $navigationSort = -2;
}
