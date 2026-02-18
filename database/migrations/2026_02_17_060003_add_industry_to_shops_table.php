<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->json('custom_skills')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['industry_id']);
            $table->dropColumn(['industry_id', 'custom_skills']);
        });
    }
};
