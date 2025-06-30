<?php
// app/Console/Commands/DebugLdapAuthCommand.php
// Command per debugar completament el procés d'autenticació LDAP

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Ldap\User as LdapUser;
use App\Models\User as EloquentUser;
use LdapRecord\Connection;

class DebugLdapAuthCommand extends Command
{
    protected $signature = 'ldap:debug-auth {username} {password}';
    protected $description = 'Debug complet del procés d\'autenticació LDAP';

    public function handle(): int
    {
        $username = $this->argument('username');
        $password = $this->argument('password');
        
        $this->info("🔐 DEBUG AUTENTICACIÓ LDAP");
        $this->info("Username: {$username}");
        $this->info("Password: " . str_repeat('*', strlen($password)));
        $this->newLine();

        // Pas 1: Verificar configuració
        if (!$this->verifyConfiguration()) {
            return Command::FAILURE;
        }

        // Pas 2: Test connexió LDAP
        if (!$this->testLdapConnection()) {
            return Command::FAILURE;
        }

        // Pas 3: Buscar usuari LDAP
        $ldapUser = $this->findLdapUser($username);
        if (!$ldapUser) {
            return Command::FAILURE;
        }

        // Pas 4: Test autenticació LDAP directa
        $this->testDirectLdapAuth($username, $password);

        // Pas 5: Test autenticació Laravel
        $this->testLaravelAuth($username, $password);

        // Pas 6: Verificar usuari BD
        $this->checkDatabaseUser($username);

        return Command::SUCCESS;
    }

    private function verifyConfiguration(): bool
    {
        $this->info("📋 Pas 1: Verificant configuració...");

        $webGuard = config('auth.guards.web');
        $ldapProvider = config('auth.providers.ldap');
        $ldapConfig = config('ldap.connections.default');

        $this->table([
            ['Configuració', 'Valor', 'Correcte']
        ], [
            ['Web Guard Provider', $webGuard['provider'], $webGuard['provider'] === 'ldap' ? '✅' : '❌'],
            ['LDAP Provider Model', $ldapProvider['model'] ?? 'NO DEFINIT', ($ldapProvider['model'] ?? '') === 'App\\Ldap\\User' ? '✅' : '❌'],
            ['LDAP Host', $ldapConfig['hosts'][0] ?? 'NO DEFINIT', !empty($ldapConfig['hosts'][0]) ? '✅' : '❌'],
            ['LDAP Base DN', $ldapConfig['base_dn'] ?? 'NO DEFINIT', !empty($ldapConfig['base_dn']) ? '✅' : '❌'],
            ['LDAP Username', $ldapConfig['username'] ?? 'NO DEFINIT', !empty($ldapConfig['username']) ? '✅' : '❌'],
        ]);

        $errors = [];
        if ($webGuard['provider'] !== 'ldap') {
            $errors[] = "Guard web no usa provider LDAP";
        }
        if (($ldapProvider['model'] ?? '') !== 'App\\Ldap\\User') {
            $errors[] = "Provider LDAP no usa model correcte";
        }
        if (empty($ldapConfig['hosts'][0])) {
            $errors[] = "LDAP Host no definit";
        }

        if (!empty($errors)) {
            $this->error("❌ Errors de configuració:");
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
            return false;
        }

        $this->info("✅ Configuració correcta");
        return true;
    }

