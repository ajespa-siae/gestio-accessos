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
        Schema::table('solicituds_acces', function (Blueprint $table) {
            // Afegir camp per tipus de sol·licitud
            $table->string('tipus')->default('normal')->after('estat');
            
            // Afegir relació amb procés de mobilitat
            $table->foreignId('process_mobilitat_id')->nullable()->constrained('process_mobilitat')->after('tipus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicituds_acces', function (Blueprint $table) {
            $table->dropForeign(['process_mobilitat_id']);
            $table->dropColumn(['tipus', 'process_mobilitat_id']);
        });
    }
};
