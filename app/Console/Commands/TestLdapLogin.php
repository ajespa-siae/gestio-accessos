<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Traits\LdapUsernameHelper;

class TestLdapLogin extends Command
{
    use LdapUsernameHelper;

    protected $signature = 'ldap:test-login {username} {password}';
    protected $description = 'Test login LDAP amb username case insensitive';

    public function handle(): int
    {
        $username = $this->argument('username');
        $password = $this->argument('password');
        
        $this->info("🔐 Testant login LDAP: {$username}");
        
        // Buscar usuari LDAP flexible
        $ldapUser = $this->findLdapUserFlexible($username);
        
        if (!$ldapUser) {
            $this->error("❌ Usuari no trobat a LDAP");
            return Command::FAILURE;
        }
        
        $canonicalUsername = $this->getCanonicalUsername($ldapUser);
        
        // Test autenticació amb username canònic
        if (Auth::attempt(['username' => $canonicalUsername, 'password' => $password])) {
            $this->info("✅ LOGIN SUCCESSFUL!");
            $this->line("   Input username: {$username}");
            $this->line("   Canonical username: {$canonicalUsername}");
            $this->line("   User ID: " . Auth::id());
            return Command::SUCCESS;
        } else {
            $this->error("❌ LOGIN FAILED - credentials incorrectes");
            return Command::FAILURE;
        }
    }
}