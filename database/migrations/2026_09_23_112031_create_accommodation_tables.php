<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the hotel sells and sleeps: amenities, the bookable room categories,
 * the physical rooms inside them, rate plans and maintenance blocks.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('category')->default('general')->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->unsignedTinyInteger('capacity_adults')->default(2);
            $table->unsignedTinyInteger('capacity_children')->default(0);
            $table->unsignedSmallInteger('size_sqm')->nullable();
            $table->string('bed_configuration')->nullable();
            $table->decimal('base_price', 12, 2);
            $table->decimal('weekend_price', 12, 2)->nullable();
            $table->decimal('extra_person_price', 12, 2)->default(0);
            $table->unsignedTinyInteger('min_nights')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Named after both tables in alphabetical order, which is the pivot name
        // Eloquent derives for the RoomType/Amenity relationship.
        Schema::create('amenity_room_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->unique(['room_type_id', 'amenity_id']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('number')->nullable();
            $table->string('floor')->nullable();
            $table->string('status')->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['room_type_id', 'name']);
        });

        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('seasonal')->index();
            $table->string('adjustment_type')->default('fixed');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedTinyInteger('min_nights')->nullable();
            $table->json('days_of_week')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('availability_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->default('maintenance');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_blocks');
        Schema::dropIfExists('rate_plans');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('amenity_room_type');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('amenities');
    }
};
