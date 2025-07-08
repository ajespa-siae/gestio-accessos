<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empleats', function (Blueprint $table) {
            // Hacer que la columna usuari_creador_id sea nullable
            $table->foreignId('usuari_creador_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleats', function (Blueprint $table) {
            // Revertir el cambio: hacer que la columna usuari_creador_id sea no nullable
            $table->foreignId('usuari_creador_id')->nullable(false)->change();
        });
    }
};
