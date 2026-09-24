<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Field;
use App\Cms\ResourceDefinition;
use App\Cms\ResourceRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResourceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One controller for every content type.
 *
 * It never names a model. It asks the registry for a definition and works from
 * that: which fields exist, which of them the list shows, how the records are
 * ordered, which boolean publishes them, which permission is required. Adding a
 * content type is therefore a definition and nothing else.
 */
class ResourceController extends Controller
{
    public function __construct(private readonly ResourceRegistry $registry) {}

    /**
     * The hub: everything that can be edited, grouped, with a count of each.
     */
    public function hub(Request $request): Response
    {
        $groups = $this->registry->groupedForHub();
        $user = $request->user();

        // Show only what this account could actually open, so nobody is offered a
        // link that comes back 403.
        $groups = array_values(array_filter(array_map(
            fn (array $group): array => [
                'group' => $group['group'],
                'resources' => array_values(array_filter(
                    $group['resources'],
                    fn (array $resource): bool => $user?->hasPermission(
                        $this->registry->find($resource['key'])?->permission,
                    ) ?? false,
                )),
            ],
            $groups,
        ), fn (array $group): bool => $group['resources'] !== []));

        abort_if($groups === [], 403);

        return Inertia::render('admin/content/hub', ['groups' => $groups]);
    }

    /**
     * The list for one content type.
     */
    public function index(Request $request, string $resource): Response
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $search = $request->string('search')->trim()->value();

        $query = $definition->model::query()
            ->when(
                $search !== '' && $definition->searchable !== [],
                fn (Builder $builder) => $builder->where(
                    fn (Builder $inner) => array_walk(
                        $definition->searchable,
                        fn (string $column) => $inner->orWhere($column, 'like', "%{$search}%"),
                    ),
                ),
            );

        $query = $definition->sortable
            ? $query->orderBy($definition->sortColumn)->orderBy('id')
            : $query->orderByDesc('id');

        $records = $query->paginate(25)->withQueryString();

        // Media is read from the models before `through` replaces them with the
        // shaped rows, otherwise it is handed a collection of arrays.
        $media = $this->mediaFor($definition, $records->getCollection());

