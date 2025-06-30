<?php
// app/Jobs/SincronitzarUsuariLDAP.php

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

class SincronitzarUsuariLDAP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $username;
    protected $forceUpdate;
    protected $createIfNotExists;

    public function __construct(string $username, bool $forceUpdate = false, bool $createIfNotExists = false)
    {
        $this->username = $username;
        $this->forceUpdate = $forceUpdate;
        $this->createIfNotExists = $createIfNotExists;
    }

    public function handle(): void
    {
        Log::info("🔄 Sincronitzant usuari individual: {$this->username}", [
            'force_update' => $this->forceUpdate,
            'create_if_not_exists' => $this->createIfNotExists
        ]);

        try {
            // ✅ Buscar usuari LDAP amb model correcte
            $ldapUser = LdapUser::where('samaccountname', $this->username)->first();
            
            if (!$ldapUser) {
                Log::error("❌ Usuari LDAP no trobat: {$this->username}");
                throw new \Exception("Usuari LDAP no trobat: {$this->username}");
            }

            // ✅ Buscar usuari existent a BD per USERNAME
            $existingUserByUsername = EloquentUser::where('username', $this->username)->first();
            
            // ✅ També buscar per EMAIL per evitar conflictes
            $email = $ldapUser->getFirstAttribute('mail');
            $existingUserByEmail = $email ? EloquentUser::where('email', $email)->first() : null;

            if ($existingUserByUsername) {
                $this->updateUser($existingUserByUsername, $ldapUser);
            } elseif ($existingUserByEmail) {
                Log::info("🔄 Usuari trobat per email, actualitzant: {$existingUserByEmail->username} -> {$this->username}");
                $this->updateUser($existingUserByEmail, $ldapUser);
            } elseif ($this->createIfNotExists) {
                $this->createUser($ldapUser);
            } else {
                Log::warning("⚠️ Usuari no existeix a BD i createIfNotExists=false: {$this->username}");
                throw new \Exception("Usuari no existeix a la base de dades: {$this->username}");
            }

        } catch (\Exception $e) {
            Log::error("❌ Error sincronitzant usuari {$this->username}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function updateUser(EloquentUser $user, LdapUser $ldapUser): void
    {
        if (!$this->forceUpdate) {
            Log::info("⏭️ Usuari existent, no s'actualitza (forceUpdate=false): {$user->username}");
            return;
        }

        Log::info("🔄 Actualitzant usuari existent: {$user->username}");

        $originalData = $user->toArray();

        // ✅ Actualitzar amb dades LDAP
        $user->update([
            'name' => $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname') ?: $user->name,
            'email' => $ldapUser->getFirstAttribute('mail') ?: $user->email,
            'nif' => $ldapUser->getFirstAttribute('employeeid') ?: $user->nif,
            'actiu' => true
        ]);

        Log::info("✅ Usuari actualitzat correctament: {$user->username}", [
            'canvis' => array_diff_assoc($user->fresh()->toArray(), $originalData)
        ]);
    }

    private function createUser(LdapUser $ldapUser): void
    {
        $username = $ldapUser->getFirstAttribute('samaccountname');
        
        Log::info("🆕 Creant nou usuari des de LDAP: {$username}");

        // Extreure dades LDAP
        $employeeId = $ldapUser->getFirstAttribute('employeeid');
        $displayName = $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname');
        $email = $ldapUser->getFirstAttribute('mail');

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
                $this->updateUser($existingUserByEmail, $ldapUser);
                return;
            }
        }

        // ✅ Crear usuari amb model Eloquent correcte
        $newUser = EloquentUser::create([
            'name' => $displayName ?: $username,
            'email' => $email ?: "{$username}@esparreguera.cat",
            'username' => $username,
            'nif' => $employeeId,
            'password' => Hash::make(Str::random(32)), // Password dummy per LDAP
            'rol_principal' => $this->determinarRolPerDefecte($ldapUser),
            'actiu' => true,
            'email_verified_at' => now()
        ]);

        Log::info("✅ Usuari creat amb èxit des de LDAP", [
            'id' => $newUser->id,
            'username' => $newUser->username,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'nif' => $newUser->nif,
            'rol_principal' => $newUser->rol_principal
        ]);
    }

    private function determinarRolPerDefecte(LdapUser $ldapUser): string
    {
        try {
            // Obtenir grups LDAP
            $groups = $ldapUser->groups()->get();
            
            if ($groups->isNotEmpty()) {
                foreach ($groups as $group) {
                    $groupName = strtolower($group->getFirstAttribute('cn') ?? '');
                    
                    // Mapejat grups LDAP → rols aplicació
                    if (str_contains($groupName, 'rrhh') || str_contains($groupName, 'recursoshumans')) {
                        Log::info("🎯 Rol determinat per grup LDAP: rrhh", ['group' => $groupName]);
                        return 'rrhh';
                    }
                    if (str_contains($groupName, 'informatica') || str_contains($groupName, 'it')) {
                        Log::info("🎯 Rol determinat per grup LDAP: it", ['group' => $groupName]);
                        return 'it';
                    }
                    if (str_contains($groupName, 'gestors') || str_contains($groupName, 'caps') || str_contains($groupName, 'director')) {
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

        return 'empleat'; // Rol per defecte segur
    }
}