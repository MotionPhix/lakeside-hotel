<?php

namespace App\Http\Requests\Admin;

use App\Models\Guest;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Correcting a guest's record.
 *
 * A guest record is built from whatever they typed at booking time, so it holds
 * the mistakes of somebody filling in a form on a phone - a name spelled wrong, a
 * country that is really a town. This is where the desk fixes that, which is why
 * almost everything here is optional: a receptionist tidying a phone number should
 * not be made to supply an address at the same time.
 */
final class UpdateGuestRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('guests', 'email')->ignore($this->guest()->getKey()),
            ],
            // The same rule the booking form uses, so a number that was refused
            // to a guest is refused to the desk typing it in on their behalf.
            'phone' => Phone::rules(),
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another guest is already on file with that email address.',
        ];
    }

    /**
     * The guest being edited, taken from the route.
     */
    public function guest(): Guest
    {
        $guest = $this->route('guest');

        return $guest instanceof Guest ? $guest : Guest::query()->findOrFail($guest);
    }

    /**
     * A blank box is stored as nothing rather than as an empty string, so the page
     * and the database agree on what "not filled in" looks like.
     */
    public function value(string $field): ?string
    {
        $value = trim((string) $this->input($field, ''));

        return $value === '' ? null : $value;
    }
}
