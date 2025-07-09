<?php

/**
 * Script para reiniciar las colas de Laravel
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

// Ejecutar el comando queue:restart
$status = $kernel->call('queue:restart');

// Registrar la ejecución en el log
if ($status === 0) {
    $app->make('log')->info('Colas reiniciadas correctamente');
} else {
    $app->make('log')->error('Error al reiniciar colas: código ' . $status);
}

// Terminar la aplicación
$kernel->terminate(null, $status);
