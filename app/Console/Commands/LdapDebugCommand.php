<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\User as EloquentUser;
use App\Ldap\User as LdapUser;
use App\Traits\LdapUsernameHelper; // ✅ AFEGIR TRAIT

class LdapDebugCommand extends Command
{
    use LdapUsernameHelper; // ✅ USAR TRAIT

    protected $signature = 'ldap:debug {username : Username a debugar}';
    protected $description = 'Debug complet del procés de sincronització LDAP (case insensitive)';

    public function handle(): int
    {
        $username = $this->argument('username');
        
        $this->info("🔍 DEBUG LDAP per usuari: {$username} (case insensitive)");
        $this->newLine();

        // Pas 1: Verificar connexió LDAP
        if (!$this->testLdapConnection()) {
            return Command::FAILURE;
        }

        // Pas 2: Buscar usuari LDAP (FLEXIBLE)
        $ldapUser = $this->findLdapUserAdvanced($username);
        if (!$ldapUser) {
            return Command::FAILURE;
        }

        // Pas 3: Verificar usuari BD (FLEXIBLE)
        $this->checkDatabaseUserAdvanced($username, $ldapUser);

        // Pas 4: Executar sincronització manual
        $this->executeManualSyncAdvanced($ldapUser);

        // Pas 5: Verificar resultat
        $this->verifyResultAdvanced($username, $ldapUser);

        return Command::SUCCESS;
    }

