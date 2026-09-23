<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The reservation engine: guests, discount coupons, bookings and the per room
 * lines that price them, plus the payments taken against a booking.
 *
 * Foreign keys that point at booking history use `restrictOnDelete` so a room
 * category or guest record can never silently erase a reservation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('id_number')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->unsignedTinyInteger('min_nights')->nullable();
            $table->decimal('min_spend', 12, 2)->nullable();
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('usage_limit')->nullable();
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('source')->default('website');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedTinyInteger('adults')->default(2);
            $table->unsignedTinyInteger('children')->default(0);
            $table->string('currency', 3)->default('MWK');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_method')->nullable();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->text('special_requests')->nullable();
            $table->boolean('airport_transfer')->default(false);
            $table->json('transfer_details')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rate_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('adults')->default(2);
            $table->unsignedTinyInteger('children')->default(0);
            $table->decimal('price_per_night', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->json('nightly_rates')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('paychangu');
            $table->string('provider_reference')->nullable()->unique();
            $table->string('provider_transaction_id')->nullable();
            $table->string('method')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('MWK');
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('guests');
    }
};
