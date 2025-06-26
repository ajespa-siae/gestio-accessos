<?php
// Script de diagnóstico para problemas de rutas en Laravel
header('Content-Type: text/plain');

echo "=== Diagnóstico de Rutas Laravel ===\n\n";

// Verificar si podemos ejecutar comandos artisan
$artisan_output = [];
$artisan_result = 0;
exec('cd .. && php artisan route:list 2>&1', $artisan_output, $artisan_result);

echo "Resultado de ejecución de artisan: " . ($artisan_result === 0 ? "OK" : "Error (código $artisan_result)") . "\n\n";

if ($artisan_result === 0) {
    echo "=== Rutas registradas ===\n";
    
    // Filtrar rutas relacionadas con login
    $login_routes = [];
    foreach ($artisan_output as $line) {
        if (strpos($line, 'login') !== false || strpos($line, 'auth') !== false) {
            $login_routes[] = $line;
        }
    }
    
    if (count($login_routes) > 0) {
        echo "Rutas de autenticación encontradas:\n";
        foreach ($login_routes as $route) {
            echo $route . "\n";
        }
    } else {
        echo "No se encontraron rutas específicas de autenticación.\n";
    }
    
    // Buscar rutas POST específicas
    echo "\n=== Rutas POST ===\n";
    $post_routes = [];
    foreach ($artisan_output as $line) {
        if (strpos($line, 'POST') !== false) {
            $post_routes[] = $line;
        }
    }
    
    if (count($post_routes) > 0) {
        foreach ($post_routes as $route) {
            echo $route . "\n";
        }
    } else {
        echo "No se encontraron rutas POST.\n";
    }
} else {
    echo "No se pudo ejecutar el comando artisan. Salida:\n";
    foreach ($artisan_output as $line) {
        echo $line . "\n";
    }
}

echo "\n";

// Verificar archivos de caché de rutas
echo "=== Archivos de caché ===\n";
$cache_files = [
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/config.php',
    'bootstrap/cache/services.php'
];

foreach ($cache_files as $file) {
    $full_path = "../$file";
    if (file_exists($full_path)) {
        echo "$file: EXISTE (tamaño: " . filesize($full_path) . " bytes, modificado: " . date("Y-m-d H:i:s", filemtime($full_path)) . ")\n";
    } else {
        echo "$file: NO EXISTE\n";
    }
}

echo "\n";

// Verificar configuración de Filament
echo "=== Configuración de Filament ===\n";
if (file_exists('../config/filament.php')) {
    echo "Archivo de configuración de Filament: EXISTE\n";
    
    // Intentar leer la configuración
    $filament_config = include('../config/filament.php');
    if (is_array($filament_config)) {
        echo "Prefijo de ruta: " . ($filament_config['path'] ?? 'No definido') . "\n";
        echo "Middleware de autenticación: " . (isset($filament_config['middleware']['auth']) ? implode(', ', $filament_config['middleware']['auth']) : 'No definido') . "\n";
    } else {
        echo "No se pudo leer la configuración de Filament\n";
    }
} else {
    echo "Archivo de configuración de Filament: NO EXISTE\n";
    echo "Verificando publicación de configuración...\n";
    
    // Verificar si el paquete está instalado
    if (file_exists('../vendor/filament')) {
        echo "Paquete Filament: INSTALADO\n";
        echo "Recomendación: Publicar la configuración con 'php artisan vendor:publish --tag=filament-config'\n";
    } else {
        echo "Paquete Filament: NO INSTALADO o no encontrado\n";
    }
}

echo "\n=== Soluciones recomendadas ===\n";
echo "1. Limpiar todas las cachés:\n";
echo "   php artisan config:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan view:clear\n";
echo "   php artisan cache:clear\n";
echo "   php artisan optimize:clear\n\n";
echo "2. Verificar que el archivo .htaccess esté configurado correctamente\n";
echo "3. Verificar que el servidor web esté configurado para procesar correctamente las solicitudes POST\n";
echo "4. Comprobar que no haya problemas con CSRF tokens\n";

echo "\n=== Fin del diagnóstico ===\n";
