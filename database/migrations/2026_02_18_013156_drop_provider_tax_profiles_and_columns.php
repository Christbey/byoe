<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('provider_tax_profiles');

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['tax_info_submitted', 'tax_info_submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::create('provider_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->onDelete('cascade');
            $table->string('legal_name');
            $table->string('business_name')->nullable();
            $table->enum('tax_classification', ['individual', 'sole_proprietor', 'llc', 'c_corp', 's_corp', 'partnership', 'trust_estate']);
            $table->text('encrypted_ssn_ein');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->string('signature');
            $table->timestamp('signed_at');
            $table->string('ip_address', 45);
            $table->timestamps();
            $table->unique('provider_id');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->boolean('tax_info_submitted')->default(false);
            $table->timestamp('tax_info_submitted_at')->nullable();
        });
    }
};
