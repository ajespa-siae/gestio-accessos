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
        Schema::table('sistemes', function (Blueprint $table) {
            $table->string('rol_gestor_defecte')->default('it')->after('actiu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sistemes', function (Blueprint $table) {
            $table->dropColumn('rol_gestor_defecte');
        });
    }
};
