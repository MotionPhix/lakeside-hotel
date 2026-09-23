<?php

namespace App\Http\Requests\Site;

use App\Enums\PaymentOption;
use App\Services\Booking\StayRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * A guest committing to a stay: the dates, the party, who they are, and how they
 * intend to pay.
 */
final class BookingStoreRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_type' => ['required', 'string', 'exists:room_types,slug'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:12'],
            'children' => ['required', 'integer', 'min:0', 'max:12'],

            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['required', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],

            'payment_option' => ['required', Rule::enum(PaymentOption::class)],

            'airport_transfer' => ['boolean'],
            'transfer_flight' => ['nullable', 'string', 'max:40'],
            'transfer_arrival' => ['nullable', 'string', 'max:60'],

            'special_requests' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'marketing_opt_in' => ['boolean'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_out.after' => 'The check out date has to be after the check in date.',
            'terms.accepted' => 'Please accept the booking terms to continue.',
        ];
    }

    public function stay(): StayRequest
    {
        return new StayRequest(
            checkIn: Carbon::parse((string) $this->input('check_in'))->startOfDay(),
            checkOut: Carbon::parse((string) $this->input('check_out'))->startOfDay(),
            adults: (int) $this->input('adults'),
            children: (int) $this->input('children'),
            roomTypeSlug: (string) $this->input('room_type'),
            couponCode: $this->input('coupon_code'),
        );
    }

    /**
     * Only the guest's own fields, so nothing else in the request can end up on
     * the guest record.
     *
     * @return array<string, mixed>
     */
    public function guestAttributes(): array
    {
        return [
            'first_name' => (string) $this->input('first_name'),
            'last_name' => (string) $this->input('last_name'),
            'email' => (string) $this->input('email'),
            'phone' => $this->input('phone'),
            'country' => $this->input('country'),
            'city' => $this->input('city'),
            'marketing_opt_in' => $this->boolean('marketing_opt_in'),
        ];
    }

    public function paymentOption(): PaymentOption
    {
        return PaymentOption::from((string) $this->input('payment_option'));
    }

    /**
     * The arrival details for a requested airport pickup.
     *
     * @return array<string, mixed>|null
     */
    public function transfer(): ?array
    {
        if (! $this->boolean('airport_transfer')) {
            return null;
        }

        return array_filter([
            'flight_number' => $this->input('transfer_flight'),
            'arrival' => $this->input('transfer_arrival'),
        ]);
    }
}
