<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content that drives the public website: hotel settings, editable copy blocks,
 * homepage hero slides, the photo/video gallery and nearby attractions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('section')->default('general')->index();
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('body')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('headline');
            $table->string('subheadline')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('caption')->nullable();
            $table->string('category')->default('hotel')->index();
            $table->string('type')->default('image');
            $table->string('video_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('nearby_attractions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('nature');
            $table->text('description')->nullable();
            $table->decimal('distance_km', 6, 2)->nullable();
            $table->unsignedSmallInteger('travel_time_minutes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nearby_attractions');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('settings');
    }
};
