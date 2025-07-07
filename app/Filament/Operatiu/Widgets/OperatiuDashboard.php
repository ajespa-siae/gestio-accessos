<?php

namespace App\Filament\Operatiu\Widgets;

use App\Models\SolicitudAcces;
use App\Models\ChecklistTask;
use App\Models\Validacio;
use App\Models\Empleat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OperatiuDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $stats = [];

        // Widgets comunes para todos los roles
        $stats[] = Stat::make('Total Empleats', Empleat::count())
            ->description('Total d\'empleats a l\'empresa')
            ->descriptionIcon('heroicon-o-users')
            ->color('primary');

        // Widgets específicos por rol
        if ($user->hasRole('admin') || $user->hasRole('rrhh')) {
            $stats = array_merge($stats, $this->getRrhhStats($user));
        }

        if ($user->hasRole('admin') || $user->hasRole('it')) {
            $stats = array_merge($stats, $this->getItStats($user));
        }

        if ($user->hasRole('admin') || $user->hasRole('gestor')) {
            $stats = array_merge($stats, $this->getGestorStats($user));
        }

        return $stats;
    }

    protected function getRrhhStats($user): array
    {
        return [
            Stat::make('Altes Pendent', Empleat::where('data_alta', '>', now())
                ->where('estat', 'pendent')
                ->count())
                ->description('Altes d\'empleats pendents')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Baixes Proper Mes', Empleat::where('data_baixa', '>=', now())
                ->where('data_baixa', '<=', now()->addMonth())
                ->count())
                ->description('Baixes programades proper mes')
                ->descriptionIcon('heroicon-o-arrow-down')
                ->color('danger'),
        ];
    }

    protected function getItStats($user): array
    {
        $userId = $user->id;
        
        return [
            Stat::make('Tasques Pendents', ChecklistTask::where('completada', false)
                ->when(!$user->hasRole('admin'), function($query) use ($userId) {
                    return $query->where('usuari_assignat_id', $userId);
                })
                ->count())
                ->description('Tasques pendents de completar')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('warning'),

            Stat::make('Tasques Avui', ChecklistTask::whereDate('data_limit', today())
                ->where('completada', false)
                ->when(!$user->hasRole('admin'), function($query) use ($userId) {
                    return $query->where('usuari_assignat_id', $userId);
                })
                ->count())
                ->description('Tasques amb venciment avui')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    protected function getGestorStats($user): array
    {
        $userId = $user->id;
        
        return [
            Stat::make('Sol·licituds Pendents', SolicitudAcces::where('estat', 'pendent')
                ->when(!$user->hasRole('admin'), function($query) use ($userId) {
                    return $query->where('usuari_solicitant_id', $userId)
                        ->orWhereHas('empleatDestinatari.departament.gestors', function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        });
                })
                ->count())
                ->description('Sol·licituds pendents de revisió')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('Validacions Pendents', Validacio::where('estat', 'pendent')
                ->where(function($query) use ($userId) {
                    $query->where('validador_id', $userId)
                        ->orWhereJsonContains('grup_validadors_ids', (string) $userId);
                })
                ->count())
                ->description('Validacions pendents')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('danger'),
        ];
    }
}
