<?php

namespace App\Casts;

use App\Support\Phone;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores a phone number in E.164 and hands it back as a plain string.
 *
 * The package ships casts for this, but both of them return a PhoneNumber value
 * object from `get()` and throw when a stored value is not in international
 * format. Every consumer here - the booking emails, the WhatsApp links, the admin
 * tables - treats a phone number as a string, so adopting either would change the
 * shape of the attribute across half the application to no one's benefit.
 *
 * This keeps the attribute a string and only takes the normalisation: trim it,
 * read it with the hotel's own country as the hint, and write it back in the one
 * format that is unambiguous.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
final class E164Phone implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return Phone::normalise(is_string($value) ? $value : null);
    }
}
