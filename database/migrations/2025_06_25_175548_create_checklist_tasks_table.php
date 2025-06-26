<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_instance_id')->constrained('checklist_instances')->onDelete('cascade');
            $table->string('nom');
            $table->text('descripcio')->nullable();
            $table->integer('ordre');
            $table->boolean('obligatoria')->default(true);
            $table->boolean('completada')->default(false);
            $table->datetime('data_assignacio')->default(now());
            $table->datetime('data_completada')->nullable();
            $table->datetime('data_limit')->nullable();
            $table->foreignId('usuari_assignat_id')->nullable()->constrained('users');
            $table->foreignId('usuari_completat_id')->nullable()->constrained('users');
            $table->text('observacions')->nullable();
            $table->timestamps();
            
            $table->index(['usuari_assignat_id', 'completada']);
            $table->index(['checklist_instance_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_tasks');
    }
};