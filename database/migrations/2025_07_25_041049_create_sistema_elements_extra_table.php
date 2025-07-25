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
        Schema::create('sistema_elements_extra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sistema_id');
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->string('tipus', 50); // 'modul', 'funcionalitat', 'recurs'
            $table->json('opcions_disponibles')->nullable(); // ["basic", "advanced", "admin"]
            $table->boolean('permet_text_lliure')->default(false);
            $table->integer('ordre')->default(1);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            // Constraints
            $table->unique(['sistema_id', 'nom']);
            $table->foreign('sistema_id')->references('id')->on('sistemes')->onDelete('cascade');
            
            // Indexes
            $table->index(['sistema_id', 'actiu']);
            $table->index('ordre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sistema_elements_extra');
    }
};
