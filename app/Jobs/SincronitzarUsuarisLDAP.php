<?php
// app/Jobs/SincronitzarUsuarisLDAP.php
// ✅ FIX: Afegir paràmetre createNew que faltava

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// ✅ IMPORTS CORRECTES amb ALIAS
use App\Models\User as EloquentUser;  // Model BD
use App\Ldap\User as LdapUser;        // Model LDAP

class SincronitzarUsuarisLDAP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $forceUpdate;
    protected $specificUser;
    protected $createNew; // ✅ FIX: Afegir paràmetre que faltava

    // ✅ FIX: Actualitzar constructor per acceptar createNew
    public function __construct(bool $forceUpdate = false, ?string $specificUser = null, bool $createNew = false)
    {
        $this->forceUpdate = $forceUpdate;
        $this->specificUser = $specificUser;
        $this->createNew = $createNew; // ✅ Nou paràmetre
    }

    public function handle(): void
    {
        Log::info('🔄 Iniciant sincronització LDAP', [
            'force_update' => $this->forceUpdate,
            'specific_user' => $this->specificUser,
            'create_new' => $this->createNew // ✅ Log del nou paràmetre
        ]);

        try {
            if ($this->specificUser) {
                $this->syncSpecificUser($this->specificUser);
            } else {
                $this->syncAllUsers();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en sincronització LDAP: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function syncSpecificUser(string $username): void
    {
        Log::info("🔍 Sincronitzant usuari específic: {$username}");

        // Buscar usuari LDAP amb model correcte
        $ldapUser = LdapUser::where('samaccountname', $username)->first();
        
        if (!$ldapUser) {
            Log::warning("⚠️ Usuari LDAP no trobat: {$username}");
            return;
        }

        $this->processLdapUser($ldapUser);
    }

    private function syncAllUsers(): void
    {
        Log::info('🔄 Sincronitzant tots els usuaris LDAP', [
            'opcions' => [
                'force_update' => $this->forceUpdate,
                'create_new' => $this->createNew
            ]
        ]);

        // ✅ FIX: Obtenir tots els usuaris LDAP amb model correcte
        $ldapUsers = LdapUser::get();
        $count = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($ldapUsers as $ldapUser) {
            try {
                $result = $this->processLdapUser($ldapUser);
                
                // ✅ Comptar resultats
                switch ($result) {
                    case 'created':
                        $created++;
                        break;
                    case 'updated':
                        $updated++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                }
                
                $count++;
            } catch (\Exception $e) {
                Log::error("❌ Error processant usuari LDAP: " . $e->getMessage(), [
                    'ldap_user' => $ldapUser->getFirstAttribute('samaccountname'),
                    'exception' => $e->getMessage()
                ]);
            }
        }

        Log::info("✅ Sincronització completada", [
            'total_processed' => $count,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'opcions_utilitzades' => [
                'force_update' => $this->forceUpdate,
                'create_new' => $this->createNew
            ]
        ]);
    }

    private function processLdapUser(LdapUser $ldapUser): string
    {
        $username = $ldapUser->getFirstAttribute('samaccountname');
        $employeeId = $ldapUser->getFirstAttribute('employeeid');
        $displayName = $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname');
        $email = $ldapUser->getFirstAttribute('mail');

        if (!$username) {
            Log::warning('⚠️ Usuari LDAP sense samaccountname, saltant...');
            return 'skipped';
        }

        Log::info("🔄 Processant usuari LDAP: {$username}", [
            'employee_id' => $employeeId,
            'display_name' => $displayName,
            'email' => $email
        ]);

        // ✅ Buscar usuari existent per USERNAME primer
        $existingUserByUsername = EloquentUser::where('username', $username)->first();
        
        // ✅ També buscar per EMAIL per evitar duplicats
        $existingUserByEmail = $email ? EloquentUser::where('email', $email)->first() : null;

        if ($existingUserByUsername) {
            return $this->updateExistingUser($existingUserByUsername, $ldapUser);
        } elseif ($existingUserByEmail) {
            Log::info("🔄 Usuari trobat per email, actualitzant: {$existingUserByEmail->username} -> {$username}");
            return $this->updateExistingUser($existingUserByEmail, $ldapUser);
        } else {
            // Crear usuari nou només si no existeix cap amb mateix email o username
            if ($this->createNew) {
                return $this->createNewUser($ldapUser);
            } else {
                Log::info("⏭️ Usuari no existeix i createNew=false, saltant: {$username}");
                return 'skipped';
            }
        }
    }

    private function updateExistingUser(EloquentUser $user, LdapUser $ldapUser): string
    {
        if (!$this->forceUpdate) {
            Log::info("⏭️ Usuari existent, saltant actualització (forceUpdate=false): {$user->username}");
            return 'skipped';
        }

        $originalData = $user->toArray();

        $user->update([
            'name' => $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname') ?: $user->name,
            'email' => $ldapUser->getFirstAttribute('mail') ?: $user->email,
            'nif' => $ldapUser->getFirstAttribute('employeeid') ?: $user->nif,
            'actiu' => true
        ]);

        Log::info("✅ Usuari actualitzat: {$user->username}", [
            'canvis_aplicats' => array_diff_assoc($user->fresh()->toArray(), $originalData)
        ]);

        return 'updated';
    }

    private function createNewUser(LdapUser $ldapUser): string
    {
        $username = $ldapUser->getFirstAttribute('samaccountname');
        $employeeId = $ldapUser->getFirstAttribute('employeeid');
        $displayName = $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname');
        $email = $ldapUser->getFirstAttribute('mail');

        Log::info("🆕 Creant nou usuari des de LDAP: {$username}");

        // ✅ VERIFICAR EMAIL DUPLICAT ABANS DE CREAR
        if ($email) {
            $existingUserByEmail = EloquentUser::where('email', $email)->first();
            if ($existingUserByEmail) {
                Log::warning("⚠️ Usuari amb mateix email ja existeix, actualitzant en lloc de crear", [
                    'existing_user_id' => $existingUserByEmail->id,
                    'existing_username' => $existingUserByEmail->username,
                    'ldap_username' => $username,
                    'email' => $email
                ]);
                
                // Actualitzar usuari existent en lloc de crear nou
                return $this->updateExistingUser($existingUserByEmail, $ldapUser);
            }
        }

        // ✅ Crear usuari amb model Eloquent correcte
        $newUser = EloquentUser::create([
            'name' => $displayName ?: $username,
            'email' => $email ?: "{$username}@esparreguera.cat",
            'username' => $username,
            'nif' => $employeeId,
            'password' => Hash::make(Str::random(32)), // Password dummy
            'rol_principal' => $this->determinarRolPerDefecte($ldapUser),
            'actiu' => true,
            'email_verified_at' => now()
        ]);

        Log::info("✅ Usuari creat amb èxit", [
            'id' => $newUser->id,
            'username' => $newUser->username,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'nif' => $newUser->nif,
            'rol' => $newUser->rol_principal
        ]);

        return 'created';
    }

    private function determinarRolPerDefecte(LdapUser $ldapUser): string
    {
        try {
            // Obtenir grups LDAP de l'usuari
            $groups = $ldapUser->groups()->get();
            
            if ($groups->isNotEmpty()) {
                foreach ($groups as $group) {
                    $groupName = strtolower($group->getFirstAttribute('cn') ?? '');
                    
                    // Determinar rol segons grup LDAP
                    if (str_contains($groupName, 'rrhh') || str_contains($groupName, 'recursoshumans')) {
                        Log::info("🎯 Rol determinat per grup LDAP: rrhh", ['group' => $groupName]);
                        return 'rrhh';
                    }
                    if (str_contains($groupName, 'informatica') || str_contains($groupName, 'it')) {
                        Log::info("🎯 Rol determinat per grup LDAP: it", ['group' => $groupName]);
                        return 'it';
                    }
                    if (str_contains($groupName, 'gestors') || str_contains($groupName, 'caps')) {
                        Log::info("🎯 Rol determinat per grup LDAP: gestor", ['group' => $groupName]);
                        return 'gestor';
                    }
                    if (str_contains($groupName, 'administradors') || str_contains($groupName, 'admins')) {
                        Log::info("🎯 Rol determinat per grup LDAP: admin", ['group' => $groupName]);
                        return 'admin';
                    }
                }

                Log::info("ℹ️ Cap grup específic trobat, assignant rol empleat", [
                    'username' => $ldapUser->getFirstAttribute('samaccountname'),
                    'groups_found' => $groups->map(fn($g) => $g->getFirstAttribute('cn'))->filter()->toArray()
                ]);
            } else {
                Log::info("ℹ️ Usuari sense grups LDAP, assignant rol empleat", [
                    'username' => $ldapUser->getFirstAttribute('samaccountname')
                ]);
            }

        } catch (\Exception $e) {
            Log::warning("⚠️ Error obtenint grups LDAP per determinar rol: " . $e->getMessage(), [
                'username' => $ldapUser->getFirstAttribute('samaccountname')
            ]);
        }

        return 'empleat'; // Rol per defecte
    }
}