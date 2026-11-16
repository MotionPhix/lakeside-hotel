import { useForm } from '@inertiajs/react';
import { SeoHead } from '@/components/public/seo-head';
import { CalendarDays, Check, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { HouseRules } from '@/components/public/house-rules';
import InputError from '@/components/input-error';
import { BookingTotals } from '@/components/public/booking-totals';
import { PageHero } from '@/components/public/page-hero';
import {
    imageBoxes,
    ResponsiveImage,
} from '@/components/public/responsive-image';
import { Section } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney, formatNight } from '@/lib/format';
import { cn, quantitiesBetween } from '@/lib/utils';
import bookingRoutes from '@/routes/site/booking';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    BookingContext,
    BookingExtraChoice,
    BookingExtraOption,
    BookingOffer,
    BookingSearch,
} from '@/types';

type Props = {
    search: BookingSearch;
    offer: BookingOffer;
    /** What may be added to the stay, priced and bounded for the form. */
    extras: BookingExtraOption[];
    booking: BookingContext;
};

/**
 * Step two: who is coming, what they would like added, and how they would like to
 * pay.
 *
 * The nights and the room come from the previous step and travel with the form,
 * so the price on the right is the price the server reserves against rather than
 * anything the browser kept hold of. That holds for the extras too: nothing here is
 * priced by the browser on trust - the catalogue and the bounds come from the
 * server, and it prices the stay again before taking it.
 */
