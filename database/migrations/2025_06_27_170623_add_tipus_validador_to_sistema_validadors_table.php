<?php

// database/migrations/2024_01_21_000001_add_tipus_validador_to_sistema_validadors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_validadors', function (Blueprint $table) {
            // Afegir tipus de validador
            $table->enum('tipus_validador', ['usuari_especific', 'gestor_departament'])
                  ->default('usuari_especific')
                  ->after('validador_id');
            
            // Fer validador_id nullable per gestors departament
            $table->unsignedBigInteger('validador_id')->nullable()->change();
            
            // Afegir índex per optimitzar consultes
            $table->index(['sistema_id', 'tipus_validador']);
        });
    }

    public function down(): void
    {
        Schema::table('sistema_validadors', function (Blueprint $table) {
            $table->dropIndex(['sistema_id', 'tipus_validador']);
            $table->dropColumn('tipus_validador');
            
            // Restaurar validador_id com NOT NULL
            $table->unsignedBigInteger('validador_id')->nullable(false)->change();
        });
    }
};