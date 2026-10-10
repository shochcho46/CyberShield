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
        Schema::create('tracking_link_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_link_id')->constrained('tracking_links')->cascadeOnDelete();

            // Network / location
            $table->string('ip_address')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code')->nullable();
            $table->string('region_name')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('timezone')->nullable();
            $table->string('isp')->nullable();
            $table->string('organization')->nullable();

            // Device / software
            $table->string('device_type')->nullable();
            $table->string('device_model')->nullable();
            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->boolean('is_robot')->default(0);

            // Request context
            $table->text('referer')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_link_visits');
    }
};
