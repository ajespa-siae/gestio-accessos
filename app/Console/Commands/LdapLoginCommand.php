<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\LdapAuthHelper;
use Illuminate\Support\Facades\Auth;

class LdapLoginCommand extends Command
{
    protected $signature = 'ldap:login {username} {password}';
    protected $description = 'Test login LDAP amb format UPN automàtic';

    public function handle(): int
    {
        $username = $this->argument('username');
        $password = $this->argument('password');

        $this->info("🔐 TEST LOGIN UPN AUTOMÀTIC");
        $this->info("Username: {$username}");
        $this->newLine();

        try {
            $result = LdapAuthHelper::loginUser($username, $password);

            if ($result) {
                $user = Auth::user();
                $this->info("🎉 LOGIN SUCCESSFUL!");
                $this->table([
                    ['Propietat', 'Valor']
                ], [
                    ['User ID', $user->id],
                    ['Name', $user->name],
                    ['Username', $user->username],
                    ['Email', $user->email],
                ]);
                Auth::logout();
                return Command::SUCCESS;
            } else {
                $this->error("❌ Login failed");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
