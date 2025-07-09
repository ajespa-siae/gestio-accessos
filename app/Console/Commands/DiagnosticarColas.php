<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnosticarColas extends Command
{
    protected $signature = 'colas:diagnosticar';
    protected $description = 'Diagnostica problemas con las colas y jobs';

    public function handle()
    {
        $this->info('Iniciando diagnóstico de colas y jobs...');
        
        // 1. Verificar configuración de colas
        $this->info('Configuración de colas:');
        $queueConnection = config('queue.default');
        $this->info("- Conexión de cola: $queueConnection");
        
        // 2. Verificar tabla de jobs
        try {
            $pendingJobs = DB::table('jobs')->count();
            $this->info("- Jobs pendientes: $pendingJobs");
            
            if ($pendingJobs > 0) {
                $this->info('Detalles de jobs pendientes:');
                $jobs = DB::table('jobs')
                    ->select('queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    $jobName = $payload['displayName'] ?? 'Desconocido';
                    $this->info("  * Job: $jobName");
                    $this->info("    Cola: {$job->queue}");
                    $this->info("    Intentos: {$job->attempts}");
                    $this->info("    Creado: {$job->created_at}");
                    $this->info("    Disponible: {$job->available_at}");
                    $this->info("    Reservado: " . ($job->reserved_at ? $job->reserved_at : 'No'));
                    $this->info("    ---");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error al verificar tabla de jobs: " . $e->getMessage());
        }
        
        // 3. Verificar tabla de jobs fallidos
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            $this->info("- Jobs fallidos: $failedJobs");
            
            if ($failedJobs > 0) {
                $this->info('Detalles de jobs fallidos:');
                $jobs = DB::table('failed_jobs')
                    ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
                    ->orderBy('failed_at', 'desc')
                    ->limit(5)
                    ->get();
                
                foreach ($jobs as $job) {
                    $payload = json_decode($job->payload, true);
                    $jobName = $payload['displayName'] ?? 'Desconocido';
                    $this->info("  * Job fallido: $jobName (ID: {$job->id})");
                    $this->info("    Cola: {$job->queue}");
                    $this->info("    Falló: {$job->failed_at}");
                    $this->info("    Error: " . substr($job->exception, 0, 150) . "...");
                    $this->info("    ---");
                }
            }
        } catch (\Exception $e) {
            $this->error("Error al verificar tabla de jobs fallidos: " . $e->getMessage());
        }
        
        // 4. Verificar si el worker está en ejecución
        $this->info('Verificando si hay workers en ejecución:');
        $result = shell_exec('ps aux | grep "queue:work\|queue:listen" | grep -v grep');
        if ($result) {
            $this->info("- Workers en ejecución encontrados:");
            $this->info($result);
        } else {
            $this->warn("- No se encontraron workers en ejecución");
            $this->info("  Recuerda que debes ejecutar 'php artisan queue:work' para procesar los jobs");
        }
        
        // 5. Verificar logs recientes
        $this->info('Últimas entradas de log relacionadas con jobs:');
        try {
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                $logContent = shell_exec("tail -n 50 $logPath | grep -i 'job\|queue\|mail\|notific' | tail -n 10");
                $this->info($logContent ?: "- No se encontraron entradas de log relevantes");
            } else {
                $this->warn("- Archivo de log no encontrado en $logPath");
            }
        } catch (\Exception $e) {
            $this->error("Error al leer logs: " . $e->getMessage());
        }
        
        $this->info('Diagnóstico completado.');
    }
}
