<?php
// Archivo de diagnóstico para identificar problemas de despliegue
header('Content-Type: text/plain');

echo "=== Diagnóstico de Gestió d'Accessos ===\n\n";

// Información del sistema
echo "Información del sistema:\n";
echo "- PHP versión: " . phpversion() . "\n";
echo "- Sistema operativo: " . php_uname() . "\n";
echo "- Servidor web: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "- Usuario del proceso: " . get_current_user() . "\n\n";

// Verificar permisos de directorios críticos
echo "Permisos de directorios críticos:\n";
$directorios = [
    '.' => 'Directorio actual',
    '../storage' => 'Directorio storage',
    '../storage/logs' => 'Directorio de logs',
    '../storage/framework' => 'Directorio framework',
    '../storage/framework/cache' => 'Directorio cache',
    '../storage/framework/sessions' => 'Directorio sessions',
    '../storage/framework/views' => 'Directorio views',
    '../bootstrap/cache' => 'Directorio bootstrap/cache'
];

foreach ($directorios as $dir => $descripcion) {
    if (file_exists($dir)) {
        echo "- $descripcion: ";
        echo "Existe, Permisos: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
        echo "  Propietario: " . posix_getpwuid(fileowner($dir))['name'] . "\n";
        echo "  Grupo: " . posix_getgrgid(filegroup($dir))['name'] . "\n";
        echo "  Escribible: " . (is_writable($dir) ? 'Sí' : 'No') . "\n";
    } else {
        echo "- $descripcion: No existe\n";
    }
}

echo "\n";

// Verificar archivo .env
echo "Archivo .env:\n";
if (file_exists('../.env')) {
    echo "- Existe, Permisos: " . substr(sprintf('%o', fileperms('../.env')), -4) . "\n";
    echo "  Propietario: " . posix_getpwuid(fileowner('../.env'))['name'] . "\n";
    echo "  Grupo: " . posix_getgrgid(filegroup('../.env'))['name'] . "\n";
    
    // Mostrar algunas variables de entorno (sin mostrar contraseñas)
    $env = file('../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "- Variables de entorno:\n";
    foreach ($env as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            if (!in_array($key, ['DB_PASSWORD', 'APP_KEY', 'LDAP_PASSWORD'])) {
                echo "  $key=$value\n";
            } else {
                echo "  $key=******\n";
            }
        }
    }
} else {
    echo "- No existe\n";
}

echo "\n";

// Verificar extensiones PHP necesarias
echo "Extensiones PHP:\n";
$extensiones = [
    'pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'tokenizer', 
    'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'ldap'
];

foreach ($extensiones as $ext) {
    echo "- $ext: " . (extension_loaded($ext) ? 'Cargada' : 'No cargada') . "\n";
}

echo "\n";

// Verificar si podemos conectar a la base de datos
echo "Conexión a la base de datos:\n";
if (file_exists('../.env')) {
    try {
        $env = parse_ini_file('../.env');
        $dsn = "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']}";
        $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD']);
        echo "- Conexión exitosa\n";
        
        // Verificar algunas tablas importantes
        $tablas = ['users', 'empleats', 'departaments', 'sistemes'];
        foreach ($tablas as $tabla) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
            if ($stmt) {
                $count = $stmt->fetchColumn();
                echo "- Tabla $tabla: $count registros\n";
            } else {
                echo "- Tabla $tabla: Error al consultar\n";
            }
        }
    } catch (PDOException $e) {
        echo "- Error de conexión: " . $e->getMessage() . "\n";
    }
} else {
    echo "- No se puede verificar (falta archivo .env)\n";
}

echo "\n";
echo "=== Fin del diagnóstico ===\n";
