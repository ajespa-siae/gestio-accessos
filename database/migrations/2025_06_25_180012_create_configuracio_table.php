<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracio', function (Blueprint $table) {
            $table->id();
            $table->string('clau')->unique();
            $table->text('valor'); // Almacenamos como TEXT, no JSON
            $table->text('descripcio')->nullable();
            $table->timestamp('data_actualitzacio')->default(now());
            
            $table->index('clau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracio');
    }
};