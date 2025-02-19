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
        Schema::dropIfExists('detections');

        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')
                    ->index('detections_users_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            $table->integer('other_detection');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};
