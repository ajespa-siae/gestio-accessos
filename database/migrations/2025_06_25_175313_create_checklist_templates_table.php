<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('departament_id')->nullable()->constrained('departaments');
            $table->enum('tipus', ['onboarding', 'offboarding']);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->index(['tipus', 'departament_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_templates');
    }
};