import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Spinner } from '@/components/ui/spinner';
import system from '@/routes/admin/system';
import type { SettingField, SettingSection } from '@/types';

/** What a checkbox holds: a real boolean, so switching one off still submits it. */
type Values = Record<string, string | boolean>;

/**
 * One group of settings, saved on its own.
 *
 * Sections are saved separately so that a mistake in the contact details cannot
 * stop the rates being corrected, and so each save is a small, legible change.
 *
 * Every field in the section is always submitted, including the toggles that are
 * off. An unchecked checkbox posts nothing at all, so a form that sent only what
 * changed would never be able to switch a setting back off.
 */
export function SettingSection({ section }: { section: SettingSection }) {
    const form = useForm<{ settings: Values }>({
        settings: Object.fromEntries(
            section.fields.map((field) => [field.wire, initial(field)]),
        ),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.patch(system.update.url(), { preserveScroll: true });
    };

    return (
        <Card>
            <CardContent>
                <form onSubmit={submit} className="flex flex-col gap-6">
                    <header className="space-y-1">
                        <h3 className="text-base font-medium">
                            {section.label}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            {section.description}
                        </p>
                    </header>

                    <div className="flex flex-col gap-5">
                        {section.fields.map((field) => (
                            <Field
                                key={field.key}
                                field={field}
                                value={form.data.settings[field.wire]}
                                error={
                                    form.errors[`settings.${field.wire}`]
                                }
                                onChange={(value) =>
                                    form.setData('settings', {
                                        ...form.data.settings,
                                        [field.wire]: value,
                                    })
                                }
                            />
                        ))}
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Save {section.label.toLowerCase()}
                        </Button>

                        {form.recentlySuccessful && (
                            <p className="text-sm text-muted-foreground">
                                Saved.
                            </p>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}

/**
 * One setting, as the control its declaration asks for.
 *
 * A toggle is laid out as a row with the switch on the right, because that is
 * what it is; everything else is a labelled input, because that is what it is.
 */
function Field({
    field,
    value,
    error,
    onChange,
}: {
    field: SettingField;
    value: string | boolean;
    error?: string;
    onChange: (value: string | boolean) => void;
}) {
    const id = `setting-${field.wire}`;

    if (field.control === 'toggle') {
        return (
            <div className="flex items-start justify-between gap-6 rounded-lg border border-sidebar-border/70 p-4">
                <div className="space-y-1">
                    <Label htmlFor={id}>{field.label}</Label>
                    {field.help && (
                        <p className="text-sm text-muted-foreground">
                            {field.help}
                        </p>
                    )}
                    <InputError message={error} />
                </div>

                <Checkbox
                    id={id}
                    checked={value === true}
                    onCheckedChange={(checked) => onChange(checked === true)}
                    className="mt-0.5"
                />
            </div>
        );
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{field.label}</Label>

            {field.control === 'textarea' ? (
                <Textarea
                    id={id}
                    value={String(value)}
                    onChange={(event) => onChange(event.target.value)}
                    rows={3}
                />
            ) : (
                <div className="flex items-center gap-2">
                    <Input
                        id={id}
                        type={inputType(field)}
                        step={field.step}
                        value={String(value)}
                        onChange={(event) => onChange(event.target.value)}
                    />
                    {field.suffix && (
                        <span className="text-sm text-muted-foreground">
                            {field.suffix}
                        </span>
                    )}
                </div>
            )}

            {field.help && (
                <p className="text-sm text-muted-foreground">{field.help}</p>
            )}

            <InputError message={error} />
        </div>
    );
}

function inputType(field: SettingField): string {
    switch (field.control) {
        case 'number':
            return 'number';
        case 'email':
            return 'email';
        case 'time':
            return 'time';
        default:
            return 'text';
    }
}

/**
 * A toggle starts from the stored `1` or `0` rather than from a truthy string, so
 * that `0` reads as off and an empty value does too.
 */
function initial(field: SettingField): string | boolean {
    if (field.control === 'toggle') {
        return field.value === '1';
    }

    return field.value;
}
