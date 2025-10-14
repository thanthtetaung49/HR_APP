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
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('designation_ids');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('teams') && Schema::hasColumn('teams', 'location_id')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['location_id']); // Drop foreign key
                $table->dropColumn('location_id'); // Drop the column
            });
        }
    }
};
