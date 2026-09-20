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
        Schema::table('recruit_applicant_blacklists', function (Blueprint $table) {
            $table->unsignedInteger('source_employee_id')
                ->nullable()
                ->after('source_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruit_applicant_blacklists', function (Blueprint $table) {
            if (Schema::hasColumn('recruit_applicant_blacklists', 'source_employee_id')) {
                $table->dropColumn('source_employee_id');
            }
        });
    }
};
