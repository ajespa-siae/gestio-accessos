<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicituds_acces')->onDelete('cascade');
            $table->foreignId('sistema_id')->constrained('sistemes');
            $table->foreignId('nivell_acces_id')->constrained('nivells_acces_sistema');
            $table->boolean('aprovat')->default(false);
            $table->datetime('data_aprovacio')->nullable();
            $table->timestamps();
            
            $table->unique(['solicitud_id', 'sistema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_sistemes');
    }
};