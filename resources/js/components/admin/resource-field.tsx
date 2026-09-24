import { DatePicker } from '@/components/date-picker';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { CmsField, CmsRelationOption, CmsValue } from '@/types';

/** shadcn's Select reserves the empty string, so "none" needs a real value. */
const NONE = '__none__';

/**
 * Renders one editable field from its schema.
 *
 * Every control the CMS offers lives here, so a new content type is written by
 * describing its fields rather than by building a form. A field type the renderer
 * does not recognise falls back to a plain text input rather than rendering
 * nothing, so a half-finished schema still shows its data and stays editable.
 */
export function ResourceField({
    field,
    value,
    error,
    options,
    onChange,
}: {
    field: CmsField;
    value: CmsValue;
    error?: string;
    /** Choices for a relation field. */
    options?: CmsRelationOption[];
    onChange: (value: CmsValue) => void;
}) {
    const id = `field-${field.name}`;

    return (
        <div className="grid gap-2">
            {field.type !== 'boolean' && (
                <Label htmlFor={id}>
                    {field.label}
                    {field.required && (
                        <span className="text-muted-foreground"> *</span>
                    )}
                </Label>
            )}

            {renderControl({ field, value, id, options, onChange, error })}

            {field.help && (
                <p className="text-xs text-muted-foreground">{field.help}</p>
            )}

            <InputError message={error} />
        </div>
    );
}

function renderControl({
    field,
    value,
    id,
    options,
    onChange,
    error,
}: {
    field: CmsField;
    value: CmsValue;
    id: string;
    options?: CmsRelationOption[];
    onChange: (value: CmsValue) => void;
    error?: string;
}) {
    // Only scalars belong in a text control; a list or a map has its own, and
    // stringifying one here would render "[object Object]".
    const asString =
        typeof value === 'string' || typeof value === 'number'
            ? String(value)
            : '';

    switch (field.type) {
        case 'boolean':
            return (
                <label className="flex items-start gap-3" htmlFor={id}>
                    <Checkbox
                        id={id}
                        checked={value === true}
                        onCheckedChange={(checked) =>
                            onChange(checked === true)
                        }
                        aria-invalid={Boolean(error)}
                    />
                    <span>
                        <span className="block text-sm font-medium">
                            {field.label}
                        </span>
                        {field.help && (
                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                {field.help}
                            </span>
                        )}
                    </span>
                </label>
            );

        case 'textarea':
            return (
                <Textarea
                    id={id}
                    rows={5}
                    value={asString}
                    onChange={(event) => onChange(event.target.value)}
                    aria-invalid={Boolean(error)}
                />
            );

        case 'list':
            return (
                <Textarea
                    id={id}
                    rows={4}
                    value={toLines(value)}
                    onChange={(event) =>
                        onChange(
                            event.target.value.split('\n').map((line) => line),
                        )
                    }
                    aria-invalid={Boolean(error)}
                />
            );

        case 'map':
            return (
                <Textarea
                    id={id}
                    rows={4}
                    value={toPairs(value)}
                    onChange={(event) =>
                        onChange(fromPairs(event.target.value))
                    }
                    aria-invalid={Boolean(error)}
                />
            );

        case 'select':
            return (
                <Select
                    value={asString === '' ? NONE : asString}
                    onValueChange={(next) =>
                        onChange(next === NONE ? '' : next)
                    }
                >
                    <SelectTrigger
                        id={id}
                        className="w-full"
                        aria-invalid={Boolean(error)}
                    >
                        <SelectValue placeholder="Choose one" />
                    </SelectTrigger>
                    <SelectContent>
                        {!field.required && (
                            <SelectItem value={NONE}>Not set</SelectItem>
                        )}
                        {Object.entries(field.options).map(
                            ([option, label]) => (
                                <SelectItem key={option} value={option}>
                                    {label}
                                </SelectItem>
                            ),
                        )}
                    </SelectContent>
                </Select>
            );

        case 'relation':
            return (
                <Select
                    value={asString === '' ? NONE : asString}
                    onValueChange={(next) =>
                        onChange(next === NONE ? null : next)
                    }
                >
                    <SelectTrigger
                        id={id}
                        className="w-full"
                        aria-invalid={Boolean(error)}
                    >
                        <SelectValue placeholder="Choose one" />
                    </SelectTrigger>
                    <SelectContent>
                        {!field.required && (
                            <SelectItem value={NONE}>None</SelectItem>
                        )}
                        {(options ?? []).map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            );

        case 'date':
            return (
                <DatePicker
                    id={id}
                    value={asString}
                    onChange={onChange}
                    clearable={!field.required}
                    placeholder="Not set"
                />
            );

        case 'number':
        case 'money':
            return (
                <Input
                    id={id}
                    type="number"
                    step="0.01"
                    value={asString}
                    onChange={(event) =>
                        onChange(
                            event.target.value === ''
                                ? null
                                : event.target.value,
                        )
                    }
                    aria-invalid={Boolean(error)}
                />
            );

        case 'slug':
        case 'icon':
        case 'text':
        default:
            return (
                <Input
                    id={id}
                    value={asString}
                    onChange={(event) => onChange(event.target.value)}
                    aria-invalid={Boolean(error)}
                />
            );
    }
}

/** A JSON array of strings, as one line per entry. */
function toLines(value: CmsValue): string {
    return Array.isArray(value) ? value.join('\n') : '';
}

/**
 * A JSON map, as `key: value` lines.
 *
 * A line without a colon is skipped rather than guessed at, so a half-typed
 * entry cannot silently become a key with no value.
 */
function toPairs(value: CmsValue): string {
    if (value === null || Array.isArray(value) || typeof value !== 'object') {
        return '';
    }

    return Object.entries(value)
        .map(([key, item]) => `${key}: ${item}`)
        .join('\n');
}

function fromPairs(text: string): Record<string, string> {
    const map: Record<string, string> = {};

    for (const line of text.split('\n')) {
        const at = line.indexOf(':');

        if (at === -1) {
            continue;
        }

        const key = line.slice(0, at).trim();

        if (key !== '') {
            map[key] = line.slice(at + 1).trim();
        }
    }

    return map;
}
