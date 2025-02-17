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
        // Schema::dropIfExists('allowances');

        Schema::create('allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('users_id')
                    ->index('allowances_users_id_foreign');
            $table->foreign('users_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            $table->integer('basic_salary');
            $table->integer('technical_allowance');
            $table->integer('living_cost_allowance');
            $table->integer('special_allowance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowances');
    }
};
