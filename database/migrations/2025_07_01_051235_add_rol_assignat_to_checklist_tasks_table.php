<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ChecklistTask;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->string('rol_assignat')->nullable()->after('usuari_assignat_id');
        });

        // Migrar dades: obtenir el rol de cada usuari assignat
        $tasks = DB::table('checklist_tasks')->whereNotNull('usuari_assignat_id')->get();
        foreach ($tasks as $task) {
            $user = User::find($task->usuari_assignat_id);
            if ($user) {
                DB::table('checklist_tasks')
                    ->where('id', $task->id)
                    ->update(['rol_assignat' => $user->rol_principal]);
            }
        }

        // Per ara mantenim la columna usuari_assignat_id per compatibilitat
        // En una futura migració l'eliminarem quan tot el codi estigui adaptat
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->dropColumn('rol_assignat');
        });
    }
};
