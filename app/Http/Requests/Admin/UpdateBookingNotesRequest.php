<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The note the desk keeps about a reservation: a late arrival, a birthday, the
 * name of the person paying. Never shown to the guest, which is why it is kept
 * apart from `special_requests`.
 */
final class UpdateBookingNotesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * The note as it should be stored: blank means no note rather than an empty
     * string, so the page and the database agree on what "nothing written" is.
     */
    public function notes(): ?string
    {
        $notes = trim((string) $this->input('internal_notes', ''));

        return $notes === '' ? null : $notes;
    }
}
