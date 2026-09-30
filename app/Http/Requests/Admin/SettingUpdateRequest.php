<?php

namespace App\Http\Requests\Admin;

use App\Cms\SettingSchema;
use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the settings screen, built from its declarations.
 *
 * The rules come from {@see SettingSchema} rather than being written out here, so
 * a setting is validated exactly as it is described to the person editing it.
 * Anything not declared has no rule and is therefore never stored - which is what
 * stops a crafted request writing a row of its own into the settings table.
 */
final class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ManageSystem->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return SettingSchema::rules();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (SettingSchema::fields() as $field) {
            $attributes["settings.{$field['wire']}"] = strtolower($field['label']);
        }

        return $attributes;
    }
}
