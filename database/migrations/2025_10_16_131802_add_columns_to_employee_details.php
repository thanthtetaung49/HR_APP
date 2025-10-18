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
        Schema::table('employee_details', function (Blueprint $table) {
            $table->foreignId('criteria_id')->nullable()->constrained('criterias');
            $table->foreignId('sub_criteria_id')->nullable()->constrained('sub_criterias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropForeign(['criteria_id']);
            $table->dropForeign(['sub_criteria_id']);
            $table->dropColumn('criteria_id');
            $table->dropColumn('sub_criteria_id');
        });
    }
};
