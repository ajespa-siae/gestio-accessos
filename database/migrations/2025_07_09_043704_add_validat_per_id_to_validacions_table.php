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
        Schema::table('validacions', function (Blueprint $table) {
            $table->foreignId('validat_per_id')->nullable()->after('grup_validadors_ids')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validacions', function (Blueprint $table) {
            $table->dropForeign(['validat_per_id']);
            $table->dropColumn('validat_per_id');
        });
    }
};
