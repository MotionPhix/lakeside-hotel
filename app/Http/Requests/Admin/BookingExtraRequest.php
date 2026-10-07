<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use App\Models\Activity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * An extra the desk is adding to a stay.
 *
 * Guests pick extras while they book; the desk sells them over the phone, at
 * check-in and at the bar, so the same rules have to hold here. How many may be
 * added is a property of the activity rather than of the field, so it is checked
 * once the catalogue has been read - by the same `quantityBounds()` the booking
 * form and the reservation itself use, which is what stops the two entrances
 * disagreeing about what is on offer.
 */
class BookingExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageBookings->value) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'integer', 'exists:activities,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * Whether the activity may be sold at all, and in what number.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /* A miss is already reported by `exists`, so there is nothing to
                   add to it here. */
                $activity = Activity::query()->find((int) $this->input('activity_id'));

                if (! $activity instanceof Activity) {
                    return;
                }

                if (! $activity->is_active) {
                    $validator->errors()->add(
                        'activity_id',
                        'That extra is no longer offered.',
                    );

                    return;
                }

                if ($activity->isComplimentary()) {
                    $validator->errors()->add(
                        'activity_id',
                        'That extra is complimentary, so there is no price to add to the folio.',
                    );

                    return;
                }

                $bounds = $activity->quantityBounds();
                $quantity = (int) $this->input('quantity');

                if ($quantity < $bounds['min'] || $quantity > $bounds['max']) {
                    $validator->errors()->add(
                        'quantity',
                        $bounds['label'] === null
                            ? 'That extra is priced for the group and needs no quantity.'
                            : "Choose between {$bounds['min']} and {$bounds['max']} {$bounds['label']}.",
                    );
                }
            },
        ];
    }

    /**
     * The activity being added. Validation has already established that it exists.
     */
    public function activity(): Activity
    {
        return Activity::query()->findOrFail((int) $this->input('activity_id'));
    }

    public function quantity(): int
    {
        return (int) $this->validated('quantity');
    }
}
