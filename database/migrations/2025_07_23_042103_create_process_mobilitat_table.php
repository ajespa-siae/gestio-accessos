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
        Schema::create('process_mobilitat', function (Blueprint $table) {
            $table->id();
            $table->string('identificador_unic')->unique(); // MOB-YYYYMMDD-XXXX
            $table->foreignId('empleat_id')->constrained('empleats');
            $table->foreignId('usuari_solicitant_id')->constrained('users');
            $table->foreignId('departament_actual_id')->constrained('departaments');
            $table->foreignId('departament_nou_id')->constrained('departaments');
            $table->enum('estat', [
                'pendent_dept_actual', 
                'pendent_dept_nou', 
                'validant', 
                'aprovada', 
                'finalitzada'
            ]);
            $table->text('justificacio');
            $table->json('dades_empleat_noves')->nullable(); // Canvis dades empleat
            $table->foreignId('solicitud_acces_id')->nullable()->constrained('solicituds_acces');
            $table->timestamp('data_finalitzacio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_mobilitat');
    }
};
