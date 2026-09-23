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
        /*
         * The named conference halls. These are physical spaces with their own
         * capacities, distinct from the packages sold to fill them, so the hotel
         * can advertise "Namalenje, 250 delegates" without it living in a template.
         */
        Schema::create('conference_halls', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('capacity');
            $table->string('layout')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /*
         * Which sections each public page renders, in what order, with the
         * editable heading copy for each. The frontend walks this list instead of
         * hardcoding sections, so the hotel can reorder, retitle, hide or switch
         * off any section from the dashboard.
         */
        Schema::create('site_sections', function (Blueprint $table) {
            $table->id();
            $table->string('page')->index();
            $table->string('key');
            $table->string('eyebrow')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['page', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_sections');
        Schema::dropIfExists('conference_halls');
    }
};
