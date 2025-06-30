<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestUpnAuthCommand extends Command
{
    protected $signature = 'ldap:test-upn {username} {password}';
    protected $description = 'Test autenticació amb configuració UPN nova';

    public function handle(): int
    {
        $username = $this->argument('username');
        $password = $this->argument('password');

        $this->info("🔐 TEST AUTENTICACIÓ UPN - CONFIGURACIÓ NOVA");
        $this->info("Username: {$username}");
        $this->newLine();

        // Test amb case exacte
        $this->testAuth($username, $password, "Case exacte");

        // Test amb minúscules  
        $this->testAuth(strtolower($username), $password, "Minúscules");

        // Test amb primera majúscula
        $this->testAuth(ucfirst(strtolower($username)), $password, "Primera majúscula");

        return Command::SUCCESS;
    }

    private function testAuth(string $username, string $password, string $description): void
    {
        $this->info("🔍 Test {$description}: {$username}");

        try {
            $result = Auth::attempt([
                'username' => $username,
                'password' => $password
            ]);

            if ($result) {
                $user = Auth::user();
                $this->info("✅ LOGIN SUCCESSFUL!");
                $this->table([
                    ['Propietat', 'Valor']
                ], [
                    ['User ID', $user->id],
                    ['Name', $user->name],
                    ['Username BD', $user->username],
                    ['Email', $user->email],
                    ['NIF', $user->nif ?? 'No definit'],
                ]);
                Auth::logout();
            } else {
                $this->error("❌ Login failed");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }

        $this->newLine();
    }
}
