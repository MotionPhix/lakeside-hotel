<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The priced extras a guest added to a stay: an activity, a boat trip, hours on
 * the water.
 *
 * The name, the basis and the unit price are copies rather than references. That
 * is the same reason a room line keeps `nightly_rates`: a folio has to say what
 * the guest agreed to pay, and it must not be rewritten because the hotel later
 * re-priced the sunset cruise. `activity_id` is kept for reporting and to link
 * back, and is nullable with the reference dropped to null so that retiring an
 * activity from the catalogue cannot delete the history of having sold it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();

            /* What was sold, as it was described at the time. */
            $table->string('name');
            $table->string('price_basis')->default('per_person');

            $table->decimal('unit_price', 12, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            /* The folio is always read for one booking, in the order it was added. */
            $table->index(['booking_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extras');
    }
};
