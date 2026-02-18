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
            // Availability schedule (JSON: days of week, time ranges)
            $table->json('availability_schedule')->nullable()->after('is_active');

            // Blackout dates (JSON: array of dates provider is unavailable)
            $table->json('blackout_dates')->nullable()->after('availability_schedule');

            // Minimum notice hours (how far in advance bookings must be made)
            $table->integer('min_notice_hours')->default(24)->after('blackout_dates');

            // Tax info collection status
            $table->boolean('tax_info_submitted')->default(false)->after('min_notice_hours');
            $table->timestamp('tax_info_submitted_at')->nullable()->after('tax_info_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'availability_schedule',
                'blackout_dates',
                'min_notice_hours',
                'tax_info_submitted',
                'tax_info_submitted_at',
            ]);
        });
    }
};
