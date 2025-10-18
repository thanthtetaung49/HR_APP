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
        Schema::create('man_power_report_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('man_power_report_id')->references('id')->on('man_power_reports')->cascadeOnDelete();
            $table->integer('man_power_setup')->nullable();
            $table->integer('man_power_basic_salary')->nullable();
            $table->unsignedInteger('team_id')->index();
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreignId('position_id')->references('id')->on('designations');
            $table->string('budget_year')->nullable();
            $table->string('quarter')->nullable();
            $table->enum('status', ['pending', 'approved', 'review'])->default('pending');

            $table->text('remarks')->nullable();
            $table->integer('created_by')->nullable();
            $table->date('approved_date')->nullable();

            $table->date('updated_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('man_power_report_histories');
    }
};
