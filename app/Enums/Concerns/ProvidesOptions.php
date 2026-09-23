<?php

namespace App\Enums\Concerns;

/**
 * Shared helpers for turning a backed enum into data a form can render and a
 * filter can validate against.
 */
trait ProvidesOptions
{
    /**
     * The enum as value/label pairs, ready for a select or a filter.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_values(array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        ));
    }

    /**
     * Every backed value, handy for a `Rule::in(...)` validation rule.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_values(array_map(fn (self $case): string => $case->value, self::cases()));
    }
}
