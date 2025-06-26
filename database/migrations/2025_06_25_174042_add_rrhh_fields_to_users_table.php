<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campos LDAP
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('nif', 20)->nullable()->index()->after('username')
                  ->comment('Employee ID de LDAP');
            
            // Campos sistema RRHH
            $table->enum('rol_principal', ['admin', 'rrhh', 'it', 'gestor', 'empleat'])
                  ->default('empleat')->after('nif');
            $table->boolean('actiu')->default(true)->after('rol_principal');
            
            // Índices para rendimiento
            $table->index(['rol_principal', 'actiu']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol_principal', 'actiu']);
            $table->dropColumn(['username', 'nif', 'rol_principal', 'actiu']);
        });
    }
};