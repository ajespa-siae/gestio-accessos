# Documentación de Soluciones Técnicas

## Gestión de Colas en Laravel

### Configuración de Colas en CloudPanel

El sistema utiliza el driver `database` para las colas de Laravel. En entornos de producción con CloudPanel sin permisos root, hemos implementado la siguiente solución:

1. **Scripts PHP para ejecución vía cron jobs**:
   - `/public/process-queue.php`: Procesa los jobs pendientes en la cola
   - `/public/restart-queue.php`: Reinicia las colas para aplicar cambios en el código

2. **Configuración de cron jobs en CloudPanel**:
   ```
   # Procesar jobs cada 5 minutos
   */5 * * * * cd /home/siae-accessos/htdocs/accessos.siae.cat/current && /opt/php-8.4/bin/php public/process-queue.php >> /home/siae-accessos/logs/queue-worker.log 2>&1
   
   # Reiniciar colas cada hora para aplicar cambios y evitar fugas de memoria
   0 * * * * cd /home/siae-accessos/htdocs/accessos.siae.cat/current && /opt/php-8.4/bin/php public/restart-queue.php >> /home/siae-accessos/logs/queue-restart.log 2>&1
   ```

3. **Comandos de diagnóstico**:
   ```bash
   # Ver estado actual de las colas
   php artisan colas:diagnosticar
   
   # Monitorear jobs en tiempo real
   php artisan colas:monitorear
   ```

### Solución de problemas comunes

1. **Jobs pendientes sin procesar**:
   - Verificar que los cron jobs estén configurados correctamente
   - Comprobar los logs en `/home/siae-accessos/logs/queue-worker.log`
   - Ejecutar manualmente `php artisan queue:work --stop-when-empty`

2. **Jobs fallidos**:
   - Revisar detalles con `php artisan queue:failed`
   - Reintentar con `php artisan queue:retry ID` o `php artisan queue:retry all`
   - Purgar con `php artisan queue:flush`

## Corrección de Advertencias PHP Deprecated

En PHP 8.4.8, los parámetros implícitamente nulos están obsoletos. Hemos corregido esto en varios modelos:

1. **Problema**: Métodos que aceptaban parámetros nulos sin declaración explícita:
   ```php
   // Antes (genera advertencia)
   public function donarBaixa($observacions = null)
   
   // Después (corregido)
   public function donarBaixa(?string $observacions = null)
   ```

2. **Archivos corregidos**:
   - `Empleat.php`: Método `donarBaixa`
   - `ChecklistTemplate.php`: Método `duplicar`
   - `Departament.php`: Método `setConfiguracio`
   - `Sistema.php`: Métodos `getValidadorsPerDepartament`, `afegirValidadorEspecific`, `afegirValidadorGestorDepartament`
   - `Validacio.php`: Método `aprovar`
   - `SolicitudAcces.php`: Método `processarValidacio`

## Solución de Problemas con Extensiones PHP

### Error en la carga de pdo_pgsql

**Problema**: PHP mostraba el siguiente error:
```
PHP Warning: PHP Startup: Unable to load dynamic library 'pdo_pgsql' (tried: /usr/lib/php/20240924/pdo_pgsql (/usr/lib/php/20240924/pdo_pgsql: cannot open shared object file: No such file or directory), /usr/lib/php/20240924/pdo_pgsql.so (/usr/lib/php/20240924/pdo_pgsql.so: undefined symbol: pdo_dbh_ce)) in Unknown on line 0
```

**Causa**: Configuración duplicada de extensiones PHP y orden de carga incorrecto.

**Solución**:
1. Reinstalar las extensiones:
   ```bash
   apt-get update
   apt-get install --reinstall php8.4-pgsql
   ```

2. Corregir la configuración en php.ini:
   - Comentar las líneas duplicadas en `/etc/php/8.4/cli/php.ini` y `/etc/php/8.4/fpm/php.ini`
   - Asegurarse de que PDO se cargue antes que pdo_pgsql

3. Reiniciar PHP-FPM:
   ```bash
   systemctl restart php8.4-fpm
   ```

**Verificación**:
```bash
php -m | grep pdo
```
Debería mostrar los módulos sin advertencias.

## Lenguaje Inclusivo en Notificaciones

Todas las notificaciones internas y correos electrónicos del sistema deben utilizar lenguaje inclusivo:

- Usar "empleat/da" en lugar de solo "empleat"
- Usar "usuari/a" en lugar de solo "usuari"
- Usar "validador/a" en lugar de solo "validador"
- Usar formas neutras cuando sea posible
- Evitar el masculino genérico

Esta regla aplica a todos los textos visibles para los usuarios, incluyendo:
- Correos electrónicos
- Notificaciones internas
- Mensajes de confirmación
- Textos en la interfaz de usuario

## Sistema de Notificaciones para Tareas Asignadas

Se ha implementado un sistema de notificaciones para la asignación de tareas:

1. **Job `NotificarTascaAssignada`**:
   - Envía notificaciones internas cuando se asigna una tarea
   - Envía correos electrónicos en catalán a usuarios IT

2. **Plantilla de correo**:
   - `tasca-assignada.blade.php` en catalán con lenguaje inclusivo

3. **Acciones en Filament**:
   - `assignar_usuari`: Asigna un usuario con el rol correspondiente a una tarea
   - `desassignar_usuari`: Quita la asignación de un usuario a una tarea
