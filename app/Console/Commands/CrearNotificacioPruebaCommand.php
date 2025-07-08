<?php

namespace App\Console\Commands;

use App\Models\Notificacio;
use App\Models\User;
use Illuminate\Console\Command;

class CrearNotificacioPruebaCommand extends Command
{
    protected $signature = 'notificacions:crear-prova {user_id} {tipus=info}';
    protected $description = 'Crea una notificació de prova per a un usuari específic';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $tipus = $this->argument('tipus');
        
        // Verificar que el usuario existe
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("No s'ha trobat cap usuari amb ID {$userId}");
            return 1;
        }
        
        // Crear la notificación
        $notificacio = Notificacio::crear(
            $userId,
            'Notificació de prova',
            'Aquesta és una notificació de prova creada per comprovar el funcionament del sistema de notificacions.',
            $tipus,
            route('filament.operatiu.pages.dashboard'),
            'prova-' . now()->timestamp
        );
        
        $this->info("S'ha creat una notificació de prova per a l'usuari {$user->name} (ID: {$userId})");
        $this->table(
            ['ID', 'Títol', 'Tipus', 'Creada'],
            [[$notificacio->id, $notificacio->titol, $notificacio->tipus, $notificacio->created_at]]
        );
        
        return 0;
    }
}
