<?php

/**
 * Script para procesar los jobs pendientes en la cola
 * Para uso en cron jobs de CloudPanel
 */

// Definir la ruta base de la aplicación
define('BASEPATH', dirname(__DIR__));

// Cargar el autoloader de Composer
require BASEPATH . '/vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once BASEPATH . '/bootstrap/app.php';

// Obtener el Kernel de Artisan
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Ejecutar el comando queue:work con las opciones adecuadas
$status = $kernel->call('queue:work', [
    '--stop-when-empty' => true,
    '--max-jobs' => 10,
    '--max-time' => 290,
]);

// Registrar la ejecución en el log
if ($status === 0) {
    $app->make('log')->info('Cron de procesamiento de colas ejecutado correctamente');
} else {
    $app->make('log')->error('Error en el cron de procesamiento de colas: código ' . $status);
}

// Terminar la aplicación
$kernel->terminate(null, $status);
