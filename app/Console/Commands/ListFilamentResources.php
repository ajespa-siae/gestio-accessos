<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class ListFilamentResources extends Command
{
    protected $signature = 'filament:list-resources';
    protected $description = 'Lista todos los recursos de Filament registrados';

    public function handle()
    {
        $this->info('Listando recursos de Filament registrados...');
        
        // Listar recursos en app/Filament/Resources
        $this->info('Recursos en el directorio:');
        $resourcesPath = app_path('Filament/Resources');
        
        if (!File::exists($resourcesPath)) {
            $this->error("El directorio {$resourcesPath} no existe.");
            return Command::FAILURE;
        }
        
        $resourceFiles = $this->getResourceFiles($resourcesPath);
        
        if (empty($resourceFiles)) {
            $this->warn("No se encontraron archivos de recursos en {$resourcesPath}");
        } else {
            foreach ($resourceFiles as $resourceFile) {
                $this->line(" - {$resourceFile}");
            }
        }
        
        // Intentar obtener recursos registrados a través de Filament
        $this->info('Intentando obtener recursos registrados en Filament:');
        
        try {
            // Obtener recursos a través de la reflexión
            $this->listRegisteredResources();
        } catch (\Exception $e) {
            $this->error("Error al obtener recursos registrados: " . $e->getMessage());
        }
        
        // Verificar si los archivos son accesibles
        $this->info('Verificando permisos de archivos:');
        $this->checkFilePermissions($resourcesPath);
        
        // Verificar si las clases son cargables
        $this->info('Verificando si las clases son cargables:');
        $this->checkClassesLoadable($resourceFiles);
        
        return Command::SUCCESS;
    }
    
    protected function getResourceFiles($path)
    {
        $files = [];
        
        if (!File::exists($path)) {
            return $files;
        }
        
        $directories = File::directories($path);
        
        foreach ($directories as $directory) {
            $directoryName = basename($directory);
            if (Str::endsWith($directoryName, 'Resource')) {
                $resourceFile = $path . '/' . $directoryName . '.php';
                if (File::exists($resourceFile)) {
                    $files[] = str_replace(app_path(), 'app', $resourceFile);
                }
            }
        }
        
        return $files;
    }
    
    protected function listRegisteredResources()
    {
        // Intentar obtener recursos registrados a través de la reflexión
        $adminPanelProvider = app_path('Providers/Filament/AdminPanelProvider.php');
        
        if (!File::exists($adminPanelProvider)) {
            $this->warn("No se encontró el archivo AdminPanelProvider.php");
            return;
        }
        
        $this->line("Recursos que deberían estar registrados según AdminPanelProvider:");
        $this->line(" - Se registran recursos desde: app\\Filament\\Resources");
        
        // Intentar cargar las clases de recursos y verificar si son válidas
        $resourcesNamespace = 'App\\Filament\\Resources';
        $resourcesPath = app_path('Filament/Resources');
        
        $resourceFiles = $this->getResourceFiles($resourcesPath);
        
        foreach ($resourceFiles as $resourceFile) {
            $className = $this->getClassNameFromFile($resourceFile);
            
            if ($className) {
                $fullyQualifiedClassName = $resourcesNamespace . '\\' . $className;
                
                try {
                    if (class_exists($fullyQualifiedClassName)) {
                        $reflection = new ReflectionClass($fullyQualifiedClassName);
                        
                        if ($reflection->isAbstract()) {
                            $this->warn(" - {$fullyQualifiedClassName} (abstracta, no registrada)");
                        } else {
                            $this->info(" - {$fullyQualifiedClassName} (clase válida)");
                            
                            // Verificar si tiene método de navegación
                            if ($reflection->hasMethod('shouldRegisterNavigation')) {
                                $this->line("   - Tiene método shouldRegisterNavigation()");
                                
                                // Intentar determinar si la navegación está habilitada
                                try {
                                    $instance = $reflection->newInstanceWithoutConstructor();
                                    $method = $reflection->getMethod('shouldRegisterNavigation');
                                    
                                    if ($method->isStatic()) {
                                        $shouldRegister = $method->invoke(null);
                                    } else {
                                        $shouldRegister = $method->invoke($instance);
                                    }
                                    
                                    $this->line("   - shouldRegisterNavigation() devuelve: " . ($shouldRegister ? 'true' : 'false'));
                                } catch (\Exception $e) {
                                    $this->warn("   - No se pudo determinar el valor de shouldRegisterNavigation(): " . $e->getMessage());
                                }
                            }
                            
                            // Verificar si tiene método de grupo de navegación
                            if ($reflection->hasMethod('getNavigationGroup')) {
                                $this->line("   - Tiene método getNavigationGroup()");
                            }
                        }
                    } else {
                        $this->error(" - {$fullyQualifiedClassName} (la clase no existe o no se puede cargar)");
                    }
                } catch (\Exception $e) {
                    $this->error(" - Error al cargar {$fullyQualifiedClassName}: " . $e->getMessage());
                }
            }
        }
    }
    
    protected function getClassNameFromFile($file)
    {
        $baseName = basename($file);
        return str_replace('.php', '', $baseName);
    }
    
    protected function checkFilePermissions($path)
    {
        $this->line("Permisos del directorio {$path}: " . substr(sprintf('%o', fileperms($path)), -4));
        
        $directories = File::directories($path);
        
        foreach ($directories as $directory) {
            $this->line("Permisos de {$directory}: " . substr(sprintf('%o', fileperms($directory)), -4));
            
            $files = File::files($directory);
            foreach ($files as $file) {
                $this->line("Permisos de {$file}: " . substr(sprintf('%o', fileperms($file)), -4));
            }
        }
    }
    
    protected function checkClassesLoadable($resourceFiles)
    {
        foreach ($resourceFiles as $resourceFile) {
            $className = $this->getClassNameFromFile($resourceFile);
            $fullyQualifiedClassName = 'App\\Filament\\Resources\\' . $className;
            
            try {
                if (class_exists($fullyQualifiedClassName)) {
                    $this->info(" - {$fullyQualifiedClassName} (cargable)");
                } else {
                    $this->error(" - {$fullyQualifiedClassName} (no cargable)");
                }
            } catch (\Exception $e) {
                $this->error(" - Error al cargar {$fullyQualifiedClassName}: " . $e->getMessage());
            }
        }
    }
}
