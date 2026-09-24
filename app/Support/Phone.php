<?php

namespace App\Support;

use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Phone numbers, in one place.
 *
 * The hotel takes bookings from Senga Bay and from overseas, so a number is
 * accepted either way: a local one typed the way it is spoken (0999 123 456,
 * with the hotel's own country filling in the code), or a full international one
 * from a guest booking from home. Anything that is not a real number is refused,
 * which is the part that matters - a mistyped number on a booking is a guest
 * nobody can reach on the day they arrive.
 *
 * Both the validation rule and the storage format come from here, so a field
 * cannot be validated as one thing and stored as another.
 */
final class Phone
{
    /**
     * The rules a phone field is validated with.
     *
     * @return list<string>
     */
    public static function rules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            // Length is capped for the same reason the number itself is checked:
            // nothing legitimate approaches it, so a longer value is a mistake.
            'max:40',
            'phone:'.self::country().',INTERNATIONAL',
        ];
    }

    /**
     * The country a number is understood in when it carries no code of its own.
     */
    public static function country(): string
    {
        return (string) config('hotel.country', 'MW');
    }

    /**
     * Canonical form: E.164, which is what a phone system and a WhatsApp link
     * both want, and what makes two spellings of one number compare equal.
     *
     * Anything that cannot be read is returned untouched rather than emptied. A
     * seeder, an import or a row from before this existed should not be quietly
     * blanked by a value the parser simply did not recognise.
     */
    public static function normalise(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        if ($value === null || $value === '') {
            return null;
        }

        try {
            $formatted = (new PhoneNumber($value, self::country()))->formatE164();
        } catch (Throwable) {
            return $value;
        }

        return $formatted === '' ? $value : $formatted;
    }
}
