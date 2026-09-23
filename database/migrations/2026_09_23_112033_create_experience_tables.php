<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Everything the hotel sells that is not a bed: restaurants and bars with their
 * menus, lake activities and experiences, and conference / wedding packages.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dining_venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('restaurant')->index();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('dress_code')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dining_venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('category')->default('mains')->index();
            $table->boolean('is_signature')->default(false);
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('price_basis')->default('per_person');
            $table->unsignedTinyInteger('min_participants')->nullable();
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('conference_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('conference')->index();
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->unsignedSmallInteger('capacity_min')->nullable();
            $table->unsignedSmallInteger('capacity_max')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('price_basis')->default('per_person');
            $table->json('includes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conference_packages');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('dining_venues');
    }
};
