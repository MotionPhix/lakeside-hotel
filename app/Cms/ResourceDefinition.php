<?php

namespace App\Cms;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Model;

/**
 * One configurable content type: what it is called, who may touch it, which of
 * its fields are editable, how its list is laid out, and how it is ordered and
 * published.
 *
 * Everything the generic admin needs is here. The controller, the validation and
 * the React screens all read this and nothing else, which is what lets a new
 * content type be added by writing one of these rather than a new controller, a
 * new request, a new page and a new form.
 */
final readonly class ResourceDefinition
{
    /**
     * @param  class-string<Model>  $model
     * @param  list<string>  $columns  field names shown in the list, in order
     * @param  list<Field>  $fields  every editable field, in form order
     * @param  list<string>  $searchable  columns the search box looks in
     * @param  array<string, array{label: string, multiple: bool}>  $media
     */
    public function __construct(
        public string $key,
        public string $model,
        public string $label,
        public string $singular,
        public string $group,
        public Permission $permission,
        public string $description,
        public array $columns,
        public array $fields,
        public array $searchable = [],
        public bool $sortable = true,
        public string $sortColumn = 'sort_order',
        /**
         * The boolean the publish toggle writes. Null where publication is not a
         * boolean: a room is in or out of service through its status instead.
         */
        public ?string $publishColumn = 'is_active',
        public array $media = [],
        /** Column used to name a record in a relation select. */
        public string $titleColumn = 'name',
    ) {}

    public function isPublishable(): bool
    {
        return $this->publishColumn !== null;
    }

    public function field(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * The fields that live in columns rather than in the media library.
     *
     * @return list<string>
     */
    public function writableColumns(): array
    {
        $columns = [];

        foreach ($this->fields as $field) {
            if ($field->type !== Field::MEDIA) {
                $columns[] = $field->name;
            }
        }

        return $columns;
    }

    /**
     * The rows the generic admin renders.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'singular' => $this->singular,
            'group' => $this->group,
            'description' => $this->description,
            'permission' => $this->permission->value,
            'columns' => $this->columns,
            'fields' => array_map(fn (Field $field): array => $field->toArray(), $this->fields),
            'searchable' => $this->searchable,
            'sortable' => $this->sortable,
            'publishable' => $this->isPublishable(),
            'publish_column' => $this->publishColumn,
            'media' => $this->media,
        ];
    }
}
