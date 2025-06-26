<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicituds_acces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleat_destinatari_id')->constrained('empleats');
            $table->foreignId('usuari_solicitant_id')->constrained('users');
            $table->enum('estat', ['pendent', 'validant', 'aprovada', 'rebutjada', 'finalitzada'])->default('pendent');
            $table->text('justificacio');
            $table->datetime('data_finalitzacio')->nullable();
            $table->string('identificador_unic')->unique();
            $table->timestamps();
            
            $table->index(['estat', 'usuari_solicitant_id']);
            $table->index('identificador_unic');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicituds_acces');
    }
};