<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MonitorearColas extends Command
{
    protected $signature = 'colas:monitorear {--intervalo=5 : Intervalo de actualización en segundos}';
    protected $description = 'Monitorea la actividad de las colas en tiempo real';

    protected $previousJobCount = 0;
    protected $previousFailedCount = 0;

    public function handle()
    {
        $intervalo = (int) $this->option('intervalo');
        
        $this->info('Iniciando monitoreo de colas en tiempo real...');
        $this->info('Presiona Ctrl+C para detener el monitoreo.');
        $this->newLine();
        
        while (true) {
            $this->output->write("\033[H\033[2J"); // Limpia la pantalla
            
            $this->mostrarEncabezado();
            $this->mostrarEstadoColas();
            $this->mostrarJobsPendientes();
            $this->mostrarJobsFallidos();
            $this->mostrarWorkers();
            
            sleep($intervalo);
        }
    }
    
    protected function mostrarEncabezado()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $this->info("=== MONITOR DE COLAS - $now ===");
        $this->newLine();
    }
    
    protected function mostrarEstadoColas()
    {
        $queueConnection = config('queue.default');
        $this->info("Conexión de cola: <fg=yellow>$queueConnection</>");
        
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        // Detectar cambios
        $jobsDiff = $pendingJobs - $this->previousJobCount;
        $failedDiff = $failedJobs - $this->previousFailedCount;
        
        $jobsStatus = $jobsDiff > 0 ? "<fg=red>+$jobsDiff</>" : ($jobsDiff < 0 ? "<fg=green>$jobsDiff</>" : "");
        $failedStatus = $failedDiff > 0 ? "<fg=red>+$failedDiff</>" : "";
        
        $this->info("Jobs pendientes: <fg=yellow>$pendingJobs</> $jobsStatus");
        $this->info("Jobs fallidos: <fg=yellow>$failedJobs</> $failedStatus");
        
        $this->previousJobCount = $pendingJobs;
        $this->previousFailedCount = $failedJobs;
        
        $this->newLine();
    }
    
    protected function mostrarJobsPendientes()
    {
        $jobs = DB::table('jobs')
            ->select('queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($jobs->isEmpty()) {
            $this->info("No hay jobs pendientes en la cola.");
        } else {
            $this->info("<fg=yellow>ÚLTIMOS JOBS PENDIENTES:</>");
            
            $headers = ['Cola', 'Job', 'Intentos', 'Creado', 'Estado'];
            $rows = [];
            
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? 'Desconocido';
                
                $estado = $job->reserved_at 
                    ? "<fg=blue>En proceso</>" 
                    : (Carbon::createFromTimestamp($job->available_at)->isPast() 
                        ? "<fg=green>Disponible</>" 
                        : "<fg=yellow>Programado</>");
                
                $rows[] = [
                    $job->queue,
                    $jobName,
                    $job->attempts,
                    Carbon::parse($job->created_at)->format('Y-m-d H:i:s'),
                    $estado
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        $this->newLine();
    }
    
    protected function mostrarJobsFallidos()
    {
        $failedJobs = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get();
            
        if ($failedJobs->isEmpty()) {
            $this->info("No hay jobs fallidos.");
        } else {
            $this->info("<fg=red>ÚLTIMOS JOBS FALLIDOS:</>");
            
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? 'Desconocido';
                
                $this->info("<fg=yellow>ID:</> {$job->id} | <fg=yellow>Job:</> $jobName | <fg=yellow>Falló:</> {$job->failed_at}");
                $this->info("<fg=yellow>Error:</> " . substr($job->exception, 0, 150) . "...");
                $this->newLine();
            }
        }
    }
    
    protected function mostrarWorkers()
    {
        $result = shell_exec('ps aux | grep "queue:work\|queue:listen" | grep -v grep');
        
        if ($result) {
            $this->info("<fg=green>WORKERS ACTIVOS:</>");
            $this->line($result);
        } else {
            $this->warn("No hay workers en ejecución. Los jobs no serán procesados.");
            $this->info("Ejecuta: <fg=yellow>php artisan queue:work</> para iniciar un worker.");
        }
        
        $this->newLine();
    }
}
