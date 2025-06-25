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
        // Templates de checklist
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('departament_id')->nullable()
                  ->constrained('departaments')
                  ->cascadeOnDelete()
                  ->comment('NULL = template global');
            $table->enum('tipus', ['onboarding', 'offboarding']);
            $table->boolean('actiu')->default(true);
            $table->json('tasques_template')
                  ->comment('Array de tasques amb nom, descripció, rol assignat, etc.');
            $table->timestamps();
            
            $table->index(['tipus', 'actiu']);
            $table->index(['departament_id', 'tipus']);
        });

        // Instàncies de checklist
        Schema::create('checklist_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleat_id')
                  ->constrained('empleats')
                  ->restrictOnDelete();
            $table->foreignId('template_id')
                  ->constrained('checklist_templates')
                  ->restrictOnDelete();
            $table->enum('estat', ['pendent', 'en_progres', 'completada'])
                  ->default('pendent');
            $table->timestamp('data_finalitzacio')->nullable();
            $table->timestamps();
            
            $table->index(['empleat_id', 'estat']);
            $table->index('estat');
        });

        // Tasques de checklist
        Schema::create('checklist_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_instance_id')
                  ->constrained('checklist_instances')
                  ->cascadeOnDelete();
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->integer('ordre')->default(1);
            $table->boolean('obligatoria')->default(true);
            $table->boolean('completada')->default(false);
            $table->timestamp('data_assignacio')->nullable();
            $table->timestamp('data_completada')->nullable();
            $table->foreignId('usuari_assignat_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->foreignId('usuari_completat_id')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('observacions')->nullable();
            $table->timestamps();
            
            $table->index(['checklist_instance_id', 'completada']);
            $table->index(['usuari_assignat_id', 'completada']);
            $table->index('ordre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_tasks');
        Schema::dropIfExists('checklist_instances');
        Schema::dropIfExists('checklist_templates');
    }
};