<?php
// Script de diagnóstico específico para problemas de PHP y extensiones
header('Content-Type: text/plain');

echo "=== Diagnóstico de PHP y Extensiones ===\n\n";

// Información básica de PHP
echo "Versión de PHP: " . phpversion() . "\n";
echo "Ruta de PHP: " . PHP_BINARY . "\n";
echo "php.ini usado: " . php_ini_loaded_file() . "\n\n";

// Verificar extensiones críticas
echo "=== Extensiones Críticas ===\n";
$extensiones_criticas = [
    'pdo',
    'pdo_pgsql',
    'pgsql',
    'mbstring',
    'xml',
    'ldap',
    'fileinfo',
    'openssl'
];

foreach ($extensiones_criticas as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "CARGADA ✓" : "NO CARGADA ✗") . "\n";
    
    if ($ext === 'pdo_pgsql' && !extension_loaded($ext)) {
        echo "  - Problema detectado con pdo_pgsql. Esto explica el error de conexión a PostgreSQL.\n";
        echo "  - Solución: Ejecutar 'sudo apt-get install php8.4-pgsql' o el equivalente para su sistema.\n";
    }
}

echo "\n";

// Verificar directorios de caché
echo "=== Directorios de Caché ===\n";
$directorios_cache = [
    'bootstrap/cache' => '../bootstrap/cache',
    'storage/framework' => '../storage/framework',
    'storage/framework/cache' => '../storage/framework/cache',
    'storage/framework/sessions' => '../storage/framework/sessions',
    'storage/framework/views' => '../storage/framework/views'
];

foreach ($directorios_cache as $nombre => $ruta) {
    echo "$nombre: ";
    if (file_exists($ruta)) {
        echo "EXISTE ✓";
        echo " (Permisos: " . substr(sprintf('%o', fileperms($ruta)), -4) . ")";
        echo " (Escribible: " . (is_writable($ruta) ? "SÍ ✓" : "NO ✗") . ")";
        
        if (!is_writable($ruta)) {
            echo " - PROBLEMA DETECTADO: El directorio no es escribible.";
            echo " Ejecutar: chmod -R 775 $ruta";
        }
    } else {
        echo "NO EXISTE ✗ - PROBLEMA DETECTADO: Crear el directorio con 'mkdir -p $ruta'";
    }
    echo "\n";
}

echo "\n";

// Verificar configuración de entorno
echo "=== Archivo .env ===\n";
if (file_exists('../.env')) {
    echo "Archivo .env: EXISTE ✓\n";
    
    // Verificar algunas configuraciones críticas sin mostrar valores sensibles
    $env_vars = [
        'APP_ENV',
        'APP_DEBUG',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'CACHE_DRIVER',
        'SESSION_DRIVER',
        'QUEUE_CONNECTION'
    ];
    
    if (function_exists('parse_ini_file')) {
        $env = @parse_ini_file('../.env');
        if ($env) {
            foreach ($env_vars as $var) {
                echo "$var: " . (isset($env[$var]) ? "CONFIGURADO ✓" : "NO CONFIGURADO ✗") . "\n";
            }
        } else {
            echo "No se pudo leer el archivo .env\n";
        }
    } else {
        echo "La función parse_ini_file no está disponible\n";
    }
} else {
    echo "Archivo .env: NO EXISTE ✗ - PROBLEMA CRÍTICO\n";
    echo "Solución: Copiar .env.production a .env\n";
}

echo "\n";

// Verificar configuración del servidor web
echo "=== Configuración del Servidor Web ===\n";
echo "Servidor: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Desconocido') . "\n";

echo "\n=== Fin del Diagnóstico ===\n";
echo "\nPara solucionar problemas de PDO PostgreSQL:\n";
echo "1. Instalar la extensión: sudo apt-get install php8.4-pgsql\n";
echo "2. Reiniciar PHP-FPM: sudo systemctl restart php8.4-fpm\n";
echo "\nPara solucionar problemas de caché:\n";
echo "1. Crear directorios: mkdir -p bootstrap/cache storage/framework/{cache,sessions,views}\n";
echo "2. Establecer permisos: chmod -R 775 bootstrap/cache storage\n";
echo "3. Establecer propietario: chown -R www-data:www-data bootstrap/cache storage\n";