    private function testLdapConnection(): bool
    {
        $this->info("🔗 Pas 2: Test connexió LDAP...");

        try {
            // Test connexió bàsica
            $connection = new Connection(config('ldap.connections.default'));
            $connection->connect();
            
            $this->info("✅ Connexió LDAP establerta");

            // Test autenticació connexió
            $bindResult = $connection->auth()->attempt(
                config('ldap.connections.default.username'),
                config('ldap.connections.default.password')
            );

            if ($bindResult) {
                $this->info("✅ Autenticació connexió LDAP correcta");
            } else {
                $this->error("❌ Autenticació connexió LDAP fallida");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->error("❌ Error connexió LDAP: " . $e->getMessage());
            $this->warn("💡 Verifica configuració LDAP al .env");
            return false;
        }
    }

    private function findLdapUser(string $username): ?LdapUser
    {
        $this->info("👤 Pas 3: Buscant usuari LDAP...");

        try {
            // Provar diferents casos
            $variations = [
                $username,                    // Exacte
                strtolower($username),       // Minúscules
                ucfirst(strtolower($username)), // Primera majúscula
                strtoupper($username),       // Majúscules
            ];

            $ldapUser = null;
            $foundVariation = null;

            foreach ($variations as $variation) {
                $ldapUser = LdapUser::where('samaccountname', $variation)->first();
                if ($ldapUser) {
                    $foundVariation = $variation;
                    break;
                }
            }

            if ($ldapUser) {
                $this->info("✅ Usuari LDAP trobat!");
                if ($foundVariation !== $username) {
                    $this->warn("🔤 Case difference: input '{$username}' → trobat '{$foundVariation}'");
                }

                // Mostrar detalls usuari LDAP
                $this->table([
                    ['Atribut LDAP', 'Valor']
                ], [
                    ['samaccountname', $ldapUser->getFirstAttribute('samaccountname')],
                    ['cn', $ldapUser->getFirstAttribute('cn')],
                    ['displayname', $ldapUser->getFirstAttribute('displayname')],
                    ['mail', $ldapUser->getFirstAttribute('mail') ?: 'No definit'],
                    ['employeeid', $ldapUser->getFirstAttribute('employeeid') ?: 'No definit'],
                    ['useraccountcontrol', $ldapUser->getFirstAttribute('useraccountcontrol') ?: 'No definit'],
                    ['dn', $ldapUser->getDn()],
                ]);

                // Verificar estat compte
                $uac = $ldapUser->getFirstAttribute('useraccountcontrol');
                if ($uac) {
                    $isDisabled = ($uac & 2) !== 0; // Bit 1 = compte desactivat
                    $this->line("Estat compte: " . ($isDisabled ? "❌ DESACTIVAT" : "✅ ACTIU"));
                }

                return $ldapUser;
            } else {
                $this->error("❌ Usuari LDAP no trobat amb cap variació");
                $this->warn("Variacions provades: " . implode(', ', $variations));
                
                // Mostrar usuaris similars
                $this->showSimilarUsers($username);
                return null;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error buscant usuari LDAP: " . $e->getMessage());
            return null;
        }
    }

    private function showSimilarUsers(string $username): void
    {
        try {
            $this->info("🔍 Usuaris similars a LDAP:");
            
            // Buscar usuaris que comencin amb les primeres 3 lletres
            $prefix = substr($username, 0, 3);
            $similarUsers = LdapUser::where('samaccountname', 'like', $prefix . '*')->limit(10)->get();
            
            if ($similarUsers->isNotEmpty()) {
                $users = $similarUsers->map(fn($u) => [
                    $u->getFirstAttribute('samaccountname'),
                    $u->getFirstAttribute('cn') ?: 'No nom'
                ])->toArray();
                
                $this->table(['Username', 'Nom'], $users);
            } else {
                $this->line("No s'han trobat usuaris similars");
            }
        } catch (\Exception $e) {
            // Ignorar errors en cerca similar
        }
    }

    private function testDirectLdapAuth(string $username, string $password): void
    {
        $this->info("🔐 Pas 4: Test autenticació LDAP directa...");

        try {
            $connection = new Connection(config('ldap.connections.default'));
            $connection->connect();

            // Provar autenticació directa amb diferents formats
            $formats = [
                $username,
                $username . '@' . config('ldap.connections.default.base_dn'),
                'cn=' . $username . ',' . config('ldap.connections.default.base_dn'),
            ];

            foreach ($formats as $format) {
                try {
                    $this->line("Provant format: {$format}");
                    $result = $connection->auth()->attempt($format, $password);
                    
                    if ($result) {
                        $this->info("✅ Autenticació LDAP directa SUCCESSFUL amb format: {$format}");
                        return;
                    } else {
                        $this->line("❌ Falla amb format: {$format}");
                    }
                } catch (\Exception $e) {
                    $this->line("❌ Error amb format {$format}: " . $e->getMessage());
                }
            }

            $this->error("❌ Autenticació LDAP directa FAILED amb tots els formats");
            $this->warn("💡 Possibles causes:");
            $this->line("   - Password incorrecte");
            $this->line("   - Compte LDAP bloquejat o desactivat");
            $this->line("   - Format username incorrecte");

        } catch (\Exception $e) {
            $this->error("❌ Error test autenticació directa: " . $e->getMessage());
        }
    }

    private function testLaravelAuth(string $username, string $password): void
    {
        $this->info("🚀 Pas 5: Test autenticació Laravel...");

        try {
            // Activar logging per veure què passa internament
            $originalLogLevel = Log::getLogger()->getHandlers()[0]->getLevel() ?? null;
            
            $this->line("Provant Auth::attempt() amb logging activat...");
            
            $result = Auth::attempt([
                'username' => $username,
                'password' => $password
            ]);

            if ($result) {
                $this->info("✅ Autenticació Laravel SUCCESSFUL!");
                $user = Auth::user();
                $this->table([
                    ['Camp', 'Valor']
                ], [
                    ['ID', $user->id],
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Username', $user->username],
                    ['NIF', $user->nif ?? 'No definit'],
                ]);
                
                Auth::logout(); // Netejar per tests següents
            } else {
                $this->error("❌ Autenticació Laravel FAILED");
                
                $this->warn("💡 Possibles causes:");
                $this->line("   - Usuari no sincronitzat a BD");
                $this->line("   - Mapejat atributs incorrecte");
                $this->line("   - Password incorrecte a LDAP");
                $this->line("   - Configuració sync_existing incorrecta");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error test autenticació Laravel: " . $e->getMessage());
        }
    }

    private function checkDatabaseUser(string $username): void
    {
        $this->info("🗄️ Pas 6: Verificant usuari BD...");

        try {
            // Buscar per username exacte
            $userExact = EloquentUser::where('username', $username)->first();
            
            // Buscar case insensitive
            $userCaseInsensitive = EloquentUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();

            if ($userExact) {
                $this->info("✅ Usuari trobat a BD (case exacte):");
                $this->displayDbUser($userExact);
            } elseif ($userCaseInsensitive) {
                $this->warn("⚠️ Usuari trobat a BD (case insensitive):");
                $this->line("   Input: {$username}");
                $this->line("   BD: {$userCaseInsensitive->username}");
                $this->displayDbUser($userCaseInsensitive);
                $this->warn("💡 Problema case sensitivity - cal sync amb LDAP");
            } else {
                $this->error("❌ Usuari NO trobat a BD");
                $this->warn("💡 Cal sincronitzar des d'LDAP:");
                $this->line("   php artisan ldap:sync --user={$username} --create-new");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error verificant usuari BD: " . $e->getMessage());
        }
    }

    private function displayDbUser(EloquentUser $user): void
    {
        $this->table([
            ['Camp BD', 'Valor']
        ], [
            ['ID', $user->id],
            ['Name', $user->name],
            ['Email', $user->email],
            ['Username', $user->username],
            ['NIF', $user->nif ?? 'No definit'],
            ['Rol', $user->rol_principal],
            ['Actiu', $user->actiu ? 'Sí' : 'No'],
            ['Created', $user->created_at->format('Y-m-d H:i:s')],
            ['Updated', $user->updated_at->format('Y-m-d H:i:s')],
        ]);
    }
}