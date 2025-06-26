<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistemes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('descripcio')->nullable();
            $table->boolean('actiu')->default(true);
            $table->timestamps();
            
            $table->index(['actiu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistemes');
    }
};