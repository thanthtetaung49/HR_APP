<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruit_applicant_blacklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id')->index();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('nrc')->nullable()->index();
            $table->text('reason');
            $table->unsignedInteger('source_application_id')->nullable()->index();
            $table->unsignedInteger('added_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('recruit_job_applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('rank_level')->nullable()->after('date_of_birth');
            $table->string('marital_status', 20)->nullable()->after('rank_level');
            $table->text('education')->nullable()->after('marital_status');
            $table->text('certifications_qualifications')->nullable()->after('education');
            $table->json('work_experience_details')->nullable()->after('certifications_qualifications');
            $table->decimal('last_salary_minimum', 15, 2)->nullable()->after('work_experience_details');
            $table->decimal('expected_salary_minimum', 15, 2)->nullable()->after('last_salary_minimum');
            $table->string('nrc')->nullable()->index()->after('expected_salary_minimum');
            $table->string('selection_phase', 40)->default('cv_screening')->index()->after('nrc');
            $table->string('overall_status', 30)->default('not_started')->index()->after('selection_phase');
            $table->string('rejection_reason', 80)->nullable()->after('overall_status');
            $table->text('rejection_reason_details')->nullable()->after('rejection_reason');
            $table->string('keep_cv_reason')->nullable()->after('rejection_reason_details');
            $table->string('job_offer_decision', 30)->nullable()->after('keep_cv_reason');
            $table->text('job_offer_decision_reason')->nullable()->after('job_offer_decision');
            $table->boolean('is_blacklisted')->default(false)->index()->after('job_offer_decision_reason');
            $table->text('blacklist_reason')->nullable()->after('is_blacklisted');
            $table->unsignedInteger('employee_user_id')->nullable()->index()->after('blacklist_reason');
        });

        Schema::table('recruit_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('man_power_report_id')->nullable()->index();
            $table->unsignedInteger('vacancy_count')->default(1);
            $table->unsignedBigInteger('hr_location_id')->nullable()->index();
            $table->unsignedBigInteger('designation_id')->nullable()->index();
            $table->unsignedTinyInteger('rank_level')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('recruit_jobs', function (Blueprint $table) {
            $table->dropColumn(['man_power_report_id', 'vacancy_count', 'hr_location_id', 'designation_id', 'rank_level']);
        });

        Schema::table('recruit_job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'rank_level', 'marital_status', 'education', 'certifications_qualifications',
                'work_experience_details', 'last_salary_minimum', 'expected_salary_minimum', 'nrc',
                'selection_phase', 'overall_status', 'rejection_reason', 'rejection_reason_details',
                'keep_cv_reason', 'job_offer_decision', 'job_offer_decision_reason', 'is_blacklisted',
                'blacklist_reason', 'employee_user_id',
            ]);
        });

        Schema::dropIfExists('recruit_applicant_blacklists');
    }
};
