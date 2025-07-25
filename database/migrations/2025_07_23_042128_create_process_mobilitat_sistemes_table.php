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
        Schema::create('process_mobilitat_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_mobilitat_id')->constrained('process_mobilitat');
            $table->foreignId('sistema_id')->constrained('sistemes');
            $table->foreignId('nivell_acces_original_id')->nullable()->constrained('nivells_acces_sistema');
            $table->foreignId('nivell_acces_final_id')->nullable()->constrained('nivells_acces_sistema');
            $table->enum('accio_dept_actual', ['mantenir', 'eliminar'])->default('mantenir');
            $table->enum('accio_dept_nou', ['mantenir', 'modificar', 'eliminar', 'afegir']);
            $table->enum('estat_final', ['mantenir', 'eliminar', 'afegir', 'modificar']);
            $table->boolean('processat_dept_actual')->default(false);
            $table->boolean('processat_dept_nou')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_mobilitat_sistemes');
    }
};
