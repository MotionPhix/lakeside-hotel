<?php

namespace App\Http\Requests\Site;

use App\Enums\PaymentOption;
use App\Models\Activity;
use App\Services\Booking\StayRequest;
use App\Support\Phone;
use Illuminate\Contracts\Validation\Validator;
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
            'phone' => Phone::rules(required: true),
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],

            'payment_option' => ['required', Rule::enum(PaymentOption::class)],

            'airport_transfer' => ['boolean'],
            'transfer_flight' => ['nullable', 'string', 'max:40'],
            'transfer_arrival' => ['nullable', 'string', 'max:60'],

            /* The extras chosen alongside the room. What each one allows is
               checked below, where the catalogue can be read. */
            'extras' => ['nullable', 'array', 'max:20'],
            'extras.*.id' => ['required', 'integer', 'exists:activities,id'],
            'extras.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],

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
            'extras.max' => 'That is more extras than one stay can carry.',
        ];
    }

    /**
     * Whether an extra may be added is a question about the extra, not the value
     * on its own: how many people may come, whether it is still offered, and
     * whether it is priced at all are all properties of the activity. So the range
     * check cannot live on the field as a rule and is done here, where the
     * catalogue can be read.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $chosen = $this->input('extras');

                if (! is_array($chosen) || $chosen === []) {
                    return;
                }

                $wanted = [];

                foreach ($chosen as $extra) {
                    if (is_array($extra) && isset($extra['id'])) {
                        $wanted[] = (int) $extra['id'];
                    }
                }

                $activities = Activity::query()
                    ->whereIn('id', array_unique($wanted))
                    ->get()
                    ->keyBy('id');

                $seen = [];

                foreach ($chosen as $index => $extra) {
                    if (! is_array($extra)) {
                        continue;
                    }

                    $id = (int) ($extra['id'] ?? 0);
                    $activity = $activities->get($id);

                    /* `exists` has already reported anything unknown. */
                    if (! $activity instanceof Activity) {
                        continue;
                    }

                    if (isset($seen[$id])) {
                        $validator->errors()->add(
                            "extras.{$index}.id",
                            'This extra has already been added.',
                        );

                        continue;
                    }

                    $seen[$id] = true;

                    if (! $activity->is_active) {
                        $validator->errors()->add(
                            "extras.{$index}.id",
                            'This extra is no longer offered.',
                        );

                        continue;
                    }

                    if ($activity->isComplimentary()) {
                        $validator->errors()->add(
                            "extras.{$index}.id",
                            'This extra is complimentary and is not charged for.',
                        );

                        continue;
                    }

                    $bounds = $activity->quantityBounds();
                    $quantity = (int) ($extra['quantity'] ?? 0);

                    if ($quantity < $bounds['min'] || $quantity > $bounds['max']) {
                        $validator->errors()->add(
                            "extras.{$index}.quantity",
                            $bounds['label'] === null
                                ? 'This extra is priced for the group and takes no quantity.'
                                : "Choose between {$bounds['min']} and {$bounds['max']} {$bounds['label']}.",
                        );
                    }
                }
            },
        ];
    }

    /**
     * The extras that were chosen, paired with the activity each one came from.
     *
     * Resolved here rather than in the controller so that what is priced is
     * exactly what was validated, and nothing else in the request can reach the
     * reservation.
     *
     * @return array<int, array{activity: Activity, quantity: int}>
     */
    public function extras(): array
    {
        $chosen = $this->validated('extras') ?? [];

        if ($chosen === []) {
            return [];
        }

        $activities = Activity::query()
            ->whereIn('id', array_column($chosen, 'id'))
            ->get()
            ->keyBy('id');

        $resolved = [];

        foreach ($chosen as $extra) {
            $activity = $activities->get((int) $extra['id']);

            if ($activity instanceof Activity) {
                $resolved[] = [
                    'activity' => $activity,
                    'quantity' => (int) $extra['quantity'],
                ];
            }
        }

        return $resolved;
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
