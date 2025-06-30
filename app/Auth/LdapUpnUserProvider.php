<?php

namespace App\Auth;

use LdapRecord\Laravel\Auth\DatabaseUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Ldap\User as LdapUser;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Log;

class LdapUpnUserProvider extends DatabaseUserProvider
{
    /**
     * Validar credencials amb format UPN
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return false;
        }

        $username = $credentials['username'];
        $password = $credentials['password'];

        // Trobar usuari LDAP (case insensitive)
        $ldapUser = $this->findLdapUser($username);
        if (!$ldapUser) {
            Log::info("LDAP Auth: Usuari no trobat", ['username' => $username]);
            return false;
        }

        // Provar autenticació amb formats que funcionen
        return $this->attemptLdapAuth($ldapUser, $password);
    }

    /**
     * Buscar usuari LDAP case insensitive
     */
    private function findLdapUser(string $username): ?LdapUser
    {
        $variations = [
            $username,
            strtolower($username),
            ucfirst(strtolower($username)),
            strtoupper($username),
        ];

        foreach ($variations as $variation) {
            $ldapUser = LdapUser::where('samaccountname', $variation)->first();
            if ($ldapUser) {
                Log::info("LDAP Auth: Usuari trobat", [
                    'input' => $username,
                    'found' => $variation,
                    'canonical' => $ldapUser->getFirstAttribute('samaccountname')
                ]);
                return $ldapUser;
            }
        }

        return null;
    }

    /**
     * Autenticació LDAP amb formats descoberts
     */
    private function attemptLdapAuth(LdapUser $ldapUser, string $password): bool
    {
        try {
            $connection = new Connection(config('ldap.connections.default'));
            $connection->connect();

            $samAccount = $ldapUser->getFirstAttribute('samaccountname');

            // Format 1: UPN (prioritat alta)
            $upnFormat = $samAccount . '@esparreguera.local';
            Log::info("LDAP Auth: Provant UPN", ['format' => $upnFormat]);
            if ($connection->auth()->attempt($upnFormat, $password)) {
                Log::info("LDAP Auth: Èxit amb UPN", ['format' => $upnFormat]);
                return true;
            }

            // Format 2: Domain\Username
            $domainFormat = 'ESPARREGUERA\\' . $samAccount;
            Log::info("LDAP Auth: Provant Domain\\User", ['format' => $domainFormat]);
            if ($connection->auth()->attempt($domainFormat, $password)) {
                Log::info("LDAP Auth: Èxit amb Domain\\User", ['format' => $domainFormat]);
                return true;
            }

            // Format 3: DN complet
            $dn = $ldapUser->getDn();
            Log::info("LDAP Auth: Provant DN", ['dn' => $dn]);
            if ($connection->auth()->attempt($dn, $password)) {
                Log::info("LDAP Auth: Èxit amb DN", ['dn' => $dn]);
                return true;
            }

            Log::warning("LDAP Auth: Tots els formats fallen", ['username' => $samAccount]);
            return false;

        } catch (\Exception $e) {
            Log::error("LDAP Auth Error: " . $e->getMessage(), [
                'username' => $ldapUser->getFirstAttribute('samaccountname'),
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Retrieve by credentials amb case insensitive
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials['username'])) {
            return null;
        }

        $username = $credentials['username'];

        // Buscar usuari LDAP primer
        $ldapUser = $this->findLdapUser($username);
        if (!$ldapUser) {
            return null;
        }

        // Obtenir username canònic d'LDAP
        $canonicalUsername = $ldapUser->getFirstAttribute('samaccountname');

        // Buscar usuari BD amb username canònic
        $dbUser = $this->createModel()->newQuery()
            ->where('username', $canonicalUsername)
            ->first();

        if (!$dbUser) {
            Log::info("LDAP Auth: Usuari no trobat a BD", [
                'canonical_username' => $canonicalUsername,
                'input_username' => $username
            ]);
        }

        return $dbUser;
    }
}