export default function BookingCreate({
    search,
    offer,
    extras,
    booking,
}: Props) {
    const { data, setData, post, processing, errors } = useForm({
        room_type: search.room_type || offer.slug,
        check_in: search.check_in,
        check_out: search.check_out,
        adults: String(search.adults),
        children: String(search.children),

        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        country: '',
        city: '',

        payment_option: booking.payment_options[0]?.value ?? 'pay_at_hotel',

        airport_transfer: false,
        transfer_flight: '',
        transfer_arrival: '',

        /* The extras chosen, in the order they were added. Sent as it stands; the
       server prices it again from the catalogue before it becomes a booking. */
        extras: [] as BookingExtraChoice[],

        special_requests: '',
        coupon_code: '',
        marketing_opt_in: false,
        terms: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        // The summary panel sits alongside a long form, so the guest keeps their
        // place rather than being thrown back to the top to find the error.
        post(bookingRoutes.store.url(), { preserveScroll: true });
    };

    const nights = Object.entries(offer.nightly);

    /**
     * What an extra comes to for a quantity.
     *
     * Worked out the way the reservation works it out: a group price is the price
     * of the group however many come, and a per-person or per-hour price is
     * multiplied. This is the only sum the browser does on money, and it is here so
     * the figure under the form moves as the guest chooses. The server works it out
     * again from the same rule when the booking is taken - what the guest is charged
     * is what the confirmation says.
     */
    const extraSubtotal = (option: BookingExtraOption, quantity: number) =>
        option.price_basis === 'per_group'
            ? Number(option.price)
            : Number(option.price) * Math.max(quantity, 1);

    const extraLabel = (option: BookingExtraOption, quantity: number) =>
        option.price_basis === 'per_group'
            ? `${option.name} (for the group)`
            : `${option.name} × ${quantity}`;

    const addExtra = (option: BookingExtraOption) =>
        setData('extras', [
            ...data.extras,
            { id: option.id, quantity: option.min_quantity },
        ]);

    const removeExtra = (id: number) =>
        setData(
            'extras',
            data.extras.filter((chosen) => chosen.id !== id),
        );

    const setExtraQuantity = (id: number, quantity: number) =>
        setData(
            'extras',
            data.extras.map((chosen) =>
                chosen.id === id ? { ...chosen, quantity } : chosen,
            ),
        );

    /* What the summary lists beneath the room, and what it adds to the total. */
    const extraLines = data.extras.flatMap((chosen) => {
        const option = extras.find((extra) => extra.id === chosen.id);

        return option
            ? [
                  {
                      label: extraLabel(option, chosen.quantity),
                      amount: extraSubtotal(option, chosen.quantity).toFixed(2),
                  },
              ]
            : [];
    });

    return (
        <>
            <SeoHead />

            <PageHero
                eyebrow="Almost there"
                title="Your details"
                description={`${offer.name} · ${search.check_in} to ${search.check_out}`}
                breadcrumb="Booking"
            />

            <Section tone="white">
                <div className="grid gap-10 lg:grid-cols-12">
                    <form
                        onSubmit={submit}
                        className="grid gap-8 lg:col-span-7"
                        noValidate
                    >
                        <Fieldset
                            step="1"
                            title="Who is staying"
                            description="We use these details to confirm the reservation and to reach you before you travel."
                        >
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field
                                    label="First name"
                                    htmlFor="first_name"
                                    error={errors.first_name}
                                >
                                    <Input
                                        id="first_name"
                                        value={data.first_name}
                                        onChange={(e) =>
                                            setData(
                                                'first_name',
                                                e.target.value,
                                            )
                                        }
                                        autoComplete="given-name"
                                        aria-invalid={Boolean(
                                            errors.first_name,
                                        )}
                                    />
                                </Field>

                                <Field
                                    label="Last name"
                                    htmlFor="last_name"
                                    error={errors.last_name}
                                >
                                    <Input
                                        id="last_name"
                                        value={data.last_name}
                                        onChange={(e) =>
                                            setData('last_name', e.target.value)
                                        }
                                        autoComplete="family-name"
                                        aria-invalid={Boolean(errors.last_name)}
                                    />
                                </Field>

                                <Field
                                    label="Email"
                                    htmlFor="email"
                                    error={errors.email}
                                >
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData('email', e.target.value)
                                        }
                                        autoComplete="email"
                                        aria-invalid={Boolean(errors.email)}
                                    />
                                </Field>

                                <Field
                                    label="Phone"
                                    htmlFor="phone"
                                    error={errors.phone}
                                >
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                        autoComplete="tel"
                                        inputMode="tel"
                                        placeholder="0999 123 456 or +44 …"
                                        aria-invalid={Boolean(errors.phone)}
                                    />
                                </Field>

                                <Field
                                    label="Country (optional)"
                                    htmlFor="country"
                                    error={errors.country}
                                >
                                    <Input
                                        id="country"
                                        value={data.country}
                                        onChange={(e) =>
                                            setData('country', e.target.value)
                                        }
                                        autoComplete="country-name"
                                    />
                                </Field>

                                <Field
                                    label="Town or city (optional)"
                                    htmlFor="city"
                                    error={errors.city}
                                >
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) =>
                                            setData('city', e.target.value)
                                        }
                                        autoComplete="address-level2"
                                    />
                                </Field>
                            </div>
                        </Fieldset>

                        <Fieldset
                            step="2"
                            title="Add something to your stay"
                            description="Optional. Anything you add is charged with the room, and you can settle it at the desk instead."
                        >
                            {extras.length === 0 ? (
                                <p className="text-sm text-navy/70">
                                    Nothing extra is offered online just now.
                                    Ask at the desk and we will see what can be
                                    arranged.
                                </p>
                            ) : (
                                <>
                                    <div className="overflow-x-auto rounded-xl border border-navy/10">
                                        <table className="w-full border-collapse text-left">
                                            <thead>
                                                <tr className="border-b border-navy/10 bg-navy/[0.03]">
                                                    <th
                                                        scope="col"
                                                        className="w-12 px-4 py-3"
                                                    >
                                                        <span className="sr-only">
                                                            Choose an extra
                                                        </span>
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        className="px-2 py-3 text-xs font-semibold tracking-wide text-navy/60 uppercase"
                                                    >
                                                        Activity
                                                    </th>
                                                    <th
                                                        scope="col"
                                                        /* Wide enough for the
                                                           longest unit line -
                                                           "MWK 85,000 per person
                                                           · 15 minutes" - so
                                                           nothing in this
                                                           column wraps and the
                                                           figures stay
                                                           comparable down the
                                                           page. */
                                                        className="w-48 px-4 py-3 text-right text-xs font-semibold tracking-wide text-navy/60 uppercase"
                                                    >
                                                        Price
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {extras.map((extra) => {
                                                    const position =
                                                        data.extras.findIndex(
                                                            (chosen) =>
                                                                chosen.id ===
                                                                extra.id,
                                                        );
                                                    const chosen =
                                                        position >= 0
                                                            ? data.extras[
                                                                  position
                                                              ]
                                                            : null;

                                                    return (
                                                        <tr
                                                            key={extra.id}
                                                            className={cn(
                                                                /* One amenity per
                                                                   row. The
                                                                   checkbox
                                                                   column stays
                                                                   fixed so the
                                                                   names line
                                                                   up down the
                                                                   page, and
                                                                   only the
                                                                   description
                                                                   wraps. */
                                                                'border-b border-navy/10 align-top transition-colors last:border-b-0',
                                                                chosen
                                                                    ? 'bg-lake-light/40'
                                                                    : 'bg-white hover:bg-navy/[0.02]',
                                                            )}
                                                        >
                                                            <td className="w-12 px-4 py-4">
                                                                <Checkbox
                                                                    id={`extra-${extra.id}`}
                                                                    className="mt-1"
                                                                    checked={Boolean(
                                                                        chosen,
                                                                    )}
                                                                    onCheckedChange={(
                                                                        checked,
                                                                    ) =>
                                                                        checked ===
                                                                        true
                                                                            ? addExtra(
                                                                                  extra,
                                                                              )
                                                                            : removeExtra(
                                                                                  extra.id,
                                                                              )
                                                                    }
                                                                />
                                                            </td>

                                                            <td className="px-2 py-4">
                                                                <Label
                                                                    htmlFor={`extra-${extra.id}`}
                                                                    className="font-display text-base font-semibold text-navy"
                                                                >
                                                                    {extra.name}
                                                                </Label>

                                                                {extra.description && (
                                                                    <p className="mt-1 max-w-prose text-sm text-navy/70">
                                                                        {
                                                                            extra.description
                                                                        }
                                                                    </p>
                                                                )}

                                                                {/* Counted
                                                                    extras put
                                                                    their
                                                                    control
                                                                    under the
                                                                    description,
                                                                    where it
                                                                    belongs to
                                                                    the row it
                                                                    changes. */}
                                                                {chosen &&
                                                                    extra.quantity_label && (
                                                                        <div className="mt-3 flex items-center gap-3">
                                                                            <Label
                                                                                htmlFor={`extra-${extra.id}-quantity`}
                                                                                className="text-sm text-navy/70"
                                                                            >
                                                                                {
                                                                                    extra.quantity_label
                                                                                }
                                                                            </Label>
                                                                            <Select
                                                                                value={String(
                                                                                    chosen.quantity,
                                                                                )}
                                                                                onValueChange={(
                                                                                    value,
                                                                                ) =>
                                                                                    setExtraQuantity(
                                                                                        extra.id,
                                                                                        Number(
                                                                                            value,
                                                                                        ),
                                                                                    )
                                                                                }
                                                                            >
                                                                                <SelectTrigger
                                                                                    id={`extra-${extra.id}-quantity`}
                                                                                    aria-label={
                                                                                        extra.quantity_label
                                                                                    }
                                                                                    className="w-24 rounded-md border-navy/15 bg-white text-sm text-navy"
                                                                                >
                                                                                    <SelectValue />
                                                                                </SelectTrigger>
                                                                                <SelectContent>
                                                                                    {quantitiesBetween(
                                                                                        extra.min_quantity,
                                                                                        extra.max_quantity,
                                                                                    ).map(
                                                                                        (
                                                                                            count,
                                                                                        ) => (
                                                                                            <SelectItem
                                                                                                key={
                                                                                                    count
                                                                                                }
                                                                                                value={String(
                                                                                                    count,
                                                                                                )}
                                                                                            >
                                                                                                {
                                                                                                    count
                                                                                                }
                                                                                            </SelectItem>
                                                                                        ),
                                                                                    )}
                                                                                </SelectContent>
                                                                            </Select>
                                                                        </div>
                                                                    )}

                                                                <InputError
                                                                    className="mt-2"
                                                                    message={
                                                                        errors[
                                                                            `extras.${position}.quantity`
                                                                        ]
                                                                    }
                                                                />
                                                            </td>

                                                            <td className="px-4 py-4 text-right">
                                                                {/* The figure
                                                                    is the thing
                                                                    being compared
                                                                    down this
                                                                    column, so it
                                                                    gets a line to
                                                                    itself and the
                                                                    unit moves
                                                                    below with the
                                                                    duration. The
                                                                    two used to
                                                                    share a line
                                                                    and the price
                                                                    broke after
                                                                    "per". */}
                                                                <p className="text-sm whitespace-nowrap text-navy/70">
                                                                    <span className="font-medium text-lake">
                                                                        {formatMoney(
                                                                            extra.price,
                                                                        )}
                                                                    </span>
                                                                </p>

                                                                <p className="mt-0.5 text-xs whitespace-nowrap text-navy/50">
                                                                    {
                                                                        extra.basis_label
                                                                    }
                                                                    {extra.duration && (
                                                                        <>
                                                                            {' '}
                                                                            ·{' '}
                                                                            {
                                                                                extra.duration
                                                                            }
                                                                        </>
                                                                    )}
                                                                </p>

                                                                {chosen && (
                                                                    <p className="mt-1 font-display text-base font-semibold whitespace-nowrap text-lake">
                                                                        {formatMoney(
                                                                            extraSubtotal(
                                                                                extra,
                                                                                chosen.quantity,
                                                                            ).toFixed(
                                                                                2,
                                                                            ),
                                                                        )}
                                                                    </p>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>

                                    <InputError
                                        className="mt-2"
                                        message={errors.extras}
                                    />
                                </>
                            )}
                        </Fieldset>

                        <Fieldset
                            step="3"
                            title="How would you like to pay"
                            description="Nothing is taken now unless you choose to pay online."
                        >
                            <div className="grid gap-3 sm:grid-cols-2">
                                {booking.payment_options.map((option) => (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() =>
                                            setData(
                                                'payment_option',
                                                option.value,
                                            )
                                        }
                                        aria-pressed={
                                            data.payment_option === option.value
                                        }
                                        className={cn(
                                            'rounded-lg border p-4 text-left transition-colors',
                                            data.payment_option === option.value
                                                ? 'border-lake bg-lake/5'
                                                : 'border-navy/15 bg-white hover:border-navy/30',
                                        )}
                                    >
                                        <span className="flex items-center gap-2 text-sm font-medium text-navy">
                                            {data.payment_option ===
                                                option.value && (
                                                <Check
                                                    className="size-4 text-lake"
                                                    aria-hidden
                                                />
                                            )}
                                            {option.label}
                                        </span>
                                        <span className="mt-1 block text-xs text-navy/60">
                                            {option.value === 'pay_now'
                                                ? 'Card, Airtel Money or TNM Mpamba on our secure checkout.'
                                                : 'Your room is held now and settled at the desk on arrival.'}
                                        </span>
                                    </button>
                                ))}
                            </div>
                            <InputError message={errors.payment_option} />
                        </Fieldset>

                        <Fieldset
                            step="4"
                            title="Anything else"
                            description="Optional, but it helps us have the room ready."
                        >
                            <div className="grid gap-5">
                                <label className="flex items-start gap-3">
                                    <Checkbox
                                        checked={data.airport_transfer}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'airport_transfer',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <span>
                                        <span className="block text-sm font-medium text-navy">
                                            Arrange an airport transfer
                                        </span>
                                        <span className="mt-0.5 block text-xs text-navy/60">
                                            {booking.transfer_note}
                                        </span>
                                    </span>
                                </label>

                                {data.airport_transfer && (
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <Field
                                            label="Flight number"
                                            htmlFor="transfer_flight"
                                            error={errors.transfer_flight}
                                        >
                                            <Input
                                                id="transfer_flight"
                                                value={data.transfer_flight}
                                                onChange={(e) =>
                                                    setData(
                                                        'transfer_flight',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="e.g. ET 871"
                                            />
                                        </Field>

                                        <Field
                                            label="Arrival date and time"
                                            htmlFor="transfer_arrival"
                                            error={errors.transfer_arrival}
                                        >
                                            <Input
                                                id="transfer_arrival"
                                                value={data.transfer_arrival}
                                                onChange={(e) =>
                                                    setData(
                                                        'transfer_arrival',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="e.g. 12 Oct, 14:20"
                                            />
                                        </Field>
                                    </div>
                                )}

                                <Field
                                    label="Special requests (optional)"
                                    htmlFor="special_requests"
                                    error={errors.special_requests}
                                >
                                    <Textarea
                                        id="special_requests"
                                        rows={4}
                                        value={data.special_requests}
                                        onChange={(e) =>
                                            setData(
                                                'special_requests',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Dietary needs, a quiet room, an early arrival…"
                                    />
                                </Field>

                                <Field
                                    label="Discount code (optional)"
                                    htmlFor="coupon_code"
                                    error={errors.coupon_code}
                                >
                                    <Input
                                        id="coupon_code"
                                        value={data.coupon_code}
                                        onChange={(e) =>
                                            setData(
                                                'coupon_code',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Enter a code"
                                    />
                                </Field>
                            </div>
                        </Fieldset>

                        <HouseRules />
                        <div className="grid gap-4 border-t border-navy/10 pt-6">
                            <label className="flex items-start gap-3">
                                <Checkbox
                                    checked={data.terms}
                                    onCheckedChange={(checked) =>
                                        setData('terms', checked === true)
                                    }
                                    aria-invalid={Boolean(errors.terms)}
                                />
                                <span className="text-sm text-navy/75">
                                    I have read the booking terms, including the
                                    cancellation policy and house rules.
                                </span>
                            </label>
                            <InputError message={errors.terms} />

                            <label className="flex items-start gap-3">
                                <Checkbox
                                    checked={data.marketing_opt_in}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'marketing_opt_in',
                                            checked === true,
                                        )
                                    }
                                />
                                <span className="text-sm text-navy/75">
                                    Send me occasional offers from the hotel.
                                </span>
                            </label>

                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing}
                                className="mt-2 w-full sm:w-auto"
                            >
                                {processing
                                    ? 'Holding your room…'
                                    : 'Confirm booking'}
                            </Button>
                        </div>
                    </form>

                    <aside className="lg:col-span-5">
                        <div className="lg:sticky lg:top-24">
                            <div className="overflow-hidden rounded-xl border border-navy/10 bg-white">
                                {offer.image && (
                                    <ResponsiveImage
                                        image={offer.image}
                                        from="hero"
                                        sizes={imageBoxes.half}
                                        alt={offer.name}
                                        className="aspect-[16/9] w-full object-cover"
                                    />
                                )}

                                <div className="p-6">
                                    <h2 className="font-display text-lg font-semibold text-navy">
                                        {offer.name}
                                    </h2>

                                    <dl className="mt-4 space-y-2 text-sm">
                                        <Line
                                            label="Check in"
                                            value={search.check_in}
                                        />
                                        <Line
                                            label="Check out"
                                            value={search.check_out}
                                        />
                                        <Line
                                            label="Nights"
                                            value={String(offer.nights)}
                                        />
                                        <Line
                                            label="Guests"
                                            value={`${search.adults} adult${search.adults === 1 ? '' : 's'}${
                                                search.children > 0
                                                    ? `, ${search.children} child${search.children === 1 ? '' : 'ren'}`
                                                    : ''
                                            }`}
                                        />
                                    </dl>

                                    <dl className="mt-5 space-y-1.5 border-t border-navy/10 pt-4 text-sm">
                                        {nights.map(([date, rate]) => (
                                            <div
                                                key={date}
                                                className="flex justify-between gap-4"
                                            >
                                                <dt className="text-navy/60">
                                                    {formatNight(date)}
                                                </dt>
                                                <dd className="text-navy">
                                                    {formatMoney(rate)}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>

                                    <BookingTotals
                                        pricing={offer.pricing}
                                        label={`Room, ${offer.nights} nights`}
                                        /* Listed and added, so the total moves as the guest
                               chooses without anything being asked of the server. */
                                        extras={extraLines}
                                        className="mt-4 border-t border-navy/10 pt-4"
                                    />
                                </div>
                            </div>

                            <div className="mt-5 rounded-xl border border-navy/10 bg-sand/60 p-5 text-sm text-navy/75">
                                <p className="flex items-center gap-2 font-medium text-navy">
                                    <CalendarDays
                                        className="size-4"
                                        aria-hidden
                                    />
                                    Arriving {booking.check_in_time} onwards
                                </p>
                                <p className="mt-2">
                                    {booking.cancellation_policy}
                                </p>
                                <p className="mt-2 flex items-start gap-2">
                                    <Users
                                        className="mt-0.5 size-4 shrink-0"
                                        aria-hidden
                                    />
                                    {booking.child_policy}
                                </p>
                            </div>
                        </div>
                    </aside>
                </div>
            </Section>
        </>
    );
}

function Fieldset({
    step,
    title,
    description,
    children,
}: {
    step: string;
    title: string;
    description: string;
    children: React.ReactNode;
}) {
    return (
        <fieldset className="grid gap-5">
            <legend className="grid gap-1">
                <span className="text-xs font-semibold tracking-[0.18em] text-gold-dark uppercase">
                    Step {step}
                </span>
                <span className="font-display text-xl font-semibold text-navy">
                    {title}
                </span>
                <span className="text-sm text-navy/65">{description}</span>
            </legend>
            {children}
        </fieldset>
    );
}

function Field({
    label,
    htmlFor,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

function Line({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-navy/60">{label}</dt>
            <dd className="text-navy">{value}</dd>
        </div>
    );
}
