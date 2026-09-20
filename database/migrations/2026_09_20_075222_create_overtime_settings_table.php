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
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->unique();
            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->enum('manager_permission',[
                        'cannot-approve',
                        'approved',
                        'pre-approve',
                    ]
                )
                ->default('cannot-approve');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_settings');
    }
};
