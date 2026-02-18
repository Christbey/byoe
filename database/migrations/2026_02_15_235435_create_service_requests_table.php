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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_location_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->json('skills_required')->nullable();
            $table->dateTime('service_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 10, 2);
            $table->enum('status', ['open', 'filled', 'expired', 'cancelled'])->default('open');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('shop_location_id');
            $table->index('status');
            $table->index('service_date');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
