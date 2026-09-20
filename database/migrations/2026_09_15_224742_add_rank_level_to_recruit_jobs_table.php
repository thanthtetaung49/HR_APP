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
        Schema::table('recruit_jobs', function (Blueprint $table) {
            $table->unsignedTinyInteger('rank_level')
                ->nullable()
                ->after('management_rank_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruit_jobs', function (Blueprint $table) {
             $table->dropColumn('rank_level');
        });
    }
};
