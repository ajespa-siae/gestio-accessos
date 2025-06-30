<?php

// database/migrations/2024_01_21_000004_add_grup_validacions_to_validacions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('validacions', function (Blueprint $table) {
            // Tipus de validació: individual (un usuari) o grup (múltiples gestors)
            $table->enum('tipus_validacio', ['individual', 'grup'])
                  ->default('individual')
                  ->after('estat');
            
            // Referència a la configuració de validador que ha creat aquesta validació
            $table->foreignId('config_validador_id')
                  ->nullable()
                  ->after('tipus_validacio')
                  ->constrained('sistema_validadors')
                  ->onDelete('set null');
            
            // JSON amb IDs dels validadors del grup (només per tipus_validacio = 'grup')
            $table->json('grup_validadors_ids')
                  ->nullable()
                  ->after('config_validador_id');
            
            // Índexs per optimitzar consultes
            $table->index(['tipus_validacio', 'estat']);
            $table->index(['config_validador_id']);
        });
    }

    public function down(): void
    {
        Schema::table('validacions', function (Blueprint $table) {
            $table->dropIndex(['tipus_validacio', 'estat']);
            $table->dropIndex(['config_validador_id']);
            $table->dropForeign(['config_validador_id']);
            $table->dropColumn(['tipus_validacio', 'config_validador_id', 'grup_validadors_ids']);
        });
    }
};