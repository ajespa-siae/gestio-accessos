<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncronitzarUsuarisLDAP implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {
            // Verificar si LDAP està disponible
            if (!class_exists('\App\Ldap\User')) {
                Log::warning('Model LDAP User no trobat, saltant sincronització');
                return;
            }

            $ldapUsers = \App\Ldap\User::all();
            $sincronitzats = 0;

            foreach ($ldapUsers as $ldapUser) {
                if ($this->syncUserFromLdap($ldapUser)) {
                    $sincronitzats++;
                }
            }

            Log::info("Sincronització LDAP completada: {$sincronitzats} usuaris processats");

        } catch (\Exception $e) {
            Log::error("Error en sincronització LDAP: " . $e->getMessage());
            throw $e;
        }
    }

    private function syncUserFromLdap($ldapUser): bool
    {
        try {
            $username = $ldapUser->getUsername();
            $employeeId = $ldapUser->getEmployeeId();

            if (!$username) {
                return false;
            }

            User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $ldapUser->getDisplayName(),
                    'email' => $ldapUser->getEmailAddress(),
                    'nif' => $employeeId,
                    'password' => Hash::make(Str::random(32)), // Password dummy
                    'actiu' => true,
                    'rol_principal' => $this->determinarRolPerDefecte($ldapUser)
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error("Error sincronitzant usuari LDAP {$username}: " . $e->getMessage());
            return false;
        }
    }

    private function determinarRolPerDefecte($ldapUser): string
    {
        try {
            $groups = $ldapUser->groups()->get();

            foreach ($groups as $group) {
                $groupName = strtolower($group->getFirstAttribute('cn'));

                if (str_contains($groupName, 'rrhh') || str_contains($groupName, 'recursoshumans')) {
                    return 'rrhh';
                }
                if (str_contains($groupName, 'informatica') || str_contains($groupName, 'it')) {
                    return 'it';
                }
                if (str_contains($groupName, 'gestors') || str_contains($groupName, 'managers')) {
                    return 'gestor';
                }
                if (str_contains($groupName, 'admin')) {
                    return 'admin';
                }
            }

        } catch (\Exception $e) {
            Log::warning("Error determinant rol LDAP: " . $e->getMessage());
        }

        return 'empleat'; // Per defecte
    }
}