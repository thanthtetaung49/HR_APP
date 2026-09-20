<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename only the applicant management-rank column.
     *
     * recruit_jobs.rank_level remains unchanged because it contains the
     * actual designation rank used by the recruitment workflow.
     */
    public function up(): void
    {
        Schema::table('recruit_job_applications', function (Blueprint $table) {
            $table->renameColumn('rank_level', 'management_rank_level');
        });
    }

    /**
     * Reverse the column rename.
     */
    public function down(): void
    {
        Schema::table('recruit_job_applications', function (Blueprint $table) {
            $table->renameColumn('management_rank_level', 'rank_level');
        });
    }
};
