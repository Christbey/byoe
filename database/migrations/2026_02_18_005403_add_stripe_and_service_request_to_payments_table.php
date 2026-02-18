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
        Schema::table('payments', function (Blueprint $table) {
            // Expand status to include pre-capture authorization state
            $table->enum('status', ['authorized', 'pending', 'processing', 'succeeded', 'failed', 'refunded'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'refunded'])
                ->default('pending')
                ->change();
        });
    }
};
