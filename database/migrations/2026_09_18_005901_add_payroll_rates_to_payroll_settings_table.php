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
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->decimal(
                'late_detection_rate',12,2)
                ->default(0)
                ->after('currency_id');

            $table->decimal(
                'gazatted_allowance_rate',12,2)
                ->default(0)
                ->after('late_detection_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn(['late_detection_rate','gazatted_allowance_rate']);
        });
    }
};
