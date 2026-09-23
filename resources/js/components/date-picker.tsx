import { format, isValid, parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import type { DateRange, Matcher } from 'react-day-picker';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

/**
 * Date pickers composed from the shadcn Popover and Calendar, following the
 * shadcn date picker pattern.
 *
 * They speak ISO `yyyy-MM-dd` strings in and out rather than Date objects, so
 * they drop straight into an Inertia `useForm` without any conversion at the
 * call site. Parsing and formatting both go through date-fns using the local
 * calendar day, which avoids the off-by-one that `toISOString` introduces in
 * positive UTC offsets.
 */

const ISO_FORMAT = 'yyyy-MM-dd';

/**
 * Reads an ISO string as a local date, returning undefined for anything empty or
 * unparseable so the calendar simply shows no selection.
 */
function toDate(value: string | undefined): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parseISO(value);

    return isValid(parsed) ? parsed : undefined;
}

function toIso(date: Date | undefined): string {
    return date ? format(date, ISO_FORMAT) : '';
}

/**
 * The trigger styling, matching the Input component so a picker sits level with
 * the text fields either side of it. The height comes from the control-height
 * rule in app.css rather than being set here.
 */
const triggerClass =
    'border-input w-full justify-start gap-2 rounded-md border bg-transparent px-3 text-left text-sm font-normal shadow-xs hover:bg-transparent';

export function DatePicker({
    value,
    onChange,
    id,
    placeholder = 'Pick a date',
    displayFormat = 'd MMM yyyy',
    disabled,
    min,
    max,
    clearable = false,
    className,
    'aria-invalid': ariaInvalid,
}: {
    /** ISO `yyyy-MM-dd`, or an empty string for no selection. */
    value: string;
    onChange: (value: string) => void;
    id?: string;
    placeholder?: string;
    displayFormat?: string;
    disabled?: boolean;
    /** ISO `yyyy-MM-dd` lower bound, inclusive. */
    min?: string;
    /** ISO `yyyy-MM-dd` upper bound, inclusive. */
    max?: string;
    clearable?: boolean;
    className?: string;
    'aria-invalid'?: boolean;
}) {
    const [open, setOpen] = useState(false);
    const selected = toDate(value);

    const before = toDate(min);
    const after = toDate(max);

    // react-day-picker takes a list of matchers, and a bare object carrying an
    // undefined bound does not satisfy its Matcher type.
    const disabledMatchers: Matcher[] = [];

    if (before) {
        disabledMatchers.push({ before });
    }

    if (after) {
        disabledMatchers.push({ after });
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-invalid={ariaInvalid}
                    data-empty={!selected}
                    className={cn(
                        triggerClass,
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0 opacity-70" />
                    {selected ? (
                        format(selected, displayFormat)
                    ) : (
                        <span>{placeholder}</span>
                    )}
                </Button>
            </PopoverTrigger>

            <PopoverContent
                className="w-auto p-0"
                align="start"
                collisionPadding={12}
            >
                <Calendar
                    mode="single"
                    selected={selected}
                    defaultMonth={selected ?? before}
                    disabled={
                        disabledMatchers.length > 0
                            ? disabledMatchers
                            : undefined
                    }
                    autoFocus
                    onSelect={(date) => {
                        onChange(toIso(date));
                        setOpen(false);
                    }}
                />

                {clearable && selected && (
                    <div className="border-t p-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="w-full"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Clear date
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}

export function DateRangePicker({
    from,
    to,
    onChange,
    id,
    placeholder = 'Pick your dates',
    displayFormat = 'd MMM yyyy',
    disabled,
    min,
    numberOfMonths = 2,
    className,
    'aria-invalid': ariaInvalid,
}: {
    /** ISO `yyyy-MM-dd`, or an empty string. */
    from: string;
    to: string;
    onChange: (range: { from: string; to: string }) => void;
    id?: string;
    placeholder?: string;
    displayFormat?: string;
    disabled?: boolean;
    /** ISO `yyyy-MM-dd` lower bound; nights before it cannot be selected. */
    min?: string;
    numberOfMonths?: number;
    className?: string;
    'aria-invalid'?: boolean;
}) {
    const [open, setOpen] = useState(false);

    const start = toDate(from);
    const end = toDate(to);
    const before = toDate(min);

    /*
     * react-day-picker sets both ends on the very first click, so a "range" whose
     * ends are the same day is really a half-finished selection rather than a one
     * night stay. Treating it as incomplete keeps the trigger honest and stops the
     * popover closing after a single click.
     */
    const hasStay = Boolean(start && end && end.getTime() !== start.getTime());

    const selected: DateRange | undefined = start
        ? { from: start, to: hasStay ? end : undefined }
        : undefined;

    const label = start
        ? hasStay
            ? `${format(start, displayFormat)} – ${format(end as Date, displayFormat)}`
            : format(start, displayFormat)
        : null;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-invalid={ariaInvalid}
                    data-empty={!label}
                    className={cn(
                        triggerClass,
                        'data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0 opacity-70" />
                    {label ?? <span>{placeholder}</span>}
                </Button>
            </PopoverTrigger>

            <PopoverContent
                className="w-auto p-0"
                align="start"
                collisionPadding={12}
            >
                <Calendar
                    mode="range"
                    selected={selected}
                    defaultMonth={start ?? before}
                    disabled={before ? { before } : undefined}
                    numberOfMonths={numberOfMonths}
                    autoFocus
                    onSelect={(range) => {
                        const from = range?.from;
                        const to = range?.to;

                        onChange({
                            from: toIso(from),
                            to: toIso(to),
                        });

                        // Close only once a real stay is chosen, not after the
                        // first click that react-day-picker treats as both ends.
                        if (from && to && to.getTime() !== from.getTime()) {
                            setOpen(false);
                        }
                    }}
                />

                {start && (
                    <div className="flex items-center justify-between gap-2 border-t p-2">
                        <p className="px-2 text-xs text-muted-foreground">
                            {hasStay && end
                                ? `${Math.round((end.getTime() - start.getTime()) / 86_400_000)} nights`
                                : 'Select a check out date'}
                        </p>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                onChange({ from: '', to: '' });
                                setOpen(false);
                            }}
                        >
                            Clear
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}
