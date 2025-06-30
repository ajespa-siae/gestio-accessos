<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Camps LDAP bàsics
            $table->timestamp('ldap_last_sync')->nullable()->after('updated_at');
            $table->json('ldap_sync_errors')->nullable()->after('ldap_last_sync');
            $table->boolean('ldap_managed')->default(false)->after('ldap_sync_errors');
            $table->string('ldap_dn')->nullable()->after('ldap_managed');
            
            // Índexs per rendiment
            $table->index(['username'], 'users_username_idx');
            $table->index(['nif'], 'users_nif_idx');
            $table->index(['rol_principal', 'actiu'], 'users_rol_actiu_idx');
            $table->index(['ldap_managed', 'actiu'], 'users_ldap_managed_actiu_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_idx');
            $table->dropIndex('users_nif_idx');
            $table->dropIndex('users_rol_actiu_idx');
            $table->dropIndex('users_ldap_managed_actiu_idx');
            
            $table->dropColumn([
                'ldap_last_sync',
                'ldap_sync_errors',
                'ldap_managed', 
                'ldap_dn'
            ]);
        });
    }
};
