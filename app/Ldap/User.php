<?php

namespace App\Ldap;

use LdapRecord\Models\Model;
use LdapRecord\Models\ActiveDirectory\User as ADUser;
use Illuminate\Support\Facades\Log;

class User extends ADUser
{
    /**
     * The object classes of the LDAP model.
     */
    public static array $objectClasses = [
        'top',
        'person',
        'organizationalperson',
        'user',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected array $dates = [
        'lastlogon',
        'lastlogontimestamp',
        'pwdlastset',
        'accountexpires',
        'badpasswordtime',
        'lastlogoff',
    ];

    /**
     * Get the username (samAccountName).
     */
    public function getUsername(): ?string
    {
        return $this->getFirstAttribute('samaccountname');
    }

    /**
     * Get the employee ID.
     */
    public function getEmployeeId(): ?string
    {
        return $this->getFirstAttribute('employeeid');
    }

    /**
     * Get the display name.
     */
    public function getDisplayName(): ?string
    {
        return $this->getFirstAttribute('cn') ?: 
               $this->getFirstAttribute('displayname') ?: 
               $this->getFirstAttribute('name');
    }

    /**
     * Get the email address.
     */
    public function getEmailAddress(): ?string
    {
        return $this->getFirstAttribute('mail');
    }

    /**
     * Get the department.
     */
    public function getDepartment(): ?string
    {
        return $this->getFirstAttribute('department');
    }

    /**
     * Get the job title.
     */
    public function getJobTitle(): ?string
    {
        return $this->getFirstAttribute('title');
    }

    /**
     * Get the phone number.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->getFirstAttribute('telephonenumber');
    }

    /**
     * Get the manager DN.
     */
    public function getManagerDn(): ?string
    {
        return $this->getFirstAttribute('manager');
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        $userAccountControl = $this->getFirstAttribute('useraccountcontrol');
        
        if (!$userAccountControl) {
            return false;
        }

        // 0x0002 = ACCOUNTDISABLE
        return !($userAccountControl & 2);
    }

    /**
     * Check if user is a member of a group.
     */
    public function isMemberOf(string $groupDn): bool
    {
        $memberOf = $this->getAttribute('memberof') ?: [];
        
        foreach ($memberOf as $group) {
            if (strcasecmp($group, $groupDn) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all group memberships.
     */
    public function getGroups(): array
    {
        return $this->getAttribute('memberof') ?: [];
    }

    /**
     * Determine role based on LDAP groups.
     */
    public function determineRole(): string
    {
        $groups = $this->getGroups();
        $groupNames = array_map(function($dn) {
            // Extract CN from DN
            if (preg_match('/CN=([^,]+)/i', $dn, $matches)) {
                return strtolower($matches[1]);
            }
            return '';
        }, $groups);

        // Role mapping based on group names - adaptat per Esparreguera
        foreach ($groupNames as $groupName) {
            // Administradors
            if (strpos($groupName, 'enterprise admins') !== false || 
                strpos($groupName, 'domain admins') !== false ||
                strpos($groupName, 'administrador') !== false) {
                return 'admin';
            }
            
            // RRHH
            if (strpos($groupName, 'rrhh') !== false || 
                strpos($groupName, 'recursos humans') !== false ||
                strpos($groupName, 'personal') !== false) {
                return 'rrhh';
            }
            
            // Informàtica
            if (strpos($groupName, 'informatica') !== false || 
                strpos($groupName, 'it') !== false ||
                strpos($groupName, 'sistemas') !== false ||
                strpos($groupName, 'tecnologia') !== false) {
                return 'it';
            }
            
            // Gestors
            if (strpos($groupName, 'gestor') !== false || 
                strpos($groupName, 'supervisor') !== false ||
                strpos($groupName, 'manager') !== false ||
                strpos($groupName, 'responsable') !== false) {
                return 'gestor';
            }
        }

        return 'empleat'; // Default role
    }

    /**
     * Get a safe identifier for database sync.
     */
    public function getSafeIdentifier(): string
    {
        return $this->getUsername() ?: 
               $this->getEmailAddress() ?: 
               $this->getEmployeeId() ?: 
               'unknown_' . uniqid();
    }

    /**
     * The attributes that should be synced to the database.
     *
     * @return array<string, mixed>
     */
    public function toSyncArray(): array
    {
        // Registrar el inicio de la sincronización
        Log::debug('Iniciando sincronización de usuario LDAP a base de datos', [
            'dn' => $this->getDn(),
            'username' => $this->getFirstAttribute('samaccountname'),
        ]);
        
        // Determinar el rol principal basado en los grupos
        $rolPrincipal = $this->determineRole();
        
        // Obtener el nombre de usuario (samaccountname)
        $username = $this->getFirstAttribute('samaccountname');
        
        // Verificar si el usuario ya existe en la base de datos
        $existingUser = \App\Models\User::where('username', $username)->first();
        
        // Crear el array de sincronización
        $syncArray = [
            'name' => $this->getFirstAttribute('cn'),
            'email' => $this->getFirstAttribute('mail'),
            'username' => $username,
            'ldap_dn' => $this->getDn(),
            'rol_principal' => $rolPrincipal,
            'actiu' => true,
            'ldap_last_sync' => now(),
        ];
        
        // Si el usuario existe pero no tiene contraseña, generar una aleatoria
        // Esto es importante para que Laravel Auth funcione correctamente
        if ($existingUser && empty($existingUser->password)) {
            $syncArray['password'] = bcrypt(\Illuminate\Support\Str::random(16));
            Log::info('Generando contraseña aleatoria para usuario existente sin contraseña', [
                'username' => $username,
            ]);
        }
        
        // Registrar los datos que se van a sincronizar
        Log::debug('Datos de usuario LDAP a sincronizar', $syncArray);
        
        return $syncArray;
    }

    /**
     * Método de diagnóstico para autenticación
     */
    public static function diagnoseAuthentication(string $username, string $password): array
    {
        $result = ['success' => false, 'messages' => []];
        
        try {
            // Obtener la conexión LDAP usando el contenedor de LdapRecord
            $connection = \LdapRecord\Container::getDefaultConnection();
            $result['messages'][] = "✅ Conexión LDAP obtenida";
            
            // Probar formatos de autenticación
            $formats = [
                'upn' => $username . '@esparreguera.local',
                'username' => $username,
                'domain_slash' => 'ESPARREGUERA\\' . $username
            ];
            
            foreach ($formats as $format => $formattedUsername) {
                try {
                    $result['messages'][] = "Probando formato {$format}: {$formattedUsername}";
                    $auth = $connection->auth()->attempt($formattedUsername, $password);
                    
                    if ($auth) {
                        $result['success'] = true;
                        $result['messages'][] = "✅ Autenticación exitosa con formato {$format}";
                        $result['format'] = $format;
                        $result['username'] = $formattedUsername;
                        break;
                    } else {
                        $result['messages'][] = "❌ Autenticación fallida con formato {$format}";
                    }
                } catch (\Exception $e) {
                    $result['messages'][] = "❌ Error con formato {$format}: " . $e->getMessage();
                }
            }
            
            // Buscar usuario en LDAP
            $baseDn = $connection->getConfiguration()->get('base_dn');
            $result['messages'][] = "Base DN: {$baseDn}";
            
            try {
                // Crear una nueva instancia del modelo
                $model = new static;
                
                // Buscar el usuario usando la conexión
                $user = $model->newQuery()
                    ->where('samaccountname', '=', $username)
                    ->orWhere('mail', '=', $username)
                    ->first();
            } catch (\Exception $e) {
                $result['messages'][] = "❌ Error al buscar usuario: " . $e->getMessage();
                $user = null;
            }
            
            if ($user) {
                $result['messages'][] = "✅ Usuario encontrado en LDAP";
                $result['messages'][] = "DN: " . $user->getDn();
                $result['messages'][] = "Username: " . $user->getUsername();
                $result['messages'][] = "Email: " . $user->getEmailAddress();
                $result['messages'][] = "Activo: " . ($user->isActive() ? 'Sí' : 'No');
                
                $groups = $user->getGroups();
                $result['messages'][] = "Grupos: " . count($groups);
                foreach (array_slice($groups, 0, 5) as $group) {
                    $result['messages'][] = "- {$group}";
                }
                
                $result['user'] = $user;
            } else {
                $result['messages'][] = "❌ Usuario no encontrado en LDAP";
            }
            
        } catch (\Exception $e) {
            $result['messages'][] = "❌ Error general: " . $e->getMessage();
        }
        
        return $result;
    }
}
