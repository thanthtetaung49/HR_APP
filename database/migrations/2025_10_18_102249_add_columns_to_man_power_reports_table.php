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
        Schema::table('man_power_reports', function (Blueprint $table) {
            $table->text('remark_from')->nullable()->after('status');
            $table->text('remark_to')->nullable()->after('remark_from');
            $table->integer('created_by')->nullable()->after('remark_to');
            $table->date('approved_date')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('man_power_reports', function (Blueprint $table) {
            $table->dropColumn('remark_from');
            $table->dropColumn('remark_to');
            $table->dropColumn('created_by');
            $table->dropColumn('approved_date');
        });
    }
};
