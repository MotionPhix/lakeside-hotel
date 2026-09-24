<?php

namespace App\Cms;

/**
 * One editable value on a content type, and everything the admin needs to render
 * it, validate it and save it back.
 *
 * Fields are declared once and used three times: the list page picks the ones it
 * shows as columns, the form renders the matching control, and the request builds
 * its validation rules from the same declaration. There is no second place to
 * update when a field changes shape, which is the whole reason the CMS is
 * config-driven rather than seventeen hand-written CRUD screens.
 */
final readonly class Field
{
    public const TEXT = 'text';

    public const TEXTAREA = 'textarea';

    public const SLUG = 'slug';

    public const NUMBER = 'number';

    public const MONEY = 'money';

    public const BOOLEAN = 'boolean';

    public const SELECT = 'select';

    public const DATE = 'date';

    public const ICON = 'icon';

    /** A JSON array of strings, edited one per line. */
    public const LIST = 'list';

    /** A JSON map of string to string, edited as key: value lines. */
    public const MAP = 'map';

    /** A belongsTo, edited as a select of the related records. */
    public const RELATION = 'relation';

    /** An uploaded image, handled by the media collections rather than a column. */
    public const MEDIA = 'media';

    /**
     * @param  array<string, string>  $options  value => label, for SELECT and BOOLEAN labelling.
     * @param  list<string>  $rules  extra validation rules appended to the type's own.
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $type = self::TEXT,
        public array $options = [],
        public bool $required = false,
        public ?string $help = null,
        public array $rules = [],
        /** Related model class, for RELATION. */
        public ?string $relation = null,
        /** Column on the related model used as the option label, for RELATION. */
        public string $optionLabel = 'name',
        /** Collection name, for MEDIA. */
        public ?string $collection = null,
    ) {}

    public static function text(string $name, string $label, bool $required = false, ?string $help = null, string $max = '255'): self
    {
        return new self($name, $label, self::TEXT, required: $required, help: $help, rules: ['max:'.$max]);
    }

    public static function area(string $name, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($name, $label, self::TEXTAREA, required: $required, help: $help, rules: ['max:5000']);
    }

    /**
     * A URL-safe identifier. Uniqueness is added by the request, because it needs
     * the table and the record being edited.
     */
    public static function slug(string $name, string $label, bool $required = false): self
    {
        return new self(
            $name,
            $label,
            self::SLUG,
            required: $required,
            help: 'Used in the web address. Leave blank to build one from the name.',
            // Nullable: left blank it is built from the name, and without this
            // the format rule would run against the empty value and reject it.
            rules: ['nullable', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        );
    }

    public static function num(string $name, string $label, bool $required = false, ?string $help = null, bool $nullable = true): self
    {
        return new self(
            $name,
            $label,
            self::NUMBER,
            required: $required,
            help: $help,
            rules: [$nullable ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
        );
    }

    /**
     * A number that may carry decimals but is not money - a distance, a weight.
     */
    public static function decimal(string $name, string $label, bool $required = false): self
    {
        return new self(
            $name,
            $label,
            self::MONEY,
            required: $required,
            rules: ['nullable', 'numeric', 'min:0', 'max:1000000'],
        );
    }

    public static function money(
        string $name,
        string $label,
        bool $required = false,
        bool $nullable = true,
        ?string $help = null,
    ): self {
        return new self(
            $name,
            $label,
            self::MONEY,
            required: $required,
            help: $help,
            rules: [$nullable ? 'nullable' : 'required', 'numeric', 'min:0', 'max:100000000'],
        );
    }

    public static function bool(string $name, string $label, ?string $help = null): self
    {
        return new self(
            $name,
            $label,
            self::BOOLEAN,
            help: $help,
            rules: ['boolean'],
        );
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function pick(string $name, string $label, array $options, bool $required = false, ?string $help = null): self
    {
        return new self(
            $name,
            $label,
            self::SELECT,
            options: $options,
            required: $required,
            help: $help,
            rules: [$required ? 'required' : 'nullable', 'string', 'max:60'],
        );
    }

    public static function date(string $name, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($name, $label, self::DATE, required: $required, help: $help, rules: ['nullable', 'date']);
    }

    public static function icon(string $name, string $label, ?string $help = null): self
    {
        return new self(
            $name,
            $label,
            self::ICON,
            help: $help ?? 'A Lucide icon name, e.g. wifi, waves, utensils.',
            rules: ['nullable', 'string', 'max:60'],
        );
    }

    /**
     * A JSON array of strings.
     */
    public static function list(string $name, string $label, ?string $help = null): self
    {
        return new self(
            $name,
            $label,
            self::LIST,
            help: $help ?? 'One entry per line.',
            rules: ['nullable', 'array'],
        );
    }

    /**
     * A JSON map of string to string.
     */
    public static function map(string $name, string $label, ?string $help = null): self
    {
        return new self(
            $name,
            $label,
            self::MAP,
            help: $help ?? 'One per line, as key: value.',
            rules: ['nullable', 'array'],
        );
    }

    /**
     * @param  class-string  $model
     */
    public static function link(
        string $name,
        string $label,
        string $model,
        string $optionLabel = 'name',
        bool $required = false,
        ?string $help = null,
    ): self {
        return new self(
            $name,
            $label,
            self::RELATION,
            required: $required,
            help: $help,
            rules: [$required ? 'required' : 'nullable'],
            relation: $model,
            optionLabel: $optionLabel,
        );
    }

    public static function media(string $collection, string $label, ?string $help = null): self
    {
        return new self(
            $collection,
            $label,
            self::MEDIA,
            help: $help ?? 'JPG, PNG or WebP. Large images are resized on upload.',
            rules: [],
            collection: $collection,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $this->options,
            'required' => $this->required,
            'help' => $this->help,
            'collection' => $this->collection,
        ];
    }

    /**
     * The rules the form request validates this field with, with the ones that
     * need the resource context left for the request to fill in.
     *
     * @return list<string>
     */
    public function validationRules(): array
    {
        $rules = $this->rules;

        /*
         * `required` is set by the sugar methods so the form can mark the field.
         * Applying the rule here as well means a field cannot be declared
         * required and then quietly accept an empty value, which is exactly the
         * kind of gap that shows up as a database error rather than a message.
         */
        if ($this->required && ! in_array('required', $rules, true)) {
            array_unshift($rules, 'required');
        }

        if ($this->type === self::RELATION && $this->relation !== null) {
            $rules[] = 'integer';
        }

        return $rules;
    }
}
