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
        Schema::create('departaments', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('descripcio')->nullable();
            $table->foreignId('gestor_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->boolean('actiu')->default(true);
            $table->json('configuracio')->nullable()
                  ->comment('Configuració específica del departament');
            $table->timestamps();
            
            // Índexs
            $table->index('actiu');
            $table->index('nom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departaments');
    }
};