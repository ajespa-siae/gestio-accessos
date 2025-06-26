<?php
/**
 * Diagnóstico específico para problemas de autenticación en Filament
 * Este script verifica las rutas de autenticación y los métodos permitidos
 */

// Función para mostrar información con formato
function mostrarInfo($titulo, $contenido) {
    echo "<div style='margin-bottom: 20px;'>";
    echo "<h3 style='background-color: #f0f0f0; padding: 5px;'>$titulo</h3>";
    echo "<pre style='background-color: #f8f8f8; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($contenido);
    echo "</pre>";
    echo "</div>";
}

// Configuración de la página
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Diagnóstico de Autenticación</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1 { color: #333; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
</style>";
echo "</head>";
echo "<body>";
echo "<h1>Diagnóstico de Autenticación en Filament</h1>";

// Verificar si podemos cargar el autoloader de Composer
try {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require __DIR__ . '/../vendor/autoload.php';
        echo "<p class='success'>✓ Autoloader de Composer cargado correctamente</p>";
    } else {
        echo "<p class='error'>✗ No se encontró el autoloader de Composer</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al cargar el autoloader: " . $e->getMessage() . "</p>";
}

// Verificar si Filament está instalado
try {
    if (class_exists('Filament\Filament')) {
        echo "<p class='success'>✓ Filament está instalado</p>";
    } else {
        echo "<p class='error'>✗ Filament no está instalado o no se puede cargar</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error al verificar Filament: " . $e->getMessage() . "</p>";
}

// Verificar archivo .htaccess
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    echo "<p class='success'>✓ Archivo .htaccess encontrado</p>";
    
    // Verificar configuración para métodos POST
    if (strpos($htaccessContent, 'RewriteRule') !== false) {
        echo "<p class='success'>✓ .htaccess contiene reglas de reescritura</p>";
    } else {
        echo "<p class='error'>✗ .htaccess no contiene reglas de reescritura</p>";
    }
    
    mostrarInfo("Contenido del .htaccess", $htaccessContent);
} else {
    echo "<p class='error'>✗ No se encontró el archivo .htaccess</p>";
}

// Verificar configuración del servidor web
echo "<h2>Información del servidor web</h2>";
echo "<p>Servidor: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Método de solicitud permitido: " . $_SERVER['REQUEST_METHOD'] . "</p>";

// Verificar si se permiten solicitudes POST
echo "<h2>Prueba de solicitud POST</h2>";
echo "<form method='post' action='diagnostico-auth.php'>";
echo "<input type='hidden' name='test_post' value='1'>";
echo "<button type='submit'>Probar solicitud POST</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_post'])) {
    echo "<p class='success'>✓ Las solicitudes POST funcionan correctamente</p>";
} 

// Verificar CSRF protection
echo "<h2>Verificación de CSRF</h2>";
echo "<p>Para que los formularios POST funcionen en Laravel, necesitan un token CSRF.</p>";
echo "<p>Verifica que tu formulario de login incluya el campo _token:</p>";
echo "<pre>&lt;input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\"&gt;</pre>";

// Soluciones recomendadas
echo "<h2>Soluciones recomendadas</h2>";
echo "<ol>";
echo "<li>Asegúrate de que todas las cachés estén limpias (ya configurado en dploy.yml)</li>";
echo "<li>Verifica que el servidor web permita solicitudes POST (prueba con el botón de arriba)</li>";
echo "<li>Comprueba que el formulario de login incluya el token CSRF</li>";
echo "<li>Si usas un proxy o balanceador de carga, asegúrate de que no esté bloqueando las solicitudes POST</li>";
echo "<li>Verifica la configuración de seguridad del servidor web (mod_security, etc.)</li>";
echo "</ol>";

echo "</body>";
echo "</html>";
