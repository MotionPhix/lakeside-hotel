<?php

namespace App\Cms;

use App\Models\Setting;
use App\Support\Tax;

/**
 * Which settings the dashboard may edit, how each is labelled, and what counts as
 * a valid value for it.
 *
 * The `settings` table stores a section and a type with every row, but neither
 * carries a human label or a rule, and neither should be trusted to decide what a
 * form offers - a value typed into the database would otherwise become a form
 * control. So the editable surface is declared here, and this is the only place it
 * is declared: validation is built from it, the screen is rendered from it, and a
 * setting that is not in it cannot be written through the dashboard at all.
 *
 * Order matters. The sections are listed in the order they appear on the screen,
 * and the booking section comes first because the rates that decide what a guest
 * is charged are the settings most worth finding quickly.
 */
final class SettingSchema
{
    /** A switch. Stored as `1` or `0`, which is what a checkbox posts. */
    public const TOGGLE = 'toggle';

    /** One line of text. */
    public const TEXT = 'text';

    /** Several lines. */
    public const TEXTAREA = 'textarea';

    /**
     * A number. `step` decides the precision the control offers, and the field
     * carries its own bounds so a percentage cannot be saved as 600.
     */
    public const NUMBER = 'number';

    public const EMAIL = 'email';

    /** A time of day, as `HH:MM`, which is how check-in and check-out are stored. */
    public const TIME = 'time';

