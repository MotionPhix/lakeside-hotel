<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest voice and marketing: reviews with the hotel's reply, special offers,
 * website inquiries and the newsletter list.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name');
            $table->string('guest_country')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('title')->nullable();
            $table->text('quote');
            $table->date('stayed_on')->nullable();
            $table->string('source')->default('website');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description');
            $table->string('highlight')->nullable();
            $table->string('discount_label')->nullable();
            $table->string('type')->default('seasonal')->index();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('coupon_code')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('type')->default('general')->index();
            $table->string('status')->default('new')->index();
            $table->date('preferred_date')->nullable();
            $table->unsignedSmallInteger('guests_count')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->text('response')->nullable();
            $table->string('source_page')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('status')->default('subscribed')->index();
            $table->string('source')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('testimonials');
    }
};
