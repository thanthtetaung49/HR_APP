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
        Schema::create('additional_basic_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salary_allowance_id');
            $table->foreign('salary_allowance_id')->references('id')->on('allowances')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            $table->unsignedInteger('user_id')
                ->index('allowances_users_id_foreign');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('type')->default('initial');
            $table->integer('amount')->default(0);
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_basic_salaries');
    }
};
