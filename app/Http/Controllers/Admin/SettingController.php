<?php

namespace App\Http\Controllers\Admin;

use App\Cms\SettingSchema;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use App\Support\Tax;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The hotel's own settings: what it charges, how it is reached, what it says about
 * itself.
 *
 * There is one screen rather than a page per setting, and it is generated from
 * {@see SettingSchema} rather than hand-written. That is the same arrangement as
 * the content types: the declaration is the source of truth for the form, the
 * validation and the write, so a setting cannot be editable in one place and
 * validated in another, and a value that is not declared cannot be written at all.
 */
class SettingController extends Controller
{
    /**
     * Everything the dashboard may edit, grouped into sections.
     */
    public function index(): Response
    {
        return Inertia::render('admin/system/index', [
            'sections' => SettingSchema::forDisplay(),
        ]);
    }

    /**
     * Save a whole screen's worth of settings at once.
     *
     * One submission per screen rather than one per field: the rates are read
     * together when a price is worked out, so an edit that changed VAT without its
     * companion would leave the site briefly quoting a rate nobody intended.
     */
    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        $submitted = $request->validated('settings');
        $fields = SettingSchema::byWireName();

        /* Tax is the one setting whose change is invisible until a page is
           reloaded, so it is worth saying out loud that quoted prices have moved. */
        $taxChanged = false;

        foreach ($submitted as $wire => $value) {
            /* The submitted names are the escaped ones; anything else never had a
               rule and so never reached here. */
            $field = $fields[$wire] ?? null;

            if ($field === null) {
                continue;
            }

            $stored = is_bool($value)
                ? ($value ? '1' : '0')
                : (string) $value;

            Setting::store(
                $field['key'],
                $stored,
                $field['section'],
                self::typeFor($field['control']),
            );

            if (in_array($field['key'], [
                Tax::VAT_SETTING,
                Tax::LEVY_SETTING,
                Tax::SHOW_SEPARATELY_SETTING,
            ], true)) {
                $taxChanged = true;
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $taxChanged
                ? __('Settings saved. Prices across the site now show the tax you set.')
                : __('Settings saved.'),
        ]);

        return back();
    }

    /**
     * The type stored alongside the value, so the settings table stays readable
     * for anyone looking at it directly rather than through the dashboard.
     */
    private static function typeFor(string $control): string
    {
        return match ($control) {
            SettingSchema::TOGGLE => 'boolean',
            SettingSchema::NUMBER => 'decimal',
            SettingSchema::EMAIL => 'email',
            SettingSchema::TIME => 'time',
            SettingSchema::TEXTAREA => 'text',
            default => 'string',
        };
    }
}
