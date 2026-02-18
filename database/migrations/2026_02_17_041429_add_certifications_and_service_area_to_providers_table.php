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
        Schema::table('providers', function (Blueprint $table) {
            $table->json('certifications')->nullable()->after('blackout_dates');
            $table->unsignedSmallInteger('service_area_max_miles')->default(25)->after('certifications');
            $table->json('preferred_zip_codes')->nullable()->after('service_area_max_miles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['certifications', 'service_area_max_miles', 'preferred_zip_codes']);
        });
    }
};
