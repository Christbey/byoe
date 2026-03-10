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
            $table->string('vetting_status')->default('pending_review')->after('is_active');
            $table->string('background_check_status')->default('pending')->after('vetting_status');
            $table->timestamp('identity_verified_at')->nullable()->after('background_check_status');
            $table->timestamp('vetting_completed_at')->nullable()->after('identity_verified_at');
            $table->timestamp('last_reviewed_at')->nullable()->after('vetting_completed_at');
            $table->foreignId('last_reviewed_by_user_id')->nullable()->after('last_reviewed_at')->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('trust_score')->default(0)->after('last_reviewed_by_user_id');
            $table->unsignedSmallInteger('reliability_score')->default(100)->after('trust_score');
            $table->unsignedInteger('cancellation_count')->default(0)->after('reliability_score');
            $table->decimal('cancellation_rate', 5, 2)->default(0)->after('cancellation_count');
            $table->unsignedInteger('no_show_count')->default(0)->after('cancellation_rate');
            $table->unsignedInteger('dispute_count')->default(0)->after('no_show_count');
            $table->unsignedInteger('completed_without_issue_count')->default(0)->after('dispute_count');
            $table->text('trust_notes')->nullable()->after('completed_without_issue_count');

            $table->index('vetting_status');
            $table->index('background_check_status');
            $table->index('trust_score');
            $table->index('reliability_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['last_reviewed_by_user_id']);
            $table->dropIndex(['vetting_status']);
            $table->dropIndex(['background_check_status']);
            $table->dropIndex(['trust_score']);
            $table->dropIndex(['reliability_score']);

            $table->dropColumn([
                'vetting_status',
                'background_check_status',
                'identity_verified_at',
                'vetting_completed_at',
                'last_reviewed_at',
                'last_reviewed_by_user_id',
                'trust_score',
                'reliability_score',
                'cancellation_count',
                'cancellation_rate',
                'no_show_count',
                'dispute_count',
                'completed_without_issue_count',
                'trust_notes',
            ]);
        });
    }
};
