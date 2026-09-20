<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('recruit_jobs', 'designation_id')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->unsignedBigInteger('designation_id')
                    ->nullable()
                    ->index();
            });
        }

        if (!Schema::hasColumn('recruit_jobs', 'rank_level')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->unsignedTinyInteger('rank_level')
                    ->nullable()
                    ->index();
            });
        }

        if (!Schema::hasColumn('recruit_jobs', 'hr_location_id')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->unsignedTinyInteger('hr_location_id')
                    ->nullable()
                    ->after('rank_level');
            });
        }

         if (!Schema::hasColumn('recruit_job_applications', 'rank_level')) {
            Schema::table('recruit_job_applications', function (Blueprint $table) {
                $table->unsignedTinyInteger('rank_level')
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recruit_jobs', 'designation_id')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->dropColumn('designation_id');
            });
        }

        if (Schema::hasColumn('recruit_jobs', 'rank_level')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->dropColumn('rank_level');
            });
        }

        if (Schema::hasColumn('recruit_jobs', 'hr_location_id')) {
            Schema::table('recruit_jobs', function (Blueprint $table) {
                $table->dropColumn('hr_location_id');
            });
        }

        if (Schema::hasColumn('recruit_job_applications', 'rank_level')) {
            Schema::table('recruit_job_applications', function (Blueprint $table) {
                $table->dropColumn('rank_level');
            });
        }
    }
};
