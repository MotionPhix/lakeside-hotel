<?php

namespace App\Http\Requests\Admin;

use App\Cms\Field;
use App\Cms\ResourceDefinition;
use App\Cms\ResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validation for any content type, built from its schema.
 *
 * Nothing here knows what a room type or a testimonial is: the rules come from
 * the field declarations, which is what keeps a new content type from needing a
 * new request class.
 */
final class ResourceRequest extends FormRequest
{
    public function definition(): ?ResourceDefinition
    {
        return app(ResourceRegistry::class)->find((string) $this->route('resource'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $definition = $this->definition();

        if ($definition === null) {
            return [];
        }

        $record = $this->record($definition);

        $rules = [];

        foreach ($definition->fields as $field) {
            if ($field->type === Field::MEDIA) {
                continue;
            }

            $rules[$field->name] = $this->rulesFor($definition, $field, $record);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $definition = $this->definition();

        if ($definition === null) {
            return [];
        }

        $attributes = [];

        foreach ($definition->fields as $field) {
            $attributes[$field->name] = mb_strtolower($field->label);
        }

        return $attributes;
    }

    /**
     * The values to write, in the shape each column expects.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $definition = $this->definition();

        if ($definition === null) {
            return [];
        }

        $payload = [];

        foreach ($definition->fields as $field) {
            if ($field->type === Field::MEDIA) {
                continue;
            }

            $payload[$field->name] = $this->valueFor($field);
        }

        return $this->withGeneratedSlug($definition, $payload);
    }

    /**
     * The record being edited, if this is an update.
     */
    private function record(ResourceDefinition $definition): mixed
    {
        $id = $this->route('id');

        return $id === null ? null : $definition->model::query()->find($id);
    }

    /**
     * @return list<mixed>
     */
    private function rulesFor(ResourceDefinition $definition, Field $field, mixed $record): array
    {
        $rules = $field->validationRules();

        if ($field->type === Field::RELATION && $field->relation !== null) {
            $rules[] = Rule::exists((new $field->relation)->getTable(), 'id');
        }

        if ($field->type === Field::SLUG) {
            $rules[] = Rule::unique((new $definition->model)->getTable(), $field->name)
                ->ignore($record?->getKey());
        }

        return $rules;
    }

    private function valueFor(Field $field): mixed
    {
        $value = $this->input($field->name);

        return match ($field->type) {
            Field::BOOLEAN => $this->boolean($field->name),
            Field::LIST => $this->toList($value),
            Field::MAP => $this->toMap($value),
            Field::RELATION => $value === null || $value === '' ? null : (int) $value,
            Field::DATE => $value === '' ? null : $value,
            Field::NUMBER, Field::MONEY => $value === '' ? null : $value,
            default => is_string($value) ? trim($value) ?: null : $value,
        };
    }

    /**
     * A slug left blank is built from whatever the record is named, so the hotel
     * never has to invent a web address to add a page.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withGeneratedSlug(ResourceDefinition $definition, array $payload): array
    {
        if (! array_key_exists('slug', $payload) || filled($payload['slug'])) {
            return $payload;
        }

        foreach (['name', 'title', 'code'] as $source) {
            if (filled($payload[$source] ?? null)) {
                $payload['slug'] = Str::slug((string) $payload[$source]);

                return $payload;
            }
        }

        // These columns are not nullable, so a record with neither a slug nor a
        // name to build one from would fail at the database. Say so on the field
        // instead, where it can be acted on.
        throw ValidationException::withMessages([
            'slug' => __('Enter a web address, or a name to build one from.'),
        ]);
    }

    /**
     * Accepts either the array the admin sends or newline-separated text, so the
     * same field works from the dashboard and from a seeded import.
     *
     * @return list<string>|null
     */
    private function toList(mixed $value): ?array
    {
        if (is_array($value)) {
            return array_values(array_filter(
                array_map(fn (mixed $item): string => trim((string) $item), $value),
                fn (string $item): bool => $item !== '',
            ));
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: []),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function toMap(mixed $value): ?array
    {
        $pairs = [];

        if (is_array($value)) {
            $pairs = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $value) ?: [] as $line) {
                if (! str_contains($line, ':')) {
                    continue;
                }

                [$key, $rest] = explode(':', $line, 2);
                $pairs[trim($key)] = trim($rest);
            }
        }

        if ($pairs === []) {
            return null;
        }

        $map = [];

        foreach ($pairs as $key => $item) {
            // Section settings such as `limit` are read as numbers elsewhere, and
            // storing them back as strings would quietly change their type.
            $map[(string) $key] = is_numeric($item) ? (int) $item : $item;
        }

        return $map;
    }
}
