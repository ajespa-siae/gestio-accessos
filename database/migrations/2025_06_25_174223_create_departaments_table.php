<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departaments', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('descripcio')->nullable();
            $table->foreignId('gestor_id')->nullable()->constrained('users');
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->index(['actiu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departaments');
    }
};