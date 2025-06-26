<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('titol');
            $table->text('missatge');
            $table->enum('tipus', ['info', 'warning', 'error', 'success'])->default('info');
            $table->boolean('llegida')->default(false);
            $table->datetime('data_llegida')->nullable();
            $table->string('url_accio')->nullable();
            $table->string('identificador_relacionat')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'llegida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacions');
    }
};