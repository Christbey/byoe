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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ratee_id')->constrained('users')->onDelete('cascade');
            $table->enum('rater_type', ['shop', 'provider']);
            $table->integer('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('rater_id');
            $table->index('ratee_id');
            $table->index('rating');
            $table->unique(['booking_id', 'rater_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
