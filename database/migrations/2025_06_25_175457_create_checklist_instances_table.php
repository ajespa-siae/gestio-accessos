<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleat_id')->constrained('empleats');
            $table->foreignId('template_id')->constrained('checklist_templates');
            $table->enum('estat', ['pendent', 'en_progres', 'completada'])->default('pendent');
            $table->datetime('data_finalitzacio')->nullable();
            $table->timestamps();
            
            $table->index(['empleat_id', 'estat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_instances');
    }
};