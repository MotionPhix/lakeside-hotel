<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cancelling a reservation.
 *
 * The reason is optional to type but is kept on the booking when it is given,
 * because "why did this guest cancel" is the question asked months later.
 */
final class CancelBookingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function reason(): ?string
    {
        $reason = $this->string('reason')->trim()->value();

        return $reason === '' ? null : $reason;
    }
}
