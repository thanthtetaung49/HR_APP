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
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->integer('overtime_amount')->default(0)->after('basic_salary');
            $table->integer('off_day_holiday_salary')->default(0)->after('overtime_amount');
            $table->integer('gazatted_allowance')->default(0)->after('off_day_holiday_salary');
            $table->integer('evening_shift_allowance')->default(0)->after('gazatted_allowance');
            $table->integer('absent')->default(0)->after('evening_shift_allowance');
            $table->integer('leave_without_pay_detection')->default(0)->after('absent');
            $table->integer('after_late_detection')->default(0)->after('leave_without_pay_detection');
            $table->integer('break_time_late_detection')->default(0)->after('after_late_detection');
            $table->integer('total_leave_without_pay_salary')->default(0)->after('break_time_late_detection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropColumn([
                'overtime_amount',
                'off_day_holiday_salary',
                'gazatted_allowance',
                'evening_shift_allowance',
                'absent',
                'leave_without_pay_detection',
                'after_late_detection',
                'break_time_late_detection',
                'total_leave_without_pay_salary'
            ]);
        });
    }
};
