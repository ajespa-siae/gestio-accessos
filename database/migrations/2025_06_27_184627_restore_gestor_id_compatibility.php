<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Restaurar columna gestor_id per compatibilitat
        Schema::table('departaments', function (Blueprint $table) {
            $table->foreignId('gestor_id')->nullable()->constrained('users')->after('descripcio');
        });
        
        // 2. Sincronitzar gestor_id amb gestors principals de departament_gestors
        $gestorsPrincipals = DB::table('departament_gestors')
            ->where('gestor_principal', true)
            ->get();
            
        foreach ($gestorsPrincipals as $gestor) {
            DB::table('departaments')
                ->where('id', $gestor->departament_id)
                ->update(['gestor_id' => $gestor->user_id]);
        }
        
        // 3. Assegurar que tots els gestors principals també estan a departament_gestors
        $departamentsAmbGestor = DB::table('departaments')
            ->whereNotNull('gestor_id')
            ->get();
            
        foreach ($departamentsAmbGestor as $dept) {
            // Verificar si ja existeix
            $existeix = DB::table('departament_gestors')
                ->where('departament_id', $dept->id)
                ->where('user_id', $dept->gestor_id)
                ->exists();
                
            if (!$existeix) {
                DB::table('departament_gestors')->insert([
                    'departament_id' => $dept->id,
                    'user_id' => $dept->gestor_id,
                    'gestor_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departaments', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
            $table->dropColumn('gestor_id');
        });
    }
};