    private function testLdapConnection(): bool
    {
        $this->info('🔗 Pas 1: Verificant connexió LDAP...');
        
        try {
            $testUser = LdapUser::first();
            
            if ($testUser) {
                $this->info("✅ Connexió LDAP OK - Test usuari trobat");
                return true;
            } else {
                $this->warn("⚠️ Connexió LDAP OK però no hi ha usuaris");
                return true;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error connexió LDAP: " . $e->getMessage());
            $this->error("💡 Verifica la configuració LDAP al .env");
            return false;
        }
    }

    private function findLdapUserAdvanced(string $username): ?LdapUser
    {
        $this->info("👤 Pas 2: Buscant usuari LDAP: {$username} (provant diferents cases)");
        
        try {
            // ✅ USAR MÈTODE FLEXIBLE
            $ldapUser = $this->findLdapUserFlexible($username);
            
            if ($ldapUser) {
                $canonicalUsername = $this->getCanonicalUsername($ldapUser);
                
                $this->info("✅ Usuari LDAP trobat!");
                
                if ($username !== $canonicalUsername) {
                    $this->warn("🔤 USERNAME CASE DIFFERENCE:");
                    $this->line("   Input: {$username}");
                    $this->line("   LDAP: {$canonicalUsername}");
                }
                
                $this->displayLdapUserInfo($ldapUser);
                return $ldapUser;
            } else {
                $this->error("❌ Usuari LDAP no trobat en cap case: {$username}");
                $this->warn("💡 Usuaris disponibles similars:");
                $this->showSimilarLdapUsers($username);
                return null;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error buscant usuari LDAP: " . $e->getMessage());
            return null;
        }
    }

    private function showSimilarLdapUsers(string $username): void
    {
        try {
            // Buscar usuaris amb noms similars
            $similarUsers = LdapUser::where('samaccountname', 'like', substr($username, 0, 3) . '*')->limit(5)->get();
            
            if ($similarUsers->isNotEmpty()) {
                $this->table(['Username LDAP Disponible'], 
                    $similarUsers->map(fn($u) => [$u->getFirstAttribute('samaccountname')])->toArray()
                );
            }
        } catch (\Exception $e) {
            // Ignorar errors en cerca similar
        }
    }

    private function checkDatabaseUserAdvanced(string $inputUsername, LdapUser $ldapUser): void
    {
        $this->info("🗄️ Pas 3: Verificant usuari a BD...");
        
        $canonicalUsername = $this->getCanonicalUsername($ldapUser);
        $email = $ldapUser->getFirstAttribute('mail');
        
        // ✅ Buscar usuari BD flexible
        $dbUserByUsername = $this->findDbUserFlexible($inputUsername);
        $dbUserByEmail = $email ? EloquentUser::whereRaw('LOWER(email) = ?', [strtolower($email)])->first() : null;
        
        if ($dbUserByUsername) {
            if ($dbUserByUsername->username === $canonicalUsername) {
                $this->info("✅ Usuari BD coincideix exactament amb LDAP:");
            } else {
                $this->warn("🔤 USUARI BD AMB CASE DIFERENT:");
                $this->line("   Input: {$inputUsername}");
                $this->line("   BD: {$dbUserByUsername->username}");
                $this->line("   LDAP: {$canonicalUsername}");
                $this->warn("   💡 Recomanació: Actualitzar BD amb case d'LDAP");
            }
            $this->displayDbUserInfo($dbUserByUsername);
        } elseif ($dbUserByEmail) {
            $this->error("❌ CONFLICTE: Usuari amb MATEIX EMAIL però username diferent:");
            $this->line("   Email: {$email}");
            $this->line("   Username BD: {$dbUserByEmail->username}");
            $this->line("   Username LDAP: {$canonicalUsername}");
            $this->displayDbUserInfo($dbUserByEmail);
        } else {
            $this->info("✅ Usuari NO existeix a BD - Es pot crear");
        }
    }

    private function executeManualSyncAdvanced(LdapUser $ldapUser): void
    {
        $this->info("🚀 Pas 4: Executant sincronització manual...");
        
        try {
            $canonicalUsername = $this->getCanonicalUsername($ldapUser);
            $email = $ldapUser->getFirstAttribute('mail');
            
            // Buscar usuaris existents de forma flexible
            $existingUserByUsername = $this->findDbUserFlexible($canonicalUsername);
            $existingUserByEmail = $email ? EloquentUser::whereRaw('LOWER(email) = ?', [strtolower($email)])->first() : null;
            
            if ($existingUserByUsername || $existingUserByEmail) {
                $userToUpdate = $existingUserByUsername ?: $existingUserByEmail;
                
                $this->warn("⚠️ USUARI EXISTENT TROBAT!");
                
                if ($existingUserByUsername && $existingUserByUsername->username !== $canonicalUsername) {
                    $this->warn("🔤 ACTUALITZANT CASE USERNAME:");
                    $this->line("   Actual: {$existingUserByUsername->username}");
                    $this->line("   LDAP: {$canonicalUsername}");
                }
                
                if ($this->confirm('Vols actualitzar l\'usuari existent amb dades LDAP?', true)) {
                    $this->updateExistingUserAdvanced($userToUpdate, $ldapUser);
                } else {
                    $this->warn("⏹️ Operació cancel·lada");
                }
                return;
            }
            
            // Crear usuari nou
            $this->createNewUserAdvanced($ldapUser);
            
        } catch (\Exception $e) {
            $this->error("❌ Error durant sincronització: " . $e->getMessage());
            
            if (str_contains($e->getMessage(), 'unique constraint')) {
                $this->warn("💡 Problema constraint - possiblement case sensitivity resolt parcialment");
            }
        }
    }

    private function updateExistingUserAdvanced(EloquentUser $user, LdapUser $ldapUser): void
    {
        $this->info("🔄 Actualitzant usuari existent ID: {$user->id}");
        
        $originalData = $user->toArray();
        $canonicalUsername = $this->getCanonicalUsername($ldapUser);
        
        $employeeId = $ldapUser->getFirstAttribute('employeeid');
        $displayName = $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname');
        $email = $ldapUser->getFirstAttribute('mail');

        // Mostrar canvis que es faran
        if ($user->username !== $canonicalUsername) {
            $this->warn("🔤 IMPORTANT: CORRIGINT USERNAME CASE:");
            $this->line("   Username BD actual: {$user->username}");
            $this->line("   Username LDAP canònic: {$canonicalUsername}");
            $this->line("   → Actualitzant a versió canònica d'LDAP");
        }

        $user->update([
            'name' => $displayName ?: $user->name,
            'email' => $email ?: $user->email,
            'username' => $canonicalUsername, // ✅ SEMPRE usar case canònic d'LDAP
            'nif' => $employeeId ?: $user->nif,
            'actiu' => true
        ]);

        $this->info("✅ Usuari actualitzat correctament");
        
        // Mostrar canvis aplicats
        $changes = [];
        $freshUser = $user->fresh();
        foreach (['name', 'email', 'username', 'nif'] as $field) {
            if ($originalData[$field] !== $freshUser->$field) {
                $changes[] = [
                    $field,
                    $originalData[$field] ?: 'buit',
                    $freshUser->$field ?: 'buit'
                ];
            }
        }
        
        if (!empty($changes)) {
            $this->table(['Camp', 'Abans', 'Després'], $changes);
        } else {
            $this->info("ℹ️ No hi ha hagut canvis en les dades");
        }
    }

    private function createNewUserAdvanced(LdapUser $ldapUser): void
    {
        $this->info("📝 Creant usuari nou...");
        
        $canonicalUsername = $this->getCanonicalUsername($ldapUser);
        $employeeId = $ldapUser->getFirstAttribute('employeeid');
        $displayName = $ldapUser->getFirstAttribute('cn') ?: $ldapUser->getFirstAttribute('displayname');
        $email = $ldapUser->getFirstAttribute('mail');

        $this->info("📋 Dades per crear (amb username canònic):");
        $this->table([
            ['Camp', 'Valor']
        ], [
            ['name', $displayName ?: $canonicalUsername],
            ['email', $email ?: strtolower($canonicalUsername) . "@esparreguera.cat"],
            ['username', $canonicalUsername], // ✅ USERNAME CANÒNIC
            ['nif', $employeeId],
            ['rol_principal', 'empleat']
        ]);

        if ($this->confirm('Vols crear aquest usuari?', true)) {
            $newUser = EloquentUser::create([
                'name' => $displayName ?: $canonicalUsername,
                'email' => $email ?: strtolower($canonicalUsername) . "@esparreguera.cat",
                'username' => $canonicalUsername, // ✅ SEMPRE username canònic d'LDAP
                'nif' => $employeeId,
                'password' => Hash::make(Str::random(32)),
                'rol_principal' => 'empleat',
                'actiu' => true,
                'email_verified_at' => now()
            ]);

            $this->info("✅ Usuari creat amb ID: {$newUser->id}");
        } else {
            $this->warn("⏹️ Creació cancel·lada");
        }
    }

    private function verifyResultAdvanced(string $inputUsername, LdapUser $ldapUser): void
    {
        $this->info("✅ Pas 5: Verificant resultat...");
        
        $canonicalUsername = $this->getCanonicalUsername($ldapUser);
        
        // Buscar per username canònic
        $user = EloquentUser::where('username', $canonicalUsername)->first();
        
        if ($user) {
            $this->info("🎉 ÈXIT! Usuari trobat a BD:");
            $this->displayDbUserInfo($user);
            
            if ($inputUsername !== $canonicalUsername) {
                $this->info("🔤 CASE NORMALIZATION APPLIED:");
                $this->line("   Input: {$inputUsername}");
                $this->line("   Stored: {$user->username}");
                $this->info("   ✅ Ara cerques amb qualsevol case funcionaran!");
            }
        } else {
            $this->error("❌ PROBLEMA: Usuari NO trobat a BD després de la sincronització");
            
            // Buscar flexiblement
            $userFlexible = $this->findDbUserFlexible($inputUsername);
            if ($userFlexible) {
                $this->warn("⚠️ Però usuari trobat per cerca flexible:");
                $this->displayDbUserInfo($userFlexible);
            }
        }
    }

    private function displayLdapUserInfo(LdapUser $ldapUser): void
    {
        $this->info('👤 Informació usuari LDAP:');
        $this->table([
            ['Camp', 'Valor']
        ], [
            ['Username (samaccountname)', $ldapUser->getFirstAttribute('samaccountname')],
            ['Nom complet (cn)', $ldapUser->getFirstAttribute('cn')],
            ['Email (mail)', $ldapUser->getFirstAttribute('mail') ?: 'No especificat'],
            ['Employee ID', $ldapUser->getFirstAttribute('employeeid') ?: 'No especificat'],
            ['Display Name', $ldapUser->getFirstAttribute('displayname') ?: 'No especificat']
        ]);
    }

    private function displayDbUserInfo(EloquentUser $user): void
    {
        $this->table([
            ['Camp BD', 'Valor']
        ], [
            ['ID', $user->id],
            ['Name', $user->name],
            ['Email', $user->email],
            ['Username', $user->username],
            ['NIF/Employee ID', $user->nif ?: 'No especificat'],
            ['Rol principal', $user->rol_principal],
            ['Actiu', $user->actiu ? '✅ Sí' : '❌ No'],
            ['Creat', $user->created_at->format('Y-m-d H:i:s')]
        ]);
    }
}