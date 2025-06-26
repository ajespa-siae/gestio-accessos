<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departament_configuracions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departament_id')->constrained()->onDelete('cascade');
            $table->string('clau'); // Ej: 'onboarding_automatico', 'email_notificacions'
            $table->text('valor'); // Valor como string, convertimos según necesidad
            $table->text('descripcio')->nullable();
            $table->timestamps();
            
            $table->unique(['departament_id', 'clau']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departament_configuracions');
    }
};