    /**
     * Every editable setting, grouped into the sections the screen renders.
     *
     * @return list<array<string, mixed>>
     */
    public static function sections(): array
    {
        return [
            [
                'key' => 'booking',
                'label' => 'Rates, tax and booking policy',
                'description' => 'What a guest is charged, and the terms they are charged under.',
                'fields' => [
                    [
                        'key' => Tax::VAT_SETTING,
                        'label' => 'VAT rate',
                        'control' => self::NUMBER,
                        'suffix' => '%',
                        'step' => '0.1',
                        'help' => 'Value added tax on accommodation. Charged on the room rate before any discount.',
                        'rules' => ['numeric', 'min:0', 'max:100'],
                    ],
                    [
                        'key' => Tax::LEVY_SETTING,
                        'label' => 'Tourism levy rate',
                        'control' => self::NUMBER,
                        'suffix' => '%',
                        'step' => '0.1',
                        'help' => 'Tourism levy on accommodation, charged alongside VAT on the same amount.',
                        'rules' => ['numeric', 'min:0', 'max:100'],
                    ],
                    [
                        'key' => Tax::SHOW_SEPARATELY_SETTING,
                        'label' => 'Show taxes separately',
                        'control' => self::TOGGLE,
                        'help' => 'Prices already include VAT and the levy either way. Switch this on to also show them as separate lines under a price. The total does not change.',
                        'rules' => ['boolean'],
                    ],
                    [
                        'key' => 'booking.deposit_percentage',
                        'label' => 'Deposit',
                        'control' => self::NUMBER,
                        'suffix' => '%',
                        'step' => '1',
                        'help' => 'Taken now when a guest chooses to pay a deposit.',
                        'rules' => ['integer', 'min:0', 'max:100'],
                    ],
                    [
                        'key' => 'booking.online_payment_enabled',
                        'label' => 'Online card payment',
                        'control' => self::TOGGLE,
                        'help' => 'Let guests pay by card on the website.',
                        'rules' => ['boolean'],
                    ],
                    [
                        'key' => 'booking.pay_at_hotel_enabled',
                        'label' => 'Pay at the hotel',
                        'control' => self::TOGGLE,
                        'help' => 'Let guests hold a room without paying now, settling on arrival.',
                        'rules' => ['boolean'],
                    ],
                    [
                        'key' => 'hotel.check_in_time',
                        'label' => 'Check in from',
                        'control' => self::TIME,
                        'rules' => ['date_format:H:i'],
                    ],
                    [
                        'key' => 'hotel.check_out_time',
                        'label' => 'Check out by',
                        'control' => self::TIME,
                        'rules' => ['date_format:H:i'],
                    ],
                    [
                        'key' => 'booking.cancellation_policy',
                        'label' => 'Cancellation policy',
                        'control' => self::TEXTAREA,
                        'help' => 'Shown on the booking pages and the confirmation.',
                        'rules' => ['string', 'max:2000'],
                    ],
                    [
                        'key' => 'booking.child_policy',
                        'label' => 'Child policy',
                        'control' => self::TEXTAREA,
                        'rules' => ['string', 'max:2000'],
                    ],
                    [
                        'key' => 'booking.transfer_note',
                        'label' => 'Airport transfer note',
                        'control' => self::TEXTAREA,
                        'rules' => ['string', 'max:2000'],
                    ],
                ],
            ],
            [
                'key' => 'general',
                'label' => 'The hotel',
                'description' => 'Name, capacity and the small facts the site repeats.',
                'fields' => [
                    ['key' => 'hotel.name', 'label' => 'Name', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'hotel.tagline', 'label' => 'Tagline', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'hotel.established', 'label' => 'Established', 'control' => self::TEXT, 'rules' => ['string', 'max:20']],
                    ['key' => 'hotel.rooms_total', 'label' => 'Rooms', 'control' => self::NUMBER, 'step' => '1', 'rules' => ['integer', 'min:0']],
                    ['key' => 'restaurant.capacity', 'label' => 'Restaurant seats', 'control' => self::NUMBER, 'step' => '1', 'rules' => ['integer', 'min:0']],
                    ['key' => 'hotel.currency', 'label' => 'Currency', 'control' => self::TEXT, 'help' => 'The three-letter code prices are quoted in, e.g. MWK.', 'rules' => ['string', 'size:3', 'alpha']],
                ],
            ],
            [
                'key' => 'contact',
                'label' => 'Contact and location',
                'description' => 'Where the hotel is and how to reach it.',
                'fields' => [
                    ['key' => 'hotel.address', 'label' => 'Address', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'hotel.reservations_office', 'label' => 'Reservations office', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'hotel.phone', 'label' => 'Phone', 'control' => self::TEXT, 'rules' => ['string', 'max:40']],
                    ['key' => 'hotel.phone_alt', 'label' => 'Phone (alternative)', 'control' => self::TEXT, 'rules' => ['string', 'max:40']],
                    ['key' => 'hotel.phone_alt_2', 'label' => 'Phone (second alternative)', 'control' => self::TEXT, 'rules' => ['string', 'max:40']],
                    ['key' => 'hotel.whatsapp', 'label' => 'WhatsApp', 'control' => self::TEXT, 'help' => 'Include the country code.', 'rules' => ['string', 'max:40']],
                    ['key' => 'hotel.email', 'label' => 'Reservations email', 'control' => self::EMAIL, 'rules' => ['email', 'max:255']],
                    ['key' => 'hotel.marketing_email', 'label' => 'Marketing email', 'control' => self::EMAIL, 'rules' => ['email', 'max:255']],
                    ['key' => 'hotel.events_email', 'label' => 'Events email', 'control' => self::EMAIL, 'rules' => ['email', 'max:255']],
                    ['key' => 'hotel.website', 'label' => 'Website', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'hotel.latitude', 'label' => 'Latitude', 'control' => self::TEXT, 'help' => 'Used by the map on the contact page.', 'rules' => ['string', 'max:32']],
                    ['key' => 'hotel.longitude', 'label' => 'Longitude', 'control' => self::TEXT, 'rules' => ['string', 'max:32']],
                    ['key' => 'hotel.map_zoom', 'label' => 'Map zoom', 'control' => self::NUMBER, 'step' => '1', 'rules' => ['integer', 'min:1', 'max:20']],
                ],
            ],
            [
                'key' => 'social',
                'label' => 'Social profiles',
                'description' => 'Left blank, a profile is simply not linked.',
                'fields' => [
                    ['key' => 'social.facebook', 'label' => 'Facebook', 'control' => self::TEXT, 'rules' => ['nullable', 'string', 'max:255']],
                    ['key' => 'social.instagram', 'label' => 'Instagram', 'control' => self::TEXT, 'rules' => ['nullable', 'string', 'max:255']],
                    ['key' => 'social.tripadvisor', 'label' => 'Tripadvisor', 'control' => self::TEXT, 'rules' => ['nullable', 'string', 'max:255']],
                ],
            ],
            [
                'key' => 'seo',
                'label' => 'Search engines',
                'description' => 'What a search result or a shared link says about the site.',
                'fields' => [
                    ['key' => 'seo.default_title', 'label' => 'Default title', 'control' => self::TEXT, 'rules' => ['string', 'max:255']],
                    ['key' => 'seo.default_description', 'label' => 'Default description', 'control' => self::TEXTAREA, 'help' => 'Aim for about 155 characters.', 'rules' => ['string', 'max:500']],
                ],
            ],
            [
                'key' => 'analytics',
                'label' => 'Measurement',
                'description' => 'Off unless a provider is chosen. Plausible, fathom and umami set no cookies.',
                'fields' => [
                    [
                        'key' => 'analytics.provider',
                        'label' => 'Provider',
                        'control' => self::TEXT,
                        'help' => 'One of plausible, fathom, umami or google. Left blank, nothing is measured at all.',
                        'rules' => ['nullable', 'string', 'in:,plausible,fathom,umami,google'],
                    ],
                    [
                        'key' => 'analytics.measurement_id',
                        'label' => 'Site or measurement ID',
                        'control' => self::TEXT,
                        'help' => 'The domain for Plausible, the site ID for Fathom and Umami, or the G- ID for Google.',
                        'rules' => ['nullable', 'string', 'max:255'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Every editable field, keyed by setting, with the section it belongs to added
     * so a writer can store it back where it came from.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        $fields = [];

        foreach (self::sections() as $section) {
            foreach ($section['fields'] as $field) {
                $fields[$field['key']] = [
                    ...$field,
                    'section' => $section['key'],
                    'wire' => self::wireName($field['key']),
                ];
            }
        }

        return $fields;
    }

    public static function field(string $key): ?array
    {
        return self::fields()[$key] ?? null;
    }

    /**
     * What a setting is called on the wire.
     *
     * Setting keys contain dots - `booking.vat_rate` - and Laravel reads a dot in a
     * rule name as a step into a nested array. Left alone, the rule for
     * `settings.booking.vat_rate` would look for `settings[booking][vat_rate]`,
     * which is not what the form sends, so nothing would ever be validated and
     * every rule here would be quietly dead.
     *
     * `__` cannot occur in a key - they are dotted lower-case words by convention -
     * and it gives an error key that reads as itself in a form. The front end is
     * told this name rather than working it out, so the escaping is defined once.
     */
    public static function wireName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /**
     * The declarations keyed by the name they arrive under.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function byWireName(): array
    {
        $byWire = [];

        foreach (self::fields() as $field) {
            $byWire[$field['wire']] = $field;
        }

        return $byWire;
    }

    /**
     * The sections as the screen needs them: each field carrying its own current
     * value, so the form is one payload and one save.
     *
     * @return list<array<string, mixed>>
     */
    public static function forDisplay(): array
    {
        return array_map(fn (array $section): array => [
            'key' => $section['key'],
            'label' => $section['label'],
            'description' => $section['description'],
            'fields' => array_map(fn (array $field): array => [
                ...$field,
                'wire' => self::wireName($field['key']),
                'value' => Setting::value($field['key'], ''),
            ], $section['fields']),
        ], self::sections());
    }

    /**
     * Validation for a submission, built from the declarations above.
     *
     * Only `settings.<key>` where the key is one the screen actually offers, so a
     * crafted request cannot write an arbitrary row into the table.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = ['settings' => ['required', 'array']];

        foreach (self::fields() as $field) {
            $rules["settings.{$field['wire']}"] = $field['rules'] ?? ['nullable'];
        }

        return $rules;
    }
}
