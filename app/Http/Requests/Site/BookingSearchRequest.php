<?php

namespace App\Http\Requests\Site;

use App\Services\Booking\StayRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * The dates and party a guest is searching with.
 *
 * Every field is optional: the booking page is reachable before anything has
 * been chosen, and answers with the search form rather than an error.
 */
final class BookingSearchRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date', 'after:check_in'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:12'],
            'children' => ['nullable', 'integer', 'min:0', 'max:12'],
            'room_type' => ['nullable', 'string', 'exists:room_types,slug'],
        ];
    }

    /**
     * The booking widget offers "Any room" as a value rather than an empty
     * choice, so the sentinel is dropped here instead of leaking into the
     * validator as an unknown category.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('room_type') === 'any') {
            $this->merge(['room_type' => null]);
        }
    }

    /**
     * The stay being asked about, or null while the guest is still choosing.
     */
    public function stay(): ?StayRequest
    {
        $checkIn = $this->dates('check_in');
        $checkOut = $this->dates('check_out');

        if ($checkIn === null || $checkOut === null || $checkIn->gte($checkOut)) {
            return null;
        }

        return new StayRequest(
            checkIn: $checkIn,
            checkOut: $checkOut,
            adults: (int) $this->input('adults', 2),
            children: (int) $this->input('children', 0),
            roomTypeSlug: $this->input('room_type'),
        );
    }

    private function dates(string $key): ?Carbon
    {
        $value = $this->input($key);

        return is_string($value) && $value !== '' ? Carbon::parse($value)->startOfDay() : null;
    }
}
