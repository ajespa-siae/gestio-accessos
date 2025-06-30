<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LdapUserDetailsCommand extends Command
{
    protected $signature = 'ldap:user 
                            {username : Username o email de l\'usuari}';

    protected $description = 'Obté detalls complets d\'un usuari específic d\'LDAP';

    public function handle(): int
    {
        $username = $this->argument('username');

        $this->info("👤 DETALLS USUARI: {$username}");
        $this->info('===========================');

        try {
            $connection = $this->establirConnexioAutenticada();
            if (!$connection) {
                $this->error('❌ No es pot establir connexió LDAP');
                return 1;
            }

            $baseDn = config('ldap.connections.default.base_dn');
            
            // Cerca per username o email
            $filter = "(&(objectClass=user)(|(samaccountname={$username})(mail={$username})(cn={$username})))";

            $search = ldap_search(
                $connection,
                $baseDn,
                $filter,
                [
                    'cn', 'samaccountname', 'mail', 'employeeid', 
                    'useraccountcontrol', 'memberof', 'department', 
                    'title', 'telephoneNumber', 'lastLogon'
                ]
            );

            if (!$search) {
                $this->error('❌ Error cercant usuari: ' . ldap_error($connection));
                ldap_close($connection);
                return 1;
            }

            $entries = ldap_get_entries($connection, $search);

            if ($entries['count'] === 0) {
                $this->warn("⚠️ Usuari '{$username}' no trobat");
                $this->info('💡 Prova amb:');
                $this->info('   - Username exacte (samaccountname)');
                $this->info('   - Email complet');
                $this->info('   - Nom complet');
                ldap_close($connection);
                return 0;
            }

            $user = $entries[0];
            $this->mostrarDetallsComplets($user);

            ldap_close($connection);
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function establirConnexioAutenticada()
    {
        $host = config('ldap.connections.default.hosts.0');
        $port = config('ldap.connections.default.port');

        $connection = ldap_connect($host, $port);
        
        if (!$connection) {
            return false;
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

        $username = config('ldap.connections.default.username');
        $password = config('ldap.connections.default.password');
        
        if (!ldap_bind($connection, $username, $password)) {
            ldap_close($connection);
            return false;
        }

        return $connection;
    }

    private function mostrarDetallsComplets(array $user): void
    {
        $this->info('✅ Usuari trobat:');
        $this->newLine();

        // Informació bàsica
        $basic = [
            ['Nom Complet', $user['cn'][0] ?? 'N/A'],
            ['Username', $user['samaccountname'][0] ?? 'N/A'],
            ['Email', $user['mail'][0] ?? 'N/A'],
            ['Employee ID', $user['employeeid'][0] ?? 'N/A'],
            ['Departament', $user['department'][0] ?? 'N/A'],
            ['Càrrec', $user['title'][0] ?? 'N/A'],
            ['Telèfon', $user['telephonenumber'][0] ?? 'N/A'],
        ];

        $this->table(['Atribut', 'Valor'], $basic);

        // Estat del compte
        $userAccountControl = $user['useraccountcontrol'][0] ?? 0;
        $isActive = !($userAccountControl & 2);
        $isLocked = $userAccountControl & 16;
        $passwordExpired = $userAccountControl & 8388608;

        $this->newLine();
        $this->info('🔐 ESTAT DEL COMPTE:');
        $this->table(['Estat', 'Valor'], [
            ['Actiu', $isActive ? '🟢 Sí' : '🔴 No'],
            ['Bloquetat', $isLocked ? '🔴 Sí' : '🟢 No'],
            ['Contrasenya caducada', $passwordExpired ? '🔴 Sí' : '🟢 No'],
        ]);

        // Grups
        if (isset($user['memberof']) && $user['memberof']['count'] > 0) {
            $this->newLine();
            $this->info('👥 GRUPS LDAP:');
            
            $groups = [];
            for ($i = 0; $i < min($user['memberof']['count'], 10); $i++) {
                if (preg_match('/CN=([^,]+)/', $user['memberof'][$i], $matches)) {
                    $groups[] = [$matches[1]];
                }
            }
            
            if ($user['memberof']['count'] > 10) {
                $groups[] = ['... i ' . ($user['memberof']['count'] - 10) . ' grups més'];
            }
            
            $this->table(['Grup'], $groups);
        }

        // Informació per desenvolupadors
        $this->newLine();
        $this->info('🔧 INFO DESENVOLUPAMENT:');
        $this->table(['Camp', 'Valor'], [
            ['DN', $user['dn'] ?? 'N/A'],
            ['UserAccountControl', $userAccountControl],
        ]);
    }
}