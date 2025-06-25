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
        Schema::create('empleats', function (Blueprint $table) {
            $table->id();
            $table->string('nom_complet');
            $table->string('nif', 20)->index();
            $table->string('correu_personal');
            $table->foreignId('departament_id')
                  ->constrained('departaments')
                  ->restrictOnDelete();
            $table->string('carrec');
            $table->enum('estat', ['actiu', 'baixa', 'suspens'])->default('actiu');
            $table->date('data_alta');
            $table->date('data_baixa')->nullable();
            $table->foreignId('usuari_creador_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->text('observacions')->nullable();
            $table->string('identificador_unic')->unique()
                  ->comment('Format: EMP-YYYYMMDDHHMMSS-XXXXXXXX');
            $table->timestamps();
            
            // Índexs compostos per millorar les cerques
            $table->index(['estat', 'departament_id']);
            $table->index(['estat', 'data_alta']);
            $table->index('identificador_unic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleats');
    }
};