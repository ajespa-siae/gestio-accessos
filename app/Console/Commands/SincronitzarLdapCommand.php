<?php
// app/Console/Commands/SincronitzarLdapCommand.php
// ✅ FIX: Assegurar que les opcions es passen correctament als Jobs

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use App\Models\User as EloquentUser;
use App\Ldap\User as LdapUser;
use App\Jobs\SincronitzarUsuarisLDAP;
use App\Jobs\SincronitzarUsuariLDAP;

class SincronitzarLdapCommand extends Command
{
    protected $signature = 'ldap:sync
                            {--user= : Usuari específic a sincronitzar}
                            {--force : Forçar actualització de dades existents}
                            {--create-new : Crear usuaris nous si no existeixen}
                            {--dry-run : Mostrar què es faria sense executar}';

    protected $description = 'Sincronitza usuaris des d\'Active Directory amb la base de dades';

    public function handle(): int
    {
        $this->info('🔄 Iniciiant sincronització LDAP amb Active Directory');
        $this->newLine();

        // Obtenir opcions
        $specificUser = $this->option('user');
        $forceUpdate = $this->option('force');
        $createNew = $this->option('create-new');
        $dryRun = $this->option('dry-run');

        // Mostrar configuració
        $this->displayConfiguration($specificUser, $forceUpdate, $createNew, $dryRun);

        if ($dryRun) {
            return $this->executeDryRun($specificUser);
        }

        try {
            if ($specificUser) {
                return $this->syncSpecificUser($specificUser, $forceUpdate, $createNew);
            } else {
                return $this->syncAllUsers($forceUpdate, $createNew); // ✅ FIX: Passar createNew
            }
        } catch (\Exception $e) {
            $this->error("❌ Error durant la sincronització: " . $e->getMessage());
            Log::error('Error sincronització LDAP command', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    private function displayConfiguration(?string $user, bool $force, bool $createNew, bool $dryRun): void
    {
        $this->info('📋 Configuració de sincronització:');
        $this->table([
            ['Paràmetre', 'Valor']
        ], [
            ['Usuari específic', $user ?: 'Tots els usuaris'],
            ['Forçar actualització', $force ? '✅ Sí' : '❌ No'],
            ['Crear usuaris nous', $createNew ? '✅ Sí' : '❌ No'],
            ['Mode dry-run', $dryRun ? '✅ Sí' : '❌ No']
        ]);
        $this->newLine();
    }

    private function syncSpecificUser(string $username, bool $forceUpdate, bool $createNew): int
    {
        $this->info("🎯 Sincronitzant usuari específic: {$username}");

        // Verificar usuari LDAP existeix
        $ldapUser = LdapUser::where('samaccountname', $username)->first();
        
        if (!$ldapUser) {
            $this->error("❌ Usuari no trobat a LDAP: {$username}");
            return Command::FAILURE;
        }

        // Verificar usuari BD
        $dbUser = EloquentUser::where('username', $username)->first();
        
        if (!$dbUser && !$createNew) {
            $this->error("❌ Usuari no existeix a BD i --create-new no especificat: {$username}");
            $this->warn("💡 Usa --create-new per crear-lo automàticament");
            return Command::FAILURE;
        }

        // Mostrar informació usuari LDAP
        $this->displayLdapUserInfo($ldapUser);

        if ($this->confirm('Vols continuar amb la sincronització?', true)) {
            $this->info('🚀 Executant sincronització...');
            
            // ✅ FIX: Passar totes les opcions correctament
            SincronitzarUsuariLDAP::dispatch($username, $forceUpdate, $createNew);
            
            $this->info("✅ Job de sincronització encuat per: {$username}");
            $this->info("📊 Pots veure el resultat als logs de Laravel");
            
            return Command::SUCCESS;
        }

        $this->warn('⏹️ Sincronització cancel·lada per l\'usuari');
        return Command::SUCCESS;
    }

    // ✅ FIX: Afegir paràmetre createNew que faltava
    private function syncAllUsers(bool $forceUpdate, bool $createNew = false): int
    {
        $this->info('🌐 Sincronitzant TOTS els usuaris LDAP');

        // ✅ FIX: Obtenir comptador usuaris LDAP correctament
        $ldapUsers = LdapUser::get();
        $ldapCount = $ldapUsers->count();
        $this->info("📊 Usuaris trobats a LDAP: {$ldapCount}");

        // Obtenir comptador usuaris BD
        $dbCount = EloquentUser::count();
        $this->info("📊 Usuaris existents a BD: {$dbCount}");

        if ($ldapCount === 0) {
            $this->warn('⚠️ No s\'han trobat usuaris a LDAP');
            return Command::SUCCESS;
        }

        // ✅ Mostrar què es farà
        $this->info('🎯 Opcions de sincronització:');
        $this->line("   - Forçar actualització: " . ($forceUpdate ? '✅' : '❌'));
        $this->line("   - Crear usuaris nous: " . ($createNew ? '✅' : '❌'));
        $this->newLine();

        if ($this->confirm("Vols sincronitzar tots els {$ldapCount} usuaris amb aquestes opcions?", true)) {
            $this->info('🚀 Executant sincronització massiva...');
            
            // ✅ FIX: Passar AMBDUES opcions al Job
            SincronitzarUsuarisLDAP::dispatch($forceUpdate, null, $createNew);
            
            $this->info("✅ Job de sincronització massiva encuat");
            $this->info("📊 Opcions passades al Job:");
            $this->line("   - forceUpdate: " . ($forceUpdate ? 'true' : 'false'));
            $this->line("   - createNew: " . ($createNew ? 'true' : 'false'));
            $this->info("📊 Pots veure el progrés als logs de Laravel");
            $this->warn("⏱️ Això pot trigar uns minuts depenent del nombre d'usuaris");
            
            return Command::SUCCESS;
        }

        $this->warn('⏹️ Sincronització cancel·lada per l\'usuari');
        return Command::SUCCESS;
    }

    private function executeDryRun(?string $specificUser): int
    {
        $this->warn('🔍 MODE DRY-RUN: No s\'executaran canvis reals');
        $this->newLine();

        if ($specificUser) {
            return $this->dryRunSpecificUser($specificUser);
        } else {
            return $this->dryRunAllUsers();
        }
    }

    private function dryRunSpecificUser(string $username): int
    {
        $this->info("🔍 Analitzant usuari: {$username}");

        // Verificar LDAP
        $ldapUser = LdapUser::where('samaccountname', $username)->first();
        
        if (!$ldapUser) {
            $this->error("❌ Usuari no trobat a LDAP: {$username}");
            return Command::FAILURE;
        }

        // Verificar BD
        $dbUser = EloquentUser::where('username', $username)->first();

        $this->displayLdapUserInfo($ldapUser);
        
        if ($dbUser) {
            $this->warn("ℹ️ Usuari JA existeix a BD:");
            $this->displayDbUserInfo($dbUser);
            $this->info("🔄 Amb --force s'actualitzaria amb dades LDAP");
        } else {
            $this->info("🆕 Usuari NO existeix a BD");
            $this->info("➕ Amb --create-new es crearia automàticament");
        }

        return Command::SUCCESS;
    }

    private function dryRunAllUsers(): int
    {
        $this->info('🔍 Analitzant tots els usuaris LDAP...');

        // ✅ FIX: Obtenir usuaris LDAP correctament
        $ldapUsers = LdapUser::get();
        $existingCount = 0;
        $newCount = 0;

        foreach ($ldapUsers as $ldapUser) {
            $username = $ldapUser->getFirstAttribute('samaccountname');
            if (!$username) continue;

            // Verificar si existeix a BD
            $exists = EloquentUser::where('username', $username)->exists();
            
            if ($exists) {
                $existingCount++;
            } else {
                $newCount++;
            }
        }

        $this->info("📊 Resultat anàlisi:");
        $this->table([
            ['Categoria', 'Quantitat', 'Acció']
        ], [
            ['Usuaris existents BD', $existingCount, 'Actualitzar si --force'],
            ['Usuaris nous', $newCount, 'Crear si --create-new'],
            ['Total LDAP', $ldapUsers->count(), 'Processar tots'] // ✅ FIX: count() sobre collection
        ]);

        return Command::SUCCESS;
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
            ['Nom', $user->name],
            ['Email', $user->email],
            ['Username', $user->username],
            ['NIF/Employee ID', $user->nif ?: 'No especificat'],
            ['Rol principal', $user->rol_principal],
            ['Actiu', $user->actiu ? '✅ Sí' : '❌ No'],
            ['Creat', $user->created_at->format('Y-m-d H:i:s')]
        ]);
    }
}