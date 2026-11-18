<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Put the hotel's own booking conditions on the site.
 *
 * The copy seeded first was invented around the edges - a 50% deposit, free
 * cancellation inside seven days - and it disagreed with the conditions the
 * hotel actually trades on. This replaces the three places a guest reads them:
 * the deposit the booking flow asks for, the cancellation summary shown before
 * a guest commits, and the policy page itself.
 *
 * Only the seeded deposit is moved. A figure the hotel has since set to
 * something else is left alone, for the same reason the VAT migration leaves a
 * deliberate rate alone.
 */
return new class extends Migration
{
    private const DEPOSIT_THE_SITE_SEEDED = '50';

    private const CANCELLATION = 'Cancellations must be in writing. The 25% deposit is non-refundable, and the charge rises with shorter notice: the deposit plus 30% under 8 weeks, 50% under 4, 60% under 3, and 100% under 2. The full scale is on the booking policies page.';

    private const POLICIES = <<<'TEXT'
        Rates are quoted per room per night in Malawian Kwacha, include breakfast for two, and include government value added tax and the tourism levy. There is nothing to add at checkout: the price you are quoted is the price you pay.

        A booking is confirmed when we issue the confirmation invoice, which is also our acceptance of it. A deposit of 25% of the invoice is payable then and is non-refundable. Once your signed information form and the deposit are with us, we reserve your room, subject to availability.

        The balance is due no later than four weeks before arrival. When it is paid we send your vouchers and the information you need for the trip, and we ask you to read it before you leave.

        A cancellation is only effective once we have it in writing, and what it costs depends on the notice we are given. More than eight weeks before arrival, the 25% deposit is forfeited. Less than eight weeks, the deposit plus 30% of the invoice. Less than four weeks, 50%. Less than three weeks, 60%. Less than two weeks, 100%.

        Changing a confirmed booking, including moving a departure earlier, carries an amendment fee of US$25 and is subject to availability. Postponing a departure is treated as a cancellation, so the normal charges apply.

        Malawi is a malarial area, so please take your doctor's advice on anti-malarial precautions before travelling. A yellow fever vaccination is required, and proof of it may be asked for at border points.

        Prices can rise after confirmation if government taxes, exchange rates or other costs outside our control move. We do our best to hold published prices, but we reserve the right to surcharge without notice, up to a maximum of 10% of the invoice.

        Every effort goes into having your room and any excursion ready as planned. If something is unavailable and no reasonable alternative can be found, no refund is due, whole or part, and the same applies to any service in the itinerary a guest does not use.

        Lakeside Hotel, and anyone acting for it, is not liable for loss or damage arising from delays, sickness, theft, injury or death, or from errors or omissions in these conditions. We may cancel a booking, or change the accommodation, price or other arrangements, at our discretion, and extra costs that follow from that are the guest's.

        Check in is from 14:00 and check out is by 11:00. Later check out can be arranged at reception, subject to availability. We accept card payments and mobile money through Airtel Money and TNM Mpamba, or you can reserve now and settle at the hotel.
        TEXT;

    public function up(): void
    {
        DB::table('settings')
            ->where('key', 'booking.deposit_percentage')
            ->where('value', self::DEPOSIT_THE_SITE_SEEDED)
            ->update(['value' => '25', 'updated_at' => now()]);

        DB::table('settings')
            ->where('key', 'booking.cancellation_policy')
            ->update(['value' => self::CANCELLATION, 'updated_at' => now()]);

        DB::table('content_blocks')
            ->where('key', 'booking.policies')
            ->update(['body' => self::POLICIES, 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     *
     * The earlier wording is not restored. It was wrong about the hotel's own
     * terms, and putting it back would be reinstating a mistake.
     */
    public function down(): void
    {
        //
    }
};
