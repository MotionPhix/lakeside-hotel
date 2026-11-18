<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Move the stored VAT rate from 16.5% to 17.5%.
 *
 * The seeder writes this figure only on an empty database, so on a site that is
 * already running nothing would change it: `Tax::vatRate()` reads the setting
 * before it reads the constant, and the booking policy page prints the setting
 * verbatim, so the guest-facing line would keep saying 16.5%.
 *
 * Only the old statutory rate is moved. A hotel that has deliberately stored
 * something else - a zero-rated period, a negotiated figure - keeps it, because
 * this is a rate change, not a correction of whatever is there.
 *
 * Bookings already on file are left alone. Their tax was calculated at the rate
 * in force when they were taken, and a folio is a record of what was charged,
 * not a live calculation.
 */
return new class extends Migration
{
    private const OLD = '16.5';

    private const NEW = '17.5';

    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'booking.vat_rate')
            ->where('value', self::OLD)
            ->update(['value' => self::NEW, 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'booking.vat_rate')
            ->where('value', self::NEW)
            ->update(['value' => self::OLD, 'updated_at' => now()]);
    }
};
