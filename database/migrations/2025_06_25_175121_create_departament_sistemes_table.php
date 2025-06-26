<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departament_sistemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departament_id')->constrained('departaments')->onDelete('cascade');
            $table->foreignId('sistema_id')->constrained('sistemes')->onDelete('cascade');
            $table->boolean('acces_per_defecte')->default(false);
            $table->timestamps();
            
            $table->unique(['departament_id', 'sistema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departament_sistemes');
    }
};