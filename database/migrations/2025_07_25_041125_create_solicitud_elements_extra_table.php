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
        Schema::create('solicitud_elements_extra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id');
            $table->unsignedBigInteger('solicitud_sistema_id');
            $table->unsignedBigInteger('element_extra_id');
            $table->string('opcio_seleccionada', 100)->nullable();
            $table->text('valor_text_lliure')->nullable();
            $table->boolean('aprovat')->default(false);
            $table->timestamp('data_aprovacio')->nullable();
            $table->text('observacions')->nullable();
            $table->timestamps();
            
            // Constraints
            $table->unique(['solicitud_sistema_id', 'element_extra_id']);
            $table->foreign('solicitud_id')->references('id')->on('solicituds_acces')->onDelete('cascade');
            $table->foreign('solicitud_sistema_id')->references('id')->on('solicitud_sistemes')->onDelete('cascade');
            $table->foreign('element_extra_id')->references('id')->on('sistema_elements_extra')->onDelete('cascade');
            
            // Indexes
            $table->index(['solicitud_id', 'aprovat']);
            $table->index('solicitud_sistema_id');
            $table->index('element_extra_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_elements_extra');
    }
};
