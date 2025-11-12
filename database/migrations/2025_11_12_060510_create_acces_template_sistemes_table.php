<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acces_template_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('acces_templates')->cascadeOnDelete();
            $table->foreignId('sistema_id')->constrained('sistemes')->cascadeOnDelete();
            $table->foreignId('nivell_acces_id')->constrained('nivell_acces_sistemes')->cascadeOnDelete();
            $table->unsignedInteger('ordre')->default(1);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acces_template_sistemes');
    }
};
