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
            $table->text('remarks')->nullable()->after('status');
            $table->integer('created_by')->nullable()->after('remarks');
            $table->date('approved_date')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('man_power_reports', function (Blueprint $table) {
            $table->dropColumn('remarks');
            $table->dropColumn('created_by');
            $table->dropColumn('approved_date');
        });
    }
};
