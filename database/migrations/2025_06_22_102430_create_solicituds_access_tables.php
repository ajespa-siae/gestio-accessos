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
        // Sol·licituds d'accés
        Schema::create('solicituds_acces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleat_destinatari_id')
                  ->constrained('empleats')
                  ->restrictOnDelete();
            $table->foreignId('usuari_solicitant_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->enum('estat', ['pendent', 'validant', 'aprovada', 'rebutjada', 'finalitzada'])
                  ->default('pendent');
            $table->text('justificacio');
            $table->timestamp('data_finalitzacio')->nullable();
            $table->string('identificador_unic')->unique()
                  ->comment('Format: SOL-YYYYMMDDHHMMSS-XXXXXXXX');
            $table->timestamps();
            
            $table->index(['estat', 'created_at']);
            $table->index('empleat_destinatari_id');
            $table->index('usuari_solicitant_id');
        });

        // Sistemes sol·licitats en cada sol·licitud
        Schema::create('solicitud_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')
                  ->constrained('solicituds_acces')
                  ->cascadeOnDelete();
            $table->foreignId('sistema_id')
                  ->constrained('sistemes')
                  ->restrictOnDelete();
            $table->foreignId('nivell_acces_id')
                  ->constrained('nivells_acces_sistema')
                  ->restrictOnDelete();
            $table->boolean('aprovat')->default(false);
            $table->timestamp('data_aprovacio')->nullable();
            $table->timestamps();
            
            $table->unique(['solicitud_id', 'sistema_id']);
            $table->index('aprovat');
        });

        // Validacions necessàries per cada sol·licitud
        Schema::create('validacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')
                  ->constrained('solicituds_acces')
                  ->cascadeOnDelete();
            $table->foreignId('sistema_id')
                  ->constrained('sistemes')
                  ->restrictOnDelete()
                  ->nullable();
            $table->foreignId('validador_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->enum('estat', ['pendent', 'aprovada', 'rebutjada'])
                  ->default('pendent');
            $table->timestamp('data_validacio')->nullable();
            $table->text('observacions')->nullable();
            $table->timestamps();
            
            $table->index(['validador_id', 'estat']);
            $table->index(['solicitud_id', 'estat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validacions');
        Schema::dropIfExists('solicitud_sistemes');
        Schema::dropIfExists('solicituds_acces');
    }
};