<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nivells_acces_sistema', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistema_id')->constrained('sistemes')->onDelete('cascade');
            $table->string('nom'); // Consulta, Gestió, Supervisor
            $table->text('descripcio')->nullable();
            $table->integer('ordre')->default(1);
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->index(['sistema_id', 'ordre']);
            $table->unique(['sistema_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nivells_acces_sistema');
    }
};