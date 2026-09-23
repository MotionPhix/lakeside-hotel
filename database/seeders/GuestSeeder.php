<?php

namespace Database\Seeders;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentOption;
use App\Enums\PaymentRecordStatus;
use App\Enums\Role;
use App\Models\Booking;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * A working set of guests, reservations, payments and enquiries, so the
 * dashboard has something real to show and the reports have data to chart.
 *
 * Bookings are spread across the calendar: stays that have finished, guests in
 * house today, arrivals booked for the weeks ahead, and a few that were
 * cancelled or never paid.
 */
class GuestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedGuests();
        $this->seedBookings();
        $this->seedInquiries();
    }

    /**
     * The guest book.
     */
    private function seedGuests(): void
    {
        Guest::factory()->count(24)->create();
        Guest::factory()->local()->count(16)->create();
        Guest::factory()->subscribed()->count(8)->create();
    }

    /**
     * Reservations across the whole booking lifecycle.
     */
    private function seedBookings(): void
    {
        $roomTypes = RoomType::query()->active()->get();
        $guests = Guest::query()->get();
        $receptionist = User::query()->where('role', Role::Reception->value)->first();

        if ($roomTypes->isEmpty() || $guests->isEmpty()) {
            return;
        }

        // Stays that have already finished.
        foreach (range(1, 20) as $ignored) {
            $checkIn = now()->subDays(fake()->numberBetween(8, 150))->startOfDay();
            $this->createStay(
                roomType: $roomTypes->random(),
                guest: $guests->random(),
                checkIn: $checkIn,
                nights: fake()->numberBetween(1, 6),
                status: BookingStatus::CheckedOut,
                staff: $receptionist,
                paymentPlan: fake()->randomElement(['paid', 'paid', 'paid', 'desk']),
            );
        }

        // Guests who are in house right now.
        foreach (range(1, 5) as $ignored) {
            $nights = fake()->numberBetween(2, 5);
            $checkIn = now()->subDays(fake()->numberBetween(1, $nights - 1))->startOfDay();
            $this->createStay(
                roomType: $roomTypes->random(),
                guest: $guests->random(),
                checkIn: $checkIn,
                nights: $nights,
                status: BookingStatus::CheckedIn,
                staff: $receptionist,
                paymentPlan: fake()->randomElement(['paid', 'deposit', 'desk']),
            );
        }

        // Arrivals confirmed for the weeks ahead. The payment plans cycle rather
        // than being random, so the seeded dataset is reproducible.
        $confirmedPlans = ['deposit', 'paid', 'unpaid', 'deposit'];

        foreach (range(1, 16) as $index) {
            $checkIn = now()->addDays(fake()->numberBetween(2, 75))->startOfDay();
            $this->createStay(
                roomType: $roomTypes->random(),
                guest: $guests->random(),
                checkIn: $checkIn,
                nights: fake()->numberBetween(1, 7),
                status: BookingStatus::Confirmed,
                staff: null,
                paymentPlan: $confirmedPlans[$index % count($confirmedPlans)],
            );
        }

        // Requests that have come in but not been confirmed yet.
        foreach (range(1, 6) as $ignored) {
            $checkIn = now()->addDays(fake()->numberBetween(3, 60))->startOfDay();
            $this->createStay(
                roomType: $roomTypes->random(),
                guest: $guests->random(),
                checkIn: $checkIn,
                nights: fake()->numberBetween(1, 4),
                status: BookingStatus::Pending,
                staff: null,
                paymentPlan: 'unpaid',
            );
        }

        // Cancellations, including one that has been refunded.
        foreach (range(1, 4) as $ignored) {
            $checkIn = now()->addDays(fake()->numberBetween(5, 50))->startOfDay();
            $this->createStay(
                roomType: $roomTypes->random(),
                guest: $guests->random(),
                checkIn: $checkIn,
                nights: fake()->numberBetween(1, 4),
                status: BookingStatus::Cancelled,
                staff: $receptionist,
                paymentPlan: 'refunded',
            );
        }

        // One guest who did not turn up.
        $this->createStay(
            roomType: $roomTypes->random(),
            guest: $guests->random(),
            checkIn: now()->subDays(4)->startOfDay(),
            nights: 2,
            status: BookingStatus::NoShow,
            staff: $receptionist,
            paymentPlan: 'unpaid',
        );
    }

    /**
     * Enquiries from the website and the events team.
     */
    private function seedInquiries(): void
    {
        $receptionist = User::query()->where('role', Role::Reception->value)->first();
        $manager = User::query()->where('role', Role::HotelManager->value)->first();

        $inquiries = [
            ['Tamara Nkhoma', 'tamara.nkhoma@example.com', 'Availability for the long weekend', 'Good morning. Do you have two deluxe rooms available for the coming long weekend, Friday to Monday, for four adults and two children? Please also let me know whether the pool is open in the evenings.', InquiryType::Booking, InquiryStatus::New, 4, 6, '/booking', null],
            ['Emmanuel Chitsulo', 'e.chitsulo@example.com', 'Conference for 80 delegates', 'We are planning a two day strategy conference in November and would like a quote for 80 delegates, day delegate rate, with accommodation for 20 of them. Do you have the conference room free from the 12th?', InquiryType::Conference, InquiryStatus::InProgress, 60, 80, '/conferences', 'manager'],
            ['Sarah Whitfield', 'sarah.whitfield@example.com', 'Wedding in September next year', 'We would like to hold our wedding on the beach in September. Around 90 guests. Could you send the wedding package and let us know about accommodation for guests? We are flexible on the exact date.', InquiryType::Wedding, InquiryStatus::New, 330, 90, '/conferences', null],
            ['Blessings Kalua', 'blessings.kalua@example.com', 'Fishing trip availability', 'Does the hotel arrange fishing trips for two people in the second week of next month? We would like to go out early morning and have what we catch cooked for lunch.', InquiryType::General, InquiryStatus::Responded, 12, 2, '/activities', 'reception'],
            ['Anna Schmidt', 'anna.schmidt@example.com', 'Group booking for a school trip', 'We are a school group of 34 students and 4 teachers looking for three nights. Do you offer group rates and can you arrange a village tour and the boat trip to Lizard Island?', InquiryType::Group, InquiryStatus::InProgress, 100, 38, '/contact', 'manager'],
            ['Joseph Kamanga', 'joseph.kamanga@example.com', 'Airport transfer from Lilongwe', 'We are arriving at Kamuzu International at 14:20 on the 18th. Can you arrange a transfer for four people with luggage, and how much would that be?', InquiryType::General, InquiryStatus::Responded, 9, 4, '/contact', 'reception'],
            ['Patricia Longwe', 'patricia.longwe@example.com', 'Honeymoon package query', 'My partner and I are looking at the honeymoon package for five nights. Is the private dinner on the veranda included, and can it be arranged for the second night?', InquiryType::Booking, InquiryStatus::Responded, 45, 2, '/offers', 'reception'],
            ['Michael Banda', 'michael.banda@example.com', 'Birthday party on the terrace', 'I would like to book the terrace for my wife\'s fiftieth, around 60 people, on a Saturday evening. Please send a quote for food and bar.', InquiryType::Event, InquiryStatus::New, 30, 60, '/contact', null],
            ['Grace Tembo', 'grace.tembo@example.com', 'Thank you for a wonderful stay', 'We stayed in chalet three last week and wanted to say thank you. The staff were exceptional, particularly the gentleman who took us out fishing. We will be back.', InquiryType::Feedback, InquiryStatus::Closed, null, null, '/contact', 'manager'],
            ['Robert Mphande', 'robert.mphande@example.com', 'Cheap rooms cheap rooms', 'CLICK HERE FOR CHEAP ROOMS BOOK NOW BEST PRICES GUARANTEED www.example-deals.invalid', InquiryType::General, InquiryStatus::Spam, null, null, '/contact', null],
            ['Fatima Nyasulu', 'fatima.nyasulu@example.com', 'Do you allow day visitors?', 'We live in Salima and would like to use the pool and have lunch on a Saturday without staying overnight. Is that possible and what do you charge?', InquiryType::General, InquiryStatus::New, 7, 4, '/contact', null],
            ['Daniel Mvula', 'daniel.mvula@example.com', 'Corporate account for our company', 'We are a logistics company with staff travelling through Salima regularly. Do you offer a corporate rate and how do we set up an account?', InquiryType::Conference, InquiryStatus::InProgress, null, null, '/contact', 'manager'],
            ['Hannah Wright', 'hannah.wright@example.com', 'Green season rates', 'I saw the green season offer. Does it apply over Easter, and is it valid for the chalets as well as the rooms?', InquiryType::Booking, InquiryStatus::Responded, 80, 2, '/offers', 'reception'],
            ['Isaac Banda', 'isaac.banda@example.com', 'Lost property', 'I think I left a phone charger in room 204 last weekend. Could you check and let me know? Happy to pay for postage.', InquiryType::General, InquiryStatus::InProgress, null, null, '/contact', 'reception'],
        ];

        foreach ($inquiries as [$name, $email, $subject, $message, $type, $status, $daysAhead, $guests, $page, $assignee]) {
            $assigneeId = match ($assignee) {
                'reception' => $receptionist?->id,
                'manager' => $manager?->id,
                default => null,
            };

            $responded = in_array($status, [InquiryStatus::Responded, InquiryStatus::Closed], true);

            Inquiry::query()->updateOrCreate(
                ['email' => $email, 'subject' => $subject],
                [
                    'name' => $name,
                    'phone' => '+265 99 '.fake()->numerify('### ####'),
                    'message' => $message,
                    'type' => $type,
                    'status' => $status,
                    'preferred_date' => $daysAhead === null ? null : now()->addDays($daysAhead)->toDateString(),
                    'guests_count' => $guests,
                    'assigned_to' => $assigneeId,
                    'responded_at' => $responded ? now()->subDays(fake()->numberBetween(1, 5)) : null,
                    'response' => $responded ? 'Thank you for getting in touch. Our reservations team has replied to you directly with the details you asked for.' : null,
                    'source_page' => $page,
                ],
            );
        }
    }

    /**
     * Build one complete stay: the booking, its room line, the money, and the
     * timestamps that match its status.
     */
    private function createStay(
        RoomType $roomType,
        Guest $guest,
        CarbonInterface $checkIn,
        int $nights,
        BookingStatus $status,
        ?User $staff,
        string $paymentPlan,
    ): void {
        $checkOut = $checkIn->copy()->addDays($nights);
        $adults = max(min(fake()->numberBetween(1, 2), $roomType->capacity_adults), 1);
        $children = $roomType->capacity_children > 0
            ? fake()->numberBetween(0, min(2, $roomType->capacity_children))
            : 0;

        // Price each night at the rate that applies on it, so weekend nights cost more.
        $nightlyRates = [];
        $subtotal = 0.0;

        for ($night = 0; $night < $nights; $night++) {
            $date = $checkIn->copy()->addDays($night);
            $rate = (float) $roomType->nightlyRateFor($date);

            $nightlyRates[$date->toDateString()] = number_format($rate, 2, '.', '');
            $subtotal += $rate;
        }

        $paymentOption = match ($status) {
            BookingStatus::Pending, BookingStatus::Cancelled, BookingStatus::NoShow => PaymentOption::PayAtHotel,
            default => fake()->randomElement([PaymentOption::PayNow, PaymentOption::PayAtHotel, PaymentOption::BankTransfer]),
        };

        $booking = Booking::query()->create([
            'reference' => Booking::generateReference(),
            'guest_id' => $guest->id,
            'status' => $status,
            'source' => fake()->randomElement(BookingSource::cases()),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights,
            'adults' => $adults,
            'children' => $children,
            'currency' => 'MWK',
            'payment_method' => $paymentOption,
            'special_requests' => fake()->boolean(30)
                ? fake()->randomElement([
                    'Late check in, arriving after 21:00.',
                    'High chair needed at breakfast.',
                    'Quiet room away from the pool, please.',
                    'Celebrating an anniversary.',
                    'Extra towels and a cot for a baby.',
                ])
                : null,
            'airport_transfer' => fake()->boolean(25),
            'transfer_details' => null,
            'created_by' => $staff?->id,
            'confirmed_at' => $status === BookingStatus::Pending ? null : $checkIn->copy()->subDays(fake()->numberBetween(3, 20)),
            'checked_in_at' => in_array($status, [BookingStatus::CheckedIn, BookingStatus::CheckedOut], true) ? $checkIn : null,
            'checked_out_at' => $status === BookingStatus::CheckedOut ? $checkOut : null,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now()->subDays(fake()->numberBetween(1, 10)) : null,
            'cancellation_reason' => $status === BookingStatus::Cancelled ? 'Guest changed their travel plans.' : null,
        ]);

        $booking->items()->create([
            'room_type_id' => $roomType->id,
            'adults' => $adults,
            'children' => $children,
            'price_per_night' => number_format($subtotal / $nights, 2, '.', ''),
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'nightly_rates' => $nightlyRates,
        ]);

        $booking->recalculateTotals()->save();

        $this->recordPayment($booking, $paymentPlan);
    }

    /**
     * Put money against a booking according to the plan the seeder chose.
     */
    private function recordPayment(Booking $booking, string $paymentPlan): void
    {
        if ($paymentPlan === 'unpaid') {
            $booking->refresh()->syncPaymentStatus()->save();

            return;
        }

        $total = (float) $booking->total;
        $atDesk = $paymentPlan === 'desk';

        $amount = $paymentPlan === 'deposit'
            ? round($total / 2, 2)
            : $total;

        $cancelled = $booking->status === BookingStatus::Cancelled;

        Payment::query()->create([
            'booking_id' => $booking->id,
            'provider' => $atDesk ? Payment::PROVIDER_MANUAL : Payment::PROVIDER_PAYCHANGU,
            'provider_reference' => $atDesk ? null : 'PCH-'.fake()->unique()->numerify('##########'),
            'provider_transaction_id' => $atDesk ? null : fake()->uuid(),
            'method' => $atDesk
                ? PaymentMethod::Cash
                : fake()->randomElement([PaymentMethod::Card, PaymentMethod::AirtelMoney, PaymentMethod::TnmMpamba]),
            'amount' => $amount,
            'currency' => 'MWK',
            'status' => $cancelled ? PaymentRecordStatus::Refunded : PaymentRecordStatus::Successful,
            'paid_at' => $booking->confirmed_at ?? now()->subDays(2),
            'payload' => $atDesk ? null : ['status' => 'success', 'source' => 'seeder'],
            'refunded_amount' => $cancelled ? $amount : 0,
            'refunded_at' => $cancelled ? now()->subDays(2) : null,
        ]);

        $booking->refresh()->syncPaymentStatus()->save();
    }
}