        return Inertia::render('admin/content/index', [
            'resource' => $definition->toArray(),
            'rows' => $records->through(fn (Model $record): array => $this->toRow($definition, $record)),
            'search' => $search,
            'media' => $media,
        ]);
    }

    /**
     * The create form.
     */
    public function create(Request $request, string $resource): Response
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        return Inertia::render('admin/content/form', [
            'resource' => $definition->toArray(),
            'record' => null,
            'values' => $this->blankValues($definition),
            'relations' => $this->relationOptions($definition),
            'media' => [],
        ]);
    }

    public function store(ResourceRequest $request, string $resource): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $record = $definition->model::query()->create(
            $request->payload() + $this->startingPosition($definition),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name created.', ['name' => $definition->singular]),
        ]);

        return to_route('admin.content.edit', ['resource' => $resource, 'id' => $record->getKey()]);
    }

    /**
     * The edit form, with any media already attached.
     */
    public function edit(Request $request, string $resource, int $id): Response
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $record = $this->record($definition, $id);

        return Inertia::render('admin/content/form', [
            'resource' => $definition->toArray(),
            'record' => $this->toRow($definition, $record),
            'values' => $this->valuesOf($definition, $record),
            'relations' => $this->relationOptions($definition),
            'media' => $this->mediaFor($definition, [$record]),
        ]);
    }

    public function update(ResourceRequest $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $this->record($definition, $id)->update($request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name saved.', ['name' => $definition->singular]),
        ]);

        return back();
    }

    public function destroy(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $this->record($definition, $id)->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __(':name deleted.', ['name' => $definition->singular]),
        ]);

        return to_route('admin.content.index', ['resource' => $resource]);
    }

    /**
     * Move a record one place up or down the list.
     *
     * Positions are rewritten as a clean 1..n after every move rather than the two
     * rows swapping values, because duplicate sort_order values accumulate and
     * eventually make the order impossible to reason about.
     */
    public function move(Request $request, string $resource, int $id, string $direction): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        abort_unless($definition->sortable, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $record = $this->record($definition, $id);

        $ordered = $definition->model::query()
            ->orderBy($definition->sortColumn)
            ->orderBy('id')
            ->get()
            ->all();

        $index = null;

        foreach ($ordered as $position => $item) {
            if ($item->getKey() === $record->getKey()) {
                $index = $position;
                break;
            }
        }

        $target = $direction === 'up' ? $index - 1 : $index + 1;

        // Already at the end of the list: nothing to do, and no error either -
        // the button is simply at the edge.
        if ($index === null || $target < 0 || $target >= count($ordered)) {
            return back();
        }

        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        foreach ($ordered as $position => $item) {
            $item->forceFill([$definition->sortColumn => $position + 1])->save();
        }

        return back();
    }

    /**
     * Flip a record between published and hidden.
     */
    public function toggle(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $column = $definition->publishColumn;

        abort_if($column === null, 404);

        $record = $this->record($definition, $id);
        $record->forceFill([$column => ! $record->{$column}])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $record->{$column}
                ? __(':name published.', ['name' => $definition->singular])
                : __(':name hidden.', ['name' => $definition->singular]),
        ]);

        return back();
    }

    /**
     * Attach an upload to one of the record's media collections.
     *
     * Single-file collections are cleared first, so replacing a cover photo does
     * not leave the old one on disk competing with it.
     */
    public function storeMedia(Request $request, string $resource, int $id): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $validated = $request->validate([
            'collection' => ['required', 'string'],
            'file' => ['required', 'file', 'image', 'max:8192'],
        ]);

        $collection = $validated['collection'];

        abort_unless(array_key_exists($collection, $definition->media), 404);

        $record = $this->record($definition, $id);

        if (! $definition->media[$collection]['multiple']) {
            $record->clearMediaCollection($collection);
        }

        $record->addMediaFromRequest('file')->toMediaCollection($collection);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Image uploaded.'),
        ]);

        return back();
    }

    public function destroyMedia(Request $request, string $resource, int $id, int $mediaId): RedirectResponse
    {
        $definition = $this->definition($resource);
        $this->authorizeResource($request, $definition);

        $media = $this->record($definition, $id)->media()->find($mediaId);

        abort_if($media === null, 404);

        $media->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Image removed.')]);

        return back();
    }

    private function definition(string $resource): ResourceDefinition
    {
        $definition = $this->registry->find($resource);

        abort_if($definition === null, 404);

        return $definition;
    }

    /**
     * The permission travels with the definition rather than the route, because
     * one route serves seventeen content types with different owners.
     */
    private function authorizeResource(Request $request, ResourceDefinition $definition): void
    {
        abort_unless($request->user()?->hasPermission($definition->permission) ?? false, 403);
    }

    private function record(ResourceDefinition $definition, int $id): Model
    {
        $record = $definition->model::query()->find($id);

        abort_if($record === null, 404);

        return $record;
    }

    /**
     * Where a new record goes: the end of the list.
     *
     * @return array<string, mixed>
     */
    private function startingPosition(ResourceDefinition $definition): array
    {
        if (! $definition->sortable) {
            return [];
        }

        $column = $definition->sortColumn;

        return [$column => (int) $definition->model::query()->max($column) + 1];
    }

    /**
     * A list row, with values shaped for display.
     *
     * @return array<string, mixed>
     */
    private function toRow(ResourceDefinition $definition, Model $record): array
    {
        return [
            'id' => $record->getKey(),
            'title' => (string) ($record->{$definition->titleColumn} ?? $record->getKey()),
            'published' => $definition->isPublishable()
                ? (bool) $record->{$definition->publishColumn}
                : null,
            'values' => collect($definition->columns)
                ->mapWithKeys(fn (string $column): array => [
                    $column => $this->display($definition, $record, $column),
                ])
                ->all(),
        ];
    }

    /**
     * One cell, formatted the way its field type should read.
     */
    private function display(ResourceDefinition $definition, Model $record, string $column): string
    {
        $field = $definition->field($column);
        $value = $record->{$column};

        if ($field instanceof Field && $field->type === Field::RELATION) {
            $name = Str::singular($column) === $column ? $column : Str::camel(Str::beforeLast($column, '_id'));

            return (string) ($record->{$name}?->{$field->optionLabel} ?? '—');
        }

        return match (true) {
            $value === null, $value === '' => '—',
            // An enum cast is asked for its label first: a room's status is an
            // enum, and looking that up in a select's option map would be a
            // type error rather than a miss.
            $value instanceof \BackedEnum => $value->label(),
            is_bool($value) => $value ? 'Yes' : 'No',
            $field?->type === Field::BOOLEAN => $value ? 'Yes' : 'No',
            $field?->type === Field::MONEY => number_format((float) $value, 2),
            $field?->type === Field::SELECT && is_scalar($value) && isset($field->options[$value]) => $field->options[$value],
            $field?->type === Field::LIST => implode(', ', (array) $value),
            $value instanceof \DateTimeInterface => $value->format('j M Y'),
            default => Str::limit((string) $value, 60),
        };
    }

    /**
     * Every editable value, ready to fill the form.
     *
     * @return array<string, mixed>
     */
    private function valuesOf(ResourceDefinition $definition, Model $record): array
    {
        $values = [];

        foreach ($definition->fields as $field) {
            if ($field->type === Field::MEDIA) {
                continue;
            }

            $value = $record->{$field->name};

            $values[$field->name] = match (true) {
                $value instanceof \BackedEnum => $value->value,
                $field->type === Field::DATE && $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                $field->type === Field::LIST => array_values((array) $value),
                $field->type === Field::MAP => (array) $value,
                $field->type === Field::BOOLEAN => (bool) $value,
                default => $value,
            };
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function blankValues(ResourceDefinition $definition): array
    {
        $values = [];

        foreach ($definition->fields as $field) {
            if ($field->type === Field::MEDIA) {
                continue;
            }

            $values[$field->name] = match ($field->type) {
                Field::BOOLEAN => false,
                Field::LIST => [],
                Field::MAP => [],
                default => null,
            };
        }

        return $values;
    }

    /**
     * The choices for every relation field, so the form can offer a select rather
     * than asking the hotel to remember a room type's id.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function relationOptions(ResourceDefinition $definition): array
    {
        $options = [];

        foreach ($definition->fields as $field) {
            if ($field->type !== Field::RELATION || $field->relation === null) {
                continue;
            }

            $options[$field->name] = $field->relation::query()
                ->orderBy($field->optionLabel)
                ->get(['id', $field->optionLabel])
                ->map(fn (Model $related): array => [
                    'value' => (string) $related->getKey(),
                    'label' => (string) $related->{$field->optionLabel},
                ])
                ->all();
        }

        return $options;
    }

    /**
     * Media already attached, keyed by collection.
     *
     * @param  iterable<Model>  $records
     * @return array<int, array<string, list<array<string, mixed>>>>
     */
    private function mediaFor(ResourceDefinition $definition, iterable $records): array
    {
        if ($definition->media === []) {
            return [];
        }

        $media = [];

        foreach ($records as $record) {
            if (! method_exists($record, 'getMedia')) {
                continue;
            }

            $collections = [];

            foreach (array_keys($definition->media) as $collection) {
                $collections[$collection] = $record->getMedia($collection)
                    ->map(fn ($item): array => [
                        'id' => $item->getKey(),
                        'url' => $item->getUrl(),
                        'thumb' => $item->hasGeneratedConversion('card')
                            ? $item->getUrl('card')
                            : $item->getUrl(),
                        'name' => $item->file_name,
                    ])
                    ->all();
            }

            $media[$record->getKey()] = $collections;
        }

        return $media;
    }
}
