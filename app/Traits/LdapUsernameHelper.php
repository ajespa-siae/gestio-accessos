<?php

namespace App\Traits;

use App\Ldap\User as LdapUser;
use App\Models\User as EloquentUser;
use Illuminate\Support\Facades\Log;

trait LdapUsernameHelper
{
    /**
     * Cercar usuari LDAP de forma flexible (prova diferents cases)
     */
    protected function findLdapUserFlexible(string $username): ?LdapUser
    {
        // Provar exacte primer
        $ldapUser = LdapUser::where('samaccountname', $username)->first();
        if ($ldapUser) {
            Log::info("✅ Usuari LDAP trobat (case exacte): {$username}");
            return $ldapUser;
        }

        // Provar majúscula primera lletra
        $titleCase = ucfirst(strtolower($username));
        $ldapUser = LdapUser::where('samaccountname', $titleCase)->first();
        if ($ldapUser) {
            Log::info("✅ Usuari LDAP trobat (title case): {$titleCase}");
            return $ldapUser;
        }

        // Provar tot majúscules
        $upperCase = strtoupper($username);
        $ldapUser = LdapUser::where('samaccountname', $upperCase)->first();
        if ($ldapUser) {
            Log::info("✅ Usuari LDAP trobat (upper case): {$upperCase}");
            return $ldapUser;
        }

        // Provar tot minúscules
        $lowerCase = strtolower($username);
        $ldapUser = LdapUser::where('samaccountname', $lowerCase)->first();
        if ($ldapUser) {
            Log::info("✅ Usuari LDAP trobat (lower case): {$lowerCase}");
            return $ldapUser;
        }

        Log::warning("❌ Usuari LDAP no trobat en cap case: {$username}");
        return null;
    }

    /**
     * Cercar usuari BD de forma flexible (case insensitive)
     */
    protected function findDbUserFlexible(string $username): ?EloquentUser
    {
        return EloquentUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();
    }

    /**
     * Obtenir el username "canònic" d'LDAP (el case exacte que retorna LDAP)
     */
    protected function getCanonicalUsername(LdapUser $ldapUser): string
    {
        return $ldapUser->getFirstAttribute('samaccountname');
    }
}