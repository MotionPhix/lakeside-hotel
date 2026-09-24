<?php

namespace App\Http\Requests\Admin;

use App\Services\Booking\RoomAllocation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The room the desk is giving a guest, or nothing to take one back.
 *
 * Only the room's existence is checked here. Whether it can actually be given to
 * this guest depends on the guest, the dates and what else is happening in the
 * building, so that decision belongs with {@see RoomAllocation}
 * rather than in a rule that cannot see the booking.
 */
final class AssignRoomRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ];
    }
}
