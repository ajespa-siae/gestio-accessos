<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistema_validadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->constrained('sistemes')->onDelete('cascade');
            $table->foreignId('validador_id')->constrained('users');
            $table->integer('ordre')->default(1); // Orden de validación
            $table->boolean('requerit')->default(true); // Si es obligatorio
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->unique(['sistema_id', 'validador_id']);
            $table->index(['sistema_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_validadors');
    }
};