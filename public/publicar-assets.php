<?php
/**
 * Script para publicar los assets de Filament
 * Esto puede ayudar con problemas de CSS/JS en producción
 */

// Configuración de la página
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Publicar Assets</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";
echo "</head>";
echo "<body>";
echo "<h1>Publicación de Assets</h1>";

// Verificar si podemos cargar el autoloader de Composer
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require __DIR__ . '/../vendor/autoload.php';
        echo "<p class='success'>✓ Autoloader de Composer cargado correctamente</p>";
    } else {
        echo "<p class='error'>✗ No se encontró el autoloader de Composer</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al cargar el autoloader: " . $e->getMessage() . "</p>";
    exit;
}

// Función para ejecutar comandos de artisan
function ejecutarComando($comando) {
    $output = [];
    $returnVar = 0;
    
    // Construir el comando completo
    $comandoCompleto = 'cd ' . dirname(__DIR__) . ' && php artisan ' . $comando . ' 2>&1';
    
    // Ejecutar el comando
    exec($comandoCompleto, $output, $returnVar);
    
    // Devolver el resultado
    return [
        'exito' => $returnVar === 0,
        'salida' => implode("\n", $output),
        'codigo' => $returnVar
    ];
}

// Publicar los assets de Filament
echo "<h2>Publicando assets de Filament</h2>";
$resultado = ejecutarComando('vendor:publish --tag=filament-assets --force');

if ($resultado['exito']) {
    echo "<p class='success'>✓ Assets de Filament publicados correctamente</p>";
} else {
    echo "<p class='error'>✗ Error al publicar los assets de Filament</p>";
}

echo "<pre>" . htmlspecialchars($resultado['salida']) . "</pre>";

// Limpiar cachés
echo "<h2>Limpiando cachés</h2>";
$comandos = [
    'config:clear' => 'Limpiar caché de configuración',
    'route:clear' => 'Limpiar caché de rutas',
    'view:clear' => 'Limpiar caché de vistas',
    'cache:clear' => 'Limpiar caché general',
    'optimize:clear' => 'Limpiar todas las cachés'
];

foreach ($comandos as $comando => $descripcion) {
    $resultado = ejecutarComando($comando);
    
    if ($resultado['exito']) {
        echo "<p class='success'>✓ $descripcion: OK</p>";
    } else {
        echo "<p class='error'>✗ $descripcion: Error</p>";
    }
    
    echo "<pre>" . htmlspecialchars($resultado['salida']) . "</pre>";
}

echo "<h2>Próximos pasos</h2>";
echo "<p>Si los assets se han publicado correctamente, intenta acceder nuevamente a la página de login.</p>";
echo "<p>Si el problema persiste, verifica los logs de error del servidor.</p>";

echo "</body>";
echo "</html>";
