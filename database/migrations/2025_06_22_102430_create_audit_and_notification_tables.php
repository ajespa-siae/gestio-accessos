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
        // Logs d'auditoria
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('accio', 50);
            $table->string('taula_afectada', 50);
            $table->unsignedBigInteger('registre_id')->nullable();
            $table->string('identificador_proces')->nullable()
                  ->comment('Identificador únic del procés (EMP-*, SOL-*, etc.)');
            $table->json('dades_abans')->nullable();
            $table->json('dades_despres')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            
            $table->index(['user_id', 'timestamp']);
            $table->index('identificador_proces');
            $table->index('taula_afectada');
            $table->index('timestamp');
        });

        // Notificacions
        Schema::create('notificacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('titol');
            $table->text('missatge');
            $table->enum('tipus', ['info', 'warning', 'error', 'success'])
                  ->default('info');
            $table->boolean('llegida')->default(false);
            $table->timestamp('data_llegida')->nullable();
            $table->string('url_accio')->nullable()
                  ->comment('URL per redirigir quan es fa clic');
            $table->string('identificador_relacionat')->nullable()
                  ->comment('Identificador del procés relacionat');
            $table->timestamps();
            
            $table->index(['user_id', 'llegida']);
            $table->index(['user_id', 'created_at']);
            $table->index('tipus');
        });

        // Configuració del sistema
        Schema::create('configuracio', function (Blueprint $table) {
            $table->id();
            $table->string('clau')->unique();
            $table->json('valor');
            $table->text('descripcio')->nullable();
            $table->timestamp('data_actualitzacio')->useCurrent();
            $table->timestamps();
            
            $table->index('clau');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracio');
        Schema::dropIfExists('notificacions');
        Schema::dropIfExists('logs_auditoria');
    }
};