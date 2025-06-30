<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión RRHH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Barra de navegación -->
        <nav class="bg-blue-600 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="font-bold text-xl">Gestión RRHH</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="ml-4 flex items-center md:ml-6">
                            <div class="relative">
                                <div class="flex items-center">
                                    <span class="mr-4">{{ $user->name }}</span>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="bg-blue-700 hover:bg-blue-800 px-3 py-1 rounded text-sm">
                                            Cerrar sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Contenido principal -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Información del Usuario</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-700 mb-3">Datos Personales</h3>
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-gray-500">Nombre:</span>
                                        <span class="ml-2 font-medium">{{ $user->name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Usuario:</span>
                                        <span class="ml-2 font-medium">{{ $user->username }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Correo electrónico:</span>
                                        <span class="ml-2 font-medium">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-700 mb-3">Información de Acceso</h3>
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-gray-500">Rol principal:</span>
                                        <span class="ml-2 font-medium">{{ ucfirst($user->rol_principal) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Estado:</span>
                                        <span class="ml-2 font-medium">{{ $user->actiu ? 'Activo' : 'Inactivo' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Última sincronización LDAP:</span>
                                        <span class="ml-2 font-medium">{{ $user->ldap_last_sync ? $user->ldap_last_sync->format('d/m/Y H:i') : 'Nunca' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
