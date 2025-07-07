<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;

class SyncUserWithLdap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ldap:sync-user {username : El nombre de usuario a sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza un usuario específico con LDAP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = $this->argument('username');
        
        // Buscar el usuario en la base de datos
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            $this->error("Usuario '{$username}' no encontrado en la base de datos.");
            return 1;
        }
        
        $this->info("Usuario encontrado: {$user->name} (ID: {$user->id})");
        $this->info("Rol actual: {$user->rol_principal}");
        
        // Buscar el usuario en LDAP
        try {
            $ldapUser = \App\Ldap\User::where('samaccountname', '=', $username)->first();
            
            if (!$ldapUser) {
                $this->error("Usuario '{$username}' no encontrado en LDAP.");
                return 1;
            }
            
            $this->info("Usuario LDAP encontrado: {$ldapUser->getDisplayName()}");
            
            // Mostrar grupos LDAP
            $groups = $ldapUser->getGroups();
            $this->info("Grupos LDAP (" . count($groups) . "):");
            foreach (array_slice($groups, 0, 10) as $group) {
                $this->line("- {$group}");
            }
            
            // Determinar rol LDAP
            $ldapRole = $ldapUser->determineRole();
            $this->info("Rol determinado por LDAP: {$ldapRole}");
            
            // Sincronizar usuario
            $this->info("Sincronizando usuario...");
            $result = $user->syncWithLdap($ldapUser);
            
            if ($result) {
                $user->refresh();
                $this->info("Sincronización completada. Nuevo rol: {$user->rol_principal}");
                return 0;
            } else {
                $this->error("Error al sincronizar usuario.");
                if ($user->teSincronitzacioErrors()) {
                    $this->error("Errores: " . json_encode($user->ldap_sync_errors));
                }
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
