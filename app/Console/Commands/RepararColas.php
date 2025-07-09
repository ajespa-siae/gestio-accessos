<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class RepararColas extends Command
{
    protected $signature = 'colas:reparar {--reintentar-fallidos : Reintenta los jobs fallidos}';
    protected $description = 'Repara problemas comunes con las colas y los jobs';

    public function handle()
    {
        $this->info('Iniciando reparación de colas...');
        
        // 1. Verificar si existen las tablas necesarias
        $this->info('Verificando tablas de colas...');
        
        if (!Schema::hasTable('jobs')) {
            $this->warn('La tabla "jobs" no existe. Creándola...');
            Artisan::call('queue:table');
            $this->info('Comando queue:table ejecutado.');
        }
        
        if (!Schema::hasTable('failed_jobs')) {
            $this->warn('La tabla "failed_jobs" no existe. Creándola...');
            Artisan::call('queue:failed-table');
            $this->info('Comando queue:failed-table ejecutado.');
        }
        
        // 2. Ejecutar migraciones pendientes
        $this->info('Ejecutando migraciones pendientes...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('Migraciones completadas.');
        
        // 3. Limpiar caché
        $this->info('Limpiando caché...');
        Artisan::call('optimize:clear');
        $this->info('Caché limpiada.');
        
        // 4. Reiniciar jobs fallidos si se solicita
        if ($this->option('reintentar-fallidos')) {
            $this->info('Reintentando jobs fallidos...');
            $count = DB::table('failed_jobs')->count();
            
            if ($count > 0) {
                Artisan::call('queue:retry all');
                $this->info("Se han programado $count jobs fallidos para reintentar.");
            } else {
                $this->info('No hay jobs fallidos para reintentar.');
            }
        }
        
        // 5. Verificar si hay workers en ejecución
        $this->info('Verificando workers...');
        $result = shell_exec('ps aux | grep "queue:work\|queue:listen" | grep -v grep');
        
        if (!$result) {
            $this->warn('No se detectaron workers en ejecución.');
            $this->info('Para iniciar un worker, ejecuta: php artisan queue:work');
            $this->info('En producción, considera usar un supervisor como systemd o supervisord.');
            
            if ($this->confirm('¿Deseas iniciar un worker en segundo plano ahora?')) {
                $this->info('Iniciando worker en segundo plano...');
                shell_exec('nohup php artisan queue:work --tries=3 > /dev/null 2>&1 &');
                $this->info('Worker iniciado. Recuerda que este worker se detendrá si cierras la sesión.');
            }
        } else {
            $this->info('Workers en ejecución detectados:');
            $this->info($result);
        }
        
        $this->info('Reparación de colas completada.');
    }
}
