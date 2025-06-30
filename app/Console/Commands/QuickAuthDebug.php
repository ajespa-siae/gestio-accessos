<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class QuickAuthDebug extends Command
{
    protected $signature = 'auth:quick-debug';
    protected $description = 'Debug ràpid configuració auth';

    public function handle()
    {
        $this->info('🔍 DEBUG AUTH CONFIGURACIÓ');
        
        $webGuard = config('auth.guards.web');
        $this->line("Web provider: " . $webGuard['provider']);
        
        $provider = Auth::getProvider();
        $this->line("Provider class: " . get_class($provider));
        
        try {
            $ldapModel = config('auth.providers.ldap.model');
            $this->line("LDAP model: " . $ldapModel);
        } catch (\Exception $e) {
            $this->error("No LDAP provider: " . $e->getMessage());
        }
    }
}
