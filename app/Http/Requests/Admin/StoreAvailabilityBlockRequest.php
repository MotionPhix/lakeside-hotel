<?php

namespace App\Http\Requests\Admin;

use App\Enums\AvailabilityBlockReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Closing a room for a range of nights.
 *
 * Both dates are inclusive, which is both how a block is stored and how the
 * availability engine reads it: a room shut from the 10th to the 12th is off sale
 * for two nights, and the desk thinks of it the same way.
 *
 * Whether the room can actually be closed depends on the rest of the building, so
 * that decision is left to the controller and the allocation service rather than
 * to a rule that cannot see which guests are already in.
 */
final class StoreAvailabilityBlockRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['required', Rule::enum(AvailabilityBlockReason::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'room_id.required' => 'Choose the room you are closing.',
            'room_id.exists' => 'Choose the room you are closing.',
            'reason.required' => 'Choose why the room is being closed.',
            'ends_on.after_or_equal' => 'The last night cannot be before the first one.',
        ];
    }

    /**
     * The first night the room is out of service.
     */
    public function startsOn(): Carbon
    {
        return Carbon::parse((string) $this->input('starts_on'))->startOfDay();
    }

    /**
     * The last night the room is out of service.
     */
    public function endsOn(): Carbon
    {
        return Carbon::parse((string) $this->input('ends_on'))->startOfDay();
    }

    public function reason(): AvailabilityBlockReason
    {
        return AvailabilityBlockReason::from((string) $this->input('reason'));
    }

    /**
     * A blank note is no note, so the page and the database agree on what an
     * empty field means.
     */
    public function notes(): ?string
    {
        $notes = trim((string) $this->input('notes', ''));

        return $notes === '' ? null : $notes;
    }
}
