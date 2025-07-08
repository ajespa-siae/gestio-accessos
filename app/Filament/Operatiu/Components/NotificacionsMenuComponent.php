<?php

namespace App\Filament\Operatiu\Components;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;

class NotificacionsMenuComponent
{
    public function render(): View
    {
        return Blade::render('@livewire(\'App\Filament\Operatiu\Livewire\NotificacionsMenu\')');
    }
}
