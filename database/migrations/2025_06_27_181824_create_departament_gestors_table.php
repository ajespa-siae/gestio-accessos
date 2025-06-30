<?php

// database/migrations/2024_01_21_000003_create_departament_gestors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear taula pivot per múltiples gestors
        Schema::create('departament_gestors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('gestor_principal')->default(false);
            $table->timestamps();
            
            $table->unique(['departament_id', 'user_id']);
            $table->index(['departament_id', 'gestor_principal']);
        });
        
        // 2. Migrar gestors existents de departaments.gestor_id a la nova taula
        $departamentsAmbGestor = DB::table('departaments')
            ->whereNotNull('gestor_id')
            ->get();
            
        foreach ($departamentsAmbGestor as $dept) {
            DB::table('departament_gestors')->insert([
                'departament_id' => $dept->id,
                'user_id' => $dept->gestor_id,
                'gestor_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // 3. Eliminar columna antiga gestor_id (OPCIONAL - mantenir per compatibilitat)
        Schema::table('departaments', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
            $table->dropColumn('gestor_id');
        });
    }

    public function down(): void
    {
        // Restaurar gestors a la columna original si no l'hem eliminada
        $gestorsPrincipals = DB::table('departament_gestors')
            ->where('gestor_principal', true)
            ->get();
            
        foreach ($gestorsPrincipals as $gestor) {
            DB::table('departaments')
                ->where('id', $gestor->departament_id)
                ->update(['gestor_id' => $gestor->user_id]);
        }
        
        Schema::dropIfExists('departament_gestors');
    }
};