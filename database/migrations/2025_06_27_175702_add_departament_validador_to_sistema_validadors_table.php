<?php

// database/migrations/2024_01_21_000002_add_departament_validador_to_sistema_validadors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistema_validadors', function (Blueprint $table) {
            // Afegir departament validador específic
            $table->foreignId('departament_validador_id')
                  ->nullable()
                  ->after('validador_id')
                  ->constrained('departaments')
                  ->comment('Departament específic que valida (quan tipus_validador = gestor_departament)');
            
            // Actualitzar comentaris per claredat
            $table->unsignedBigInteger('validador_id')
                  ->nullable()
                  ->comment('Usuari específic que valida (quan tipus_validador = usuari_especific)')
                  ->change();
            
            // Afegir índex per optimitzar consultes
            $table->index(['sistema_id', 'tipus_validador', 'departament_validador_id'], 'idx_sistema_validadors_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('sistema_validadors', function (Blueprint $table) {
            $table->dropIndex('idx_sistema_validadors_lookup');
            $table->dropForeign(['departament_validador_id']);
            $table->dropColumn('departament_validador_id');
        });
    }
};