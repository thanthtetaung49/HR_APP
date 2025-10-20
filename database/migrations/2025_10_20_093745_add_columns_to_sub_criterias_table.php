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
        Schema::table('sub_criterias', function (Blueprint $table) {
            $table->string('responsible_person')->nullable();
            $table->string('accountability')->nullable();
            $table->string('action_taken')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_criterias', function (Blueprint $table) {
            $table->dropColumn('responsible_person');
            $table->dropColumn('accountability');
            $table->dropColumn('action_taken');
        });
    }
};
