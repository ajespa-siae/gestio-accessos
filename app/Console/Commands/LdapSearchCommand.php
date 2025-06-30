<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LdapSearchCommand extends Command
{
    protected $signature = 'ldap:search
                            {query : Terme de cerca (nom, username, email)}
                            {--filter= : Filtre LDAP personalitzat}
                            {--limit=5 : Límit de resultats (màx 10)}
                            {--exact : Cerca exacta en lloc de parcial}';

    protected $description = 'Cerca usuaris a LDAP amb límits de seguretat';

    public function handle(): int
    {
        $query = $this->argument('query');
        $limit = min($this->option('limit'), 10); // Màxim 10 per seguretat
        $customFilter = $this->option('filter');
        $exact = $this->option('exact');

        $this->info("🔍 CERCANT: {$query}");
        $this->info('=================');

        try {
            $connection = $this->establirConnexioAutenticada();
            if (!$connection) {
                $this->error('❌ No es pot establir connexió LDAP');
                return 1;
            }

            $baseDn = config('ldap.connections.default.base_dn');
            
            // Construir filtre
            if ($customFilter) {
                $filter = $customFilter;
            } else {
                if ($exact) {
                    $filter = "(&(objectClass=user)(|(cn={$query})(samaccountname={$query})(mail={$query})))";
                } else {
                    $filter = "(&(objectClass=user)(objectCategory=person)(!(objectClass=computer))(|(cn=*{$query}*)(samaccountname=*{$query}*)(mail=*{$query}*)))";
                }
            }

            $this->info("Filtre LDAP: {$filter}");
            $this->info("Límit resultats: {$limit}");
            $this->newLine();

            $search = ldap_search(
                $connection,
                $baseDn,
                $filter,
                ['cn', 'samaccountname', 'mail', 'employeeid', 'useraccountcontrol'],
                0,
                $limit
            );

            if (!$search) {
                $error = ldap_error($connection);
                $this->error('❌ Error en la cerca: ' . $error);
                
                if (strpos($error, 'Sizelimit') !== false) {
                    $this->warn('💡 Prova reduir el límit amb --limit=2 o fer cerca més específica');
                }
                
                ldap_close($connection);
                return 1;
            }

            $entries = ldap_get_entries($connection, $search);
            $count = $entries['count'];

            if ($count === 0) {
                $this->warn('⚠️ No s\'han trobat usuaris amb aquest criteri');
                $this->info('💡 Prova amb:');
                $this->info('   - Cerca més específica');
                $this->info('   - Utilitzar --exact per cerca exacta');
                $this->info('   - Verificar que l\'usuari existeix al domini');
                ldap_close($connection);
                return 0;
            }

            $this->info("✅ Trobats {$count} usuaris:");
            $this->newLine();

            $tableData = [];
            for ($i = 0; $i < $count; $i++) {
                $entry = $entries[$i];
                
                $name = $entry['cn'][0] ?? 'N/A';
                $username = $entry['samaccountname'][0] ?? 'N/A';
                $email = $entry['mail'][0] ?? 'N/A';
                $employeeId = $entry['employeeid'][0] ?? 'N/A';
                
                $userAccountControl = $entry['useraccountcontrol'][0] ?? 0;
                $isActive = !($userAccountControl & 2);
                $status = $isActive ? '🟢' : '🔴';
                
                $tableData[] = [$status, $name, $username, $email, $employeeId];
            }

            $this->table(['', 'Nom', 'Username', 'Email', 'Employee ID'], $tableData);

            // Mostrar consells si arriba al límit
            if ($count >= $limit) {
                $this->newLine();
                $this->warn("⚠️ S'ha arribat al límit de {$limit} resultats.");
                $this->info('💡 Pot haver-hi més usuaris. Prova:');
                $this->info('   - Cerca més específica');
                $this->info('   - Augmentar --limit (màx 10)');
            }

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
        ldap_set_option($connection, LDAP_OPT_TIMELIMIT, 30);
        ldap_set_option($connection, LDAP_OPT_SIZELIMIT, 50);

        $username = config('ldap.connections.default.username');
        $password = config('ldap.connections.default.password');
        
        if (!ldap_bind($connection, $username, $password)) {
            ldap_close($connection);
            return false;
        }

        return $connection;
    }
}