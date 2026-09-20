<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->enum('shift_manager_permission', [
                'cannot-approve',
                'approved',
                'pre-approve',
            ])->default('cannot-approve');
        });

        Schema::table('employee_shift_change_requests', function (Blueprint $table) {
            $table->string('manager_status_permission')
                ->nullable()
                ->after('status');
            $table->unsignedInteger('action_by')
                ->nullable()
                ->after('manager_status_permission');
            $table->foreign('action_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_shift_change_requests', function (Blueprint $table) {
            $table->dropForeign(['action_by']);
            $table->dropColumn([
                'manager_status_permission',
                'action_by',
            ]);
        });

        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn('shift_manager_permission');
        });
    }
};
