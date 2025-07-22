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
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('solicitud_acces_id')->nullable()->after('checklist_instance_id');
            $table->foreign('solicitud_acces_id')->references('id')->on('solicituds_acces')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklist_tasks', function (Blueprint $table) {
            $table->dropForeign(['solicitud_acces_id']);
            $table->dropColumn('solicitud_acces_id');
        });
    }
};
