<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Support\HotelMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/*
 * The overview is only worth having if its figures are right, so these tests pin
 * the arithmetic rather than the page: what counts as occupied, what counts as
 * sellable, and which payments count as money taken.
 */

beforeEach(function (): void {
    $this->seed();

    $this->metrics = app(HotelMetrics::class);
    $this->day = Carbon::parse('2026-11-10');
});

test('a booking out of service shrinks capacity rather than becoming a free room', function () {
    $before = $this->metrics->occupancy($this->day);

    expect($before['sellable'])->toBeGreaterThan(0);

    Room::query()->first()->update(['status' => RoomStatus::OutOfService]);

    $after = $this->metrics->occupancy($this->day);

    // The room is not suddenly available for sale - it is no longer part of the
    // hotel's capacity at all, and the percentage is measured against what is left.
    expect($after['sellable'])->toBe($before['sellable'] - 1)
        ->and($after['occupied'])->toBe($before['occupied']);
});

test('a cancelled booking stops counting towards occupancy', function () {
    // A date well clear of anything the seeder books, so the count under test is
    // the one this test creates.
    $night = $this->day->copy()->addMonths(14);

    $booking = Booking::factory()->create([
        'status' => BookingStatus::Confirmed,
        'check_in' => $night->toDateString(),
        'check_out' => $night->copy()->addDays(2)->toDateString(),
        'nights' => 2,
    ]);

    expect($this->metrics->occupancy($night)['occupied'])->toBe(1);

    $booking->update(['status' => BookingStatus::Cancelled]);

    expect($this->metrics->occupancy($night)['occupied'])->toBe(0);
});

test('occupancy never reports more rooms held than the hotel can sell', function () {
    $occupancy = $this->metrics->occupancy($this->day);

    expect($occupancy['occupied'])->toBeLessThanOrEqual($occupancy['sellable'])
        ->and($occupancy['percentage'])->toBeLessThanOrEqual(100.0);
});

test('revenue counts money that settled, not money that was attempted', function () {
    $before = $this->metrics->revenue($this->day);

    $booking = Booking::factory()->create();

    $payment = $booking->payments()->create([
        'provider' => 'manual',
        'amount' => 50_000,
        'currency' => Booking::CURRENCY,
        'status' => PaymentRecordStatus::Pending,
    ]);

    // Revenue is a window on when the money came in, so the payment has to land
    // inside the day under test rather than whenever the suite happens to run.
    $payment->forceFill(['created_at' => $this->day->copy()->setTime(10, 0)])->save();

    expect($this->metrics->revenue($this->day)['taken_today'])
        ->toBe($before['taken_today']);

    $payment->markSuccessful(PaymentMethod::Cash);

    expect((float) $this->metrics->revenue($this->day)['taken_today'])
        ->toBe((float) $before['taken_today'] + 50_000.0);
});

test('what is still owed is the sum of live bookings that have not settled', function () {
    $outstanding = (float) $this->metrics->outstanding();

    $booking = Booking::factory()->create(['status' => BookingStatus::Confirmed]);
    $booking->forceFill(['total' => 100_000, 'amount_paid' => 40_000])->save();

    expect((float) $this->metrics->outstanding())->toBe($outstanding + 60_000.0);

    // A cancelled booking is nobody's debt.
    $booking->update(['status' => BookingStatus::Cancelled]);

    expect((float) $this->metrics->outstanding())->toBe($outstanding);
});

test('the week ahead counts guests arriving after today only', function () {
    $inside = Booking::factory()->create([
        'status' => BookingStatus::Confirmed,
        'check_in' => $this->day->copy()->addDays(3)->toDateString(),
        'check_out' => $this->day->copy()->addDays(5)->toDateString(),
        'nights' => 2,
    ]);

    // Today itself, and something beyond the window: neither belongs in the week
    // ahead, which is about what is still to come.
    Booking::factory()->create([
        'status' => BookingStatus::Confirmed,
        'check_in' => $this->day->toDateString(),
        'check_out' => $this->day->copy()->addDays(2)->toDateString(),
        'nights' => 2,
    ]);

    Booking::factory()->create([
        'status' => BookingStatus::Confirmed,
        'check_in' => $this->day->copy()->addDays(30)->toDateString(),
        'check_out' => $this->day->copy()->addDays(32)->toDateString(),
        'nights' => 2,
    ]);

    $soon = collect($this->metrics->arrivingSoon($this->day));
    $dates = $soon->pluck('date');

    expect($dates)->toContain($inside->check_in->toDateString())
        ->and($dates)->not->toContain($this->day->toDateString())
        ->and($dates)->not->toContain($this->day->copy()->addDays(30)->toDateString());

    foreach ($soon as $day) {
        expect($day['date'])->toBeGreaterThan($this->day->toDateString())
            ->and($day['date'])->toBeLessThanOrEqual($this->day->copy()->addDays(7)->toDateString())
            ->and($day['bookings'])->toBeGreaterThan(0)
            ->and($day['guests'])->toBeGreaterThanOrEqual($day['bookings']);
    }
});
