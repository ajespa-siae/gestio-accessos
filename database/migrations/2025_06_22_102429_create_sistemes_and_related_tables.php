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
        // Taula de sistemes
        Schema::create('sistemes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('descripcio')->nullable();
            $table->boolean('actiu')->default(true);
            $table->json('configuracio_validadors')->nullable()
                  ->comment('Configuració de qui ha de validar les sol·licituds');
            $table->timestamps();
            
            $table->index('actiu');
        });

        // Taula de nivells d'accés per sistema
        Schema::create('nivells_acces_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')
                  ->constrained('sistemes')
                  ->cascadeOnDelete();
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->integer('ordre')->default(1);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->unique(['sistema_id', 'nom']);
            $table->index(['sistema_id', 'actiu']);
            $table->index('ordre');
        });

        // Taula pivot departament-sistemes
        Schema::create('departament_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departament_id')
                  ->constrained('departaments')
                  ->cascadeOnDelete();
            $table->foreignId('sistema_id')
                  ->constrained('sistemes')
                  ->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['departament_id', 'sistema_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departament_sistemes');
        Schema::dropIfExists('nivells_acces_sistema');
        Schema::dropIfExists('sistemes');
    }
};