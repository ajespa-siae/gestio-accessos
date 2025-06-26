<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_template_tasques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->onDelete('cascade');
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->integer('ordre');
            $table->boolean('obligatoria')->default(true);
            $table->enum('rol_assignat', ['it', 'rrhh', 'gestor'])->default('it');
            $table->integer('dies_limit')->nullable(); // Límite de días para completar
            $table->boolean('activa')->default(true);
            $table->timestamps();
            
            $table->index(['template_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_template_tasques');
    }
};