<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('accio'); // 'created', 'updated', 'deleted'
            $table->string('taula_afectada');
            $table->bigInteger('registre_id');
            $table->string('identificador_proces')->nullable(); // Para agrupar acciones
            $table->text('dades_abans')->nullable(); // JSON serializado como TEXT
            $table->text('dades_despres')->nullable(); // JSON serializado como TEXT
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp')->default(now());
            
            $table->index(['user_id', 'timestamp']);
            $table->index(['identificador_proces']);
            $table->index(['taula_afectada', 'registre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};