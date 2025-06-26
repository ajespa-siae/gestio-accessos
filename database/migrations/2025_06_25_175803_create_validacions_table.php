<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicituds_acces')->onDelete('cascade');
            $table->foreignId('sistema_id')->constrained('sistemes');
            $table->foreignId('validador_id')->constrained('users');
            $table->enum('estat', ['pendent', 'aprovada', 'rebutjada'])->default('pendent');
            $table->datetime('data_validacio')->nullable();
            $table->text('observacions')->nullable();
            $table->timestamps();
            
            $table->index(['validador_id', 'estat']);
            $table->unique(['solicitud_id', 'sistema_id', 'validador_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validacions');
    }
};