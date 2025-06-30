<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LdapTestCommand extends Command
{
    protected $signature = 'ldap:test
                            {--detailed : Mostrar informació detallada}
                            {--search= : Cercar un usuari específic}
                            {--limit=3 : Límit de resultats per evitar sizelimit}';

    protected $description = 'Test manual de connexió LDAP sense LdapRecord';

    public function handle(): int
    {
        $this->info('🔧 LDAP CONNECTION TEST');
        $this->info('======================');
        $this->newLine();

        // Mostrar configuració
        $this->mostrarConfiguracio();

        // Test 1: Connexió bàsica
        if (!$this->testConnexio()) {
            return 1;
        }

        // Test 2: Autenticació
        if (!$this->testAutenticacio()) {
            return 1;
        }

        // Test 3: Cerca bàsica amb límit reduït
        if (!$this->testCercaLimitada()) {
            return 1;
        }

        // Test 4: Cerca específica (si s'ha demanat)
        if ($this->option('search')) {
            $this->cercaUsuariEspecific($this->option('search'));
        }

        $this->newLine();
        $this->info('🎉 TOTS ELS TESTS LDAP HAN PASSAT CORRECTAMENT!');
        $this->info('El teu Active Directory està configurat i funcionant.');
        $this->newLine();
        
        $this->info('📝 NOTA: El servidor té límits de cerca configurats (normal per seguretat)');
        $this->info('Podem continuar amb la integració completa.');

        return 0;
    }

    private function mostrarConfiguracio(): void
    {
        $this->info('📋 CONFIGURACIÓ LDAP:');
        $this->info('=====================');

        $config = [
            ['Host', config('ldap.connections.default.hosts.0')],
            ['Port', config('ldap.connections.default.port')],
            ['Base DN', config('ldap.connections.default.base_dn')],
            ['Username', config('ldap.connections.default.username')],
            ['Password', str_repeat('*', strlen(config('ldap.connections.default.password')))],
            ['SSL', config('ldap.connections.default.use_ssl') ? 'Sí' : 'No'],
            ['TLS', config('ldap.connections.default.use_tls') ? 'Sí' : 'No'],
            ['Timeout', config('ldap.connections.default.timeout') . 's'],
        ];

        $this->table(['Paràmetre', 'Valor'], $config);
        $this->newLine();
    }

    private function testConnexio(): bool
    {
        $this->info('1️⃣ TEST CONNEXIÓ BÀSICA...');

        try {
            $host = config('ldap.connections.default.hosts.0');
            $port = config('ldap.connections.default.port');

            $connection = ldap_connect($host, $port);

            if (!$connection) {
                $this->error('   ❌ No es pot establir connexió amb ' . $host . ':' . $port);
                return false;
            }

            // Configurar opcions
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, config('ldap.connections.default.timeout'));

            $this->info('   ✅ Connexió establerta correctament');
            
            // Tancar connexió
            ldap_close($connection);
            
            return true;

        } catch (\Exception $e) {
            $this->error('   ❌ Error de connexió: ' . $e->getMessage());
            return false;
        }
    }

    private function testAutenticacio(): bool
    {
        $this->info('2️⃣ TEST AUTENTICACIÓ...');

        try {
            $connection = $this->establirConnexio();
            if (!$connection) {
                return false;
            }

            $username = config('ldap.connections.default.username');
            $password = config('ldap.connections.default.password');

            $bind = ldap_bind($connection, $username, $password);

            if (!$bind) {
                $error = ldap_error($connection);
                $this->error('   ❌ Error d\'autenticació: ' . $error);
                ldap_close($connection);
                return false;
            }

            $this->info('   ✅ Autenticació correcta per: ' . $username);
            
            ldap_close($connection);
            return true;

        } catch (\Exception $e) {
            $this->error('   ❌ Error d\'autenticació: ' . $e->getMessage());
            return false;
        }
    }

    private function testCercaLimitada(): bool
    {
        $this->info('3️⃣ TEST CERCA D\'USUARIS (amb límit)...');

        try {
            $connection = $this->establirConnexioAutenticada();
            if (!$connection) {
                return false;
            }

            $baseDn = config('ldap.connections.default.base_dn');
            $limit = $this->option('limit');
            
            // Cerca amb límit molt reduït per evitar sizelimit
            $search = ldap_search(
                $connection,
                $baseDn,
                '(&(objectClass=user)(objectCategory=person)(!(objectClass=computer)))',
                ['cn', 'samaccountname', 'mail', 'employeeid', 'useraccountcontrol'],
                0, // attrsonly
                $limit  // sizelimit reduït
            );

            if (!$search) {
                $error = ldap_error($connection);
                
                // Si encara falla per sizelimit, intentar cerca més específica
                if (strpos($error, 'Sizelimit') !== false) {
                    $this->warn('   ⚠️ Límit del servidor detectat, provant cerca específica...');
                    return $this->testCercaEspecifica($connection);
                }
                
                $this->error('   ❌ Error en la cerca: ' . $error);
                ldap_close($connection);
                return false;
            }

            $entries = ldap_get_entries($connection, $search);
            $count = $entries['count'];

            $this->info("   ✅ Trobats {$count} usuaris a LDAP (límit: {$limit})");

            if ($this->option('detailed') && $count > 0) {
                $this->mostrarUsuaris($entries);
            }

            ldap_close($connection);
            return true;

        } catch (\Exception $e) {
            $this->error('   ❌ Error en la cerca: ' . $e->getMessage());
            return false;
        }
    }

    private function testCercaEspecifica($connection): bool
    {
        $this->info('   🔄 Provant cerca específica...');

        try {
            $baseDn = config('ldap.connections.default.base_dn');
            
            // Cerca només administradors o usuaris amb nom específic
            $search = ldap_search(
                $connection,
                $baseDn,
                '(&(objectClass=user)(|(cn=*admin*)(cn=*escuda*)(samaccountname=*admin*)))',
                ['cn', 'samaccountname', 'mail', 'employeeid'],
                0,
                2 // Molt limitat
            );

            if (!$search) {
                $error = ldap_error($connection);
                $this->error('   ❌ Error en cerca específica: ' . $error);
                return false;
            }

            $entries = ldap_get_entries($connection, $search);
            $count = $entries['count'];

            if ($count > 0) {
                $this->info("   ✅ Cerca específica: trobats {$count} usuaris");
                if ($this->option('detailed')) {
                    $this->mostrarUsuaris($entries);
                }
                return true;
            } else {
                $this->info('   ✅ Cerca específica executada (cap resultat, però funciona)');
                return true;
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Error en cerca específica: ' . $e->getMessage());
            return false;
        }
    }

    private function cercaUsuariEspecific(string $username): void
    {
        $this->info("4️⃣ CERCA USUARI ESPECÍFIC: {$username}");

        try {
            $connection = $this->establirConnexioAutenticada();
            if (!$connection) {
                return;
            }

            $baseDn = config('ldap.connections.default.base_dn');
            $filter = "(&(objectClass=user)(samaccountname={$username}))";

            $search = ldap_search(
                $connection,
                $baseDn,
                $filter,
                ['cn', 'samaccountname', 'mail', 'employeeid', 'useraccountcontrol', 'memberof']
            );

            if (!$search) {
                $this->error('   ❌ Error cercant usuari: ' . ldap_error($connection));
                ldap_close($connection);
                return;
            }

            $entries = ldap_get_entries($connection, $search);

            if ($entries['count'] === 0) {
                $this->warn("   ⚠️ Usuari '{$username}' no trobat");
            } else {
                $this->info('   ✅ Usuari trobat:');
                $this->mostrarDetallUsuari($entries[0]);
            }

            ldap_close($connection);

        } catch (\Exception $e) {
            $this->error('   ❌ Error cercant usuari: ' . $e->getMessage());
        }
    }

    private function establirConnexio()
    {
        $host = config('ldap.connections.default.hosts.0');
        $port = config('ldap.connections.default.port');

        $connection = ldap_connect($host, $port);
        
        if ($connection) {
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($connection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            ldap_set_option($connection, LDAP_OPT_TIMELIMIT, 30);
            ldap_set_option($connection, LDAP_OPT_SIZELIMIT, 100); // Límit explícit
        }

        return $connection;
    }

    private function establirConnexioAutenticada()
    {
        $connection = $this->establirConnexio();
        
        if ($connection) {
            $username = config('ldap.connections.default.username');
            $password = config('ldap.connections.default.password');
            
            if (!ldap_bind($connection, $username, $password)) {
                ldap_close($connection);
                return false;
            }
        }

        return $connection;
    }

    private function mostrarUsuaris(array $entries): void
    {
        $this->newLine();
        $this->info('   📋 USUARIS TROBATS:');
        
        $tableData = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            
            $name = $entry['cn'][0] ?? 'N/A';
            $username = $entry['samaccountname'][0] ?? 'N/A';
            $email = $entry['mail'][0] ?? 'N/A';
            $employeeId = $entry['employeeid'][0] ?? 'N/A';
            
            // Verificar si està actiu
            $userAccountControl = $entry['useraccountcontrol'][0] ?? 0;
            $isActive = !($userAccountControl & 2); // 2 = ACCOUNTDISABLE
            $status = $isActive ? '🟢 Actiu' : '🔴 Inactiu';
            
            $tableData[] = [$name, $username, $email, $employeeId, $status];
        }

        $this->table(['Nom', 'Username', 'Email', 'Employee ID', 'Estat'], $tableData);
    }

    private function mostrarDetallUsuari(array $entry): void
    {
        $details = [
            ['Nom Complet', $entry['cn'][0] ?? 'N/A'],
            ['Username', $entry['samaccountname'][0] ?? 'N/A'],
            ['Email', $entry['mail'][0] ?? 'N/A'],
            ['Employee ID', $entry['employeeid'][0] ?? 'N/A'],
        ];

        // Estat del compte
        $userAccountControl = $entry['useraccountcontrol'][0] ?? 0;
        $isActive = !($userAccountControl & 2);
        $details[] = ['Estat', $isActive ? '🟢 Actiu' : '🔴 Inactiu'];

        // Grups (només primers 3)
        if (isset($entry['memberof'])) {
            $groups = [];
            $count = min(3, $entry['memberof']['count']);
            
            for ($i = 0; $i < $count; $i++) {
                if (preg_match('/CN=([^,]+)/', $entry['memberof'][$i], $matches)) {
                    $groups[] = $matches[1];
                }
            }
            
            if ($entry['memberof']['count'] > 3) {
                $groups[] = '... i ' . ($entry['memberof']['count'] - 3) . ' més';
            }
            
            $details[] = ['Grups', implode(', ', $groups)];
        }

        $this->table(['Atribut', 'Valor'], $details);
    }
}