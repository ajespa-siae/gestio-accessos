<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Camps LDAP
            $table->string('username')->unique()->nullable()->after('email')
                  ->comment('LDAP samaccountname');
            $table->string('nif', 20)->nullable()->index()->after('username')
                  ->comment('Employee ID de LDAP');
            
            // Camps sistema RRHH
            $table->enum('rol_principal', ['admin', 'rrhh', 'it', 'gestor', 'empleat'])
                  ->default('empleat')->after('nif')
                  ->comment('Rol principal en el sistema');
            $table->boolean('actiu')->default(true)->after('rol_principal')
                  ->comment('Usuari actiu o inactiu');
            
            // Índexs per millorar el rendiment
            $table->index(['rol_principal', 'actiu']);
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar índexs
            $table->dropIndex(['rol_principal', 'actiu']);
            $table->dropIndex(['username']);
            
            // Eliminar columnes
            $table->dropColumn(['username', 'nif', 'rol_principal', 'actiu']);
        });
    }
};