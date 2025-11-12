<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acces_template_elements_extra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_sistema_id')->constrained('acces_template_sistemes')->cascadeOnDelete();
            $table->foreignId('element_extra_id')->constrained('sistema_elements_extra')->cascadeOnDelete();
            $table->string('opcio_seleccionada')->nullable();
            $table->text('valor_text_lliure')->nullable();
            $table->unsignedInteger('ordre')->default(1);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acces_template_elements_extra');
    }
};
