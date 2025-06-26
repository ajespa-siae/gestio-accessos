<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleats', function (Blueprint $table) {
            $table->id();
            $table->string('nom_complet');
            $table->string('nif', 20)->index();
            $table->string('correu_personal');
            $table->foreignId('departament_id')->constrained('departaments');
            $table->string('carrec');
            $table->enum('estat', ['actiu', 'baixa', 'suspens'])->default('actiu');
            $table->datetime('data_alta')->default(now());
            $table->datetime('data_baixa')->nullable();
            $table->foreignId('usuari_creador_id')->constrained('users');
            $table->text('observacions')->nullable();
            $table->string('identificador_unic')->unique();
            $table->timestamps();
            
            $table->index(['estat', 'departament_id']);
            $table->index('identificador_unic');
            $table->unique(['nif', 'estat']); // Evitar duplicados activos
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleats');
    }
};