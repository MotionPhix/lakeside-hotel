<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the columns the desk's everyday queries filter on.
     *
     * Arrivals and departures, the occupancy sweep and the booked-today figure
     * all filtered on these, and every one of them read the whole table.
     *
     * `booking_items.room_type_id` is deliberately absent: MySQL already
     * indexes it to back the foreign key, so an explicit index would be a
     * duplicate that costs write throughput and buys nothing. Check it before
     * adding one - `php artisan db:table booking_items`.
     *
     * `check_out` is here even though the audit only listed `check_in`:
     * partingToday() and coveringDate() both filter on it, so it is the same
     * problem by the same argument.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('check_in');
            $table->index('check_out');
            $table->index('created_at');
        });

        // A block covers a range, so overlap is a range test on both ends. The
        // composite at least narrows on the start before the end is checked.
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->index(['starts_on', 'ends_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('availability_blocks', function (Blueprint $table) {
            $table->dropIndex(['starts_on', 'ends_on']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['check_in']);
            $table->dropIndex(['check_out']);
            $table->dropIndex(['created_at']);
        });
    }
};
