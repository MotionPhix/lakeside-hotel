import { Head, router } from '@inertiajs/react';
import { CreditCard, Mail, Printer } from 'lucide-react';
import { useState } from 'react';
import { ContactCta } from '@/components/public/contact-cta';
import { PageHero } from '@/components/public/page-hero';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatLongDate, formatMoney } from '@/lib/format';
import bookingRoutes from '@/routes/site/booking';
import type { BookingContext, Reservation } from '@/types';

type Props = {
    reservation: Reservation;
    booking: BookingContext;
};

const formatDate = (value: string) => formatLongDate(value);

/**
 * Step three: the guest's copy of the reservation.
 *
 * Deliberately reachable by reference alone, with no account behind it, so the
 * link in the confirmation email works for however long the guest keeps it.
 */
export default function BookingShow({ reservation, booking }: Props) {
    const [paying, setPaying] = useState(false);

    const balance = Number(reservation.balance);
    const paid = Number(reservation.amount_paid);

    const pay = () => {
        setPaying(true);

        router.post(
            bookingRoutes.pay.url(reservation.reference),
            {},
            // The guest is part-way down a long folio; leaving them where they
            // were matters more here than anywhere.
            { preserveScroll: true, onFinish: () => setPaying(false) },
        );
    };

    return (
        <>
            <Head title={`Booking ${reservation.reference}`}>
                <meta name="robots" content="noindex" />
            </Head>

            <PageHero
                eyebrow="Your booking"
                title={reservation.reference}
                description={`${formatDate(reservation.check_in)} to ${formatDate(reservation.check_out)}`}
                breadcrumb="Booking"
            />

            <Section tone="white">
                <div className="grid gap-8 lg:grid-cols-12">
                    <div className="grid gap-6 lg:col-span-7">
                        <div className="rounded-xl border border-navy/10 bg-white p-6">
                            <div className="flex flex-wrap items-center gap-3">
                                <Badge variant="secondary">
                                    {reservation.status_label}
                                </Badge>
                                <Badge variant="outline">
                                    {reservation.payment_status_label}
                                </Badge>
                            </div>

                            <dl className="mt-6 space-y-3 text-sm">
                                <Row
                                    label="Guest"
                                    value={reservation.guest.name}
                                />
                                <Row
                                    label="Email"
                                    value={reservation.guest.email}
                                />
                                {reservation.guest.phone && (
                                    <Row
                                        label="Phone"
                                        value={reservation.guest.phone}
                                    />
                                )}
                                <Row
                                    label="Check in"
                                    value={`${formatDate(reservation.check_in)}, from ${booking.check_in_time}`}
                                />
                                <Row
                                    label="Check out"
                                    value={`${formatDate(reservation.check_out)}, by ${booking.check_out_time}`}
                                />
                                <Row
                                    label="Nights"
                                    value={String(reservation.nights)}
                                />
                                <Row
                                    label="Guests"
                                    value={`${reservation.adults} adult${reservation.adults === 1 ? '' : 's'}${
                                        reservation.children > 0
                                            ? `, ${reservation.children} child${reservation.children === 1 ? '' : 'ren'}`
                                            : ''
                                    }`}
                                />
                                {reservation.rooms.map((room, index) => (
                                    <Row
                                        key={`${room.slug}-${index}`}
                                        label={index === 0 ? 'Room' : 'Also'}
                                        value={room.name ?? '—'}
                                    />
                                ))}
                                <Row
                                    label="Airport transfer"
                                    value={
                                        reservation.airport_transfer
                                            ? 'Requested'
                                            : 'Not requested'
                                    }
                                />
                            </dl>

                            {reservation.special_requests && (
                                <p className="mt-6 rounded-lg bg-sand/60 p-4 text-sm text-navy/75">
                                    <span className="font-medium text-navy">
                                        Your requests:
                                    </span>{' '}
                                    {reservation.special_requests}
                                </p>
                            )}
                        </div>

                        <div className="rounded-xl border border-navy/10 bg-white p-6">
                            <h2 className="font-display text-lg font-semibold text-navy">
                                What happens next
                            </h2>
                            <ul className="mt-4 grid gap-3 text-sm text-navy/75">
                                <li className="flex gap-3">
                                    <Mail
                                        className="mt-0.5 size-4 shrink-0 text-lake"
                                        aria-hidden
                                    />
                                    We have emailed a copy of this booking to{' '}
                                    {reservation.guest.email}. Quote{' '}
                                    {reservation.reference} if you get in touch.
                                </li>
                                <li className="flex gap-3">
                                    <CreditCard
                                        className="mt-0.5 size-4 shrink-0 text-lake"
                                        aria-hidden
                                    />
                                    {balance > 0
                                        ? 'The balance is payable on arrival, or online above if you would rather settle it now.'
                                        : 'This booking is paid in full. Nothing further is due.'}
                                </li>
                                <li className="flex gap-3">
                                    <Printer
                                        className="mt-0.5 size-4 shrink-0 text-lake"
                                        aria-hidden
                                    />
                                    {booking.cancellation_policy}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <aside className="lg:col-span-5">
                        <div className="rounded-xl border border-navy/10 bg-white p-6 lg:sticky lg:top-24">
                            <h2 className="font-display text-lg font-semibold text-navy">
                                Summary
                            </h2>

                            <dl className="mt-5 space-y-2 text-sm">
                                <Row
                                    label="Room"
                                    value={formatMoney(
                                        reservation.subtotal,
                                        reservation.currency,
                                    )}
                                />
                                {Number(reservation.discount_total) > 0 && (
                                    <Row
                                        label="Discount"
                                        value={`−${formatMoney(
                                            reservation.discount_total,
                                            reservation.currency,
                                        )}`}
                                    />
                                )}
                                <Row
                                    label="VAT and tourism levy"
                                    value={formatMoney(
                                        reservation.tax_total,
                                        reservation.currency,
                                    )}
                                />
                                <Row
                                    label="Total"
                                    value={formatMoney(
                                        reservation.total,
                                        reservation.currency,
                                    )}
                                    emphasis
                                />
                                {paid > 0 && (
                                    <Row
                                        label="Paid"
                                        value={formatMoney(
                                            reservation.amount_paid,
                                            reservation.currency,
                                        )}
                                    />
                                )}
                                {balance > 0 && (
                                    <Row
                                        label="Balance"
                                        value={formatMoney(
                                            reservation.balance,
                                            reservation.currency,
                                        )}
                                        emphasis
                                    />
                                )}
                            </dl>

                            {balance > 0 &&
                                booking.online_payment_available && (
                                    <Button
                                        onClick={pay}
                                        size="lg"
                                        disabled={paying}
                                        className="mt-6 w-full"
                                    >
                                        <CreditCard />
                                        {paying
                                            ? 'Opening secure checkout…'
                                            : `Pay ${formatMoney(reservation.balance, reservation.currency)} now`}
                                    </Button>
                                )}

                            {balance > 0 && (
                                <p className="mt-3 text-xs text-navy/60">
                                    {booking.online_payment_available
                                        ? 'Paying opens PayChangu’s secure page, where you can use a card, Airtel Money or TNM Mpamba. You can also settle at the desk.'
                                        : 'Settle at the desk on arrival. We accept card, Airtel Money, TNM Mpamba and cash.'}
                                </p>
                            )}

                            <p className="mt-5 border-t border-navy/10 pt-4 text-xs text-navy/60">
                                {booking.child_policy}
                            </p>
                        </div>
                    </aside>
                </div>
            </Section>

            <ContactCta
                title="Need to change something?"
                description="Reply to your confirmation email or message us — we will take care of it."
                message={`Hello Lakeside Hotel, I would like to change booking ${reservation.reference}.`}
            />
        </>
    );
}

function Row({
    label,
    value,
    emphasis = false,
}: {
    label: string;
    value: string;
    emphasis?: boolean;
}) {
    return (
        <div
            className={
                emphasis
                    ? 'flex justify-between gap-4 border-t border-navy/10 pt-2 font-semibold'
                    : 'flex justify-between gap-4'
            }
        >
            <dt className="text-navy/60">{label}</dt>
            <dd className="text-right text-navy">{value}</dd>
        </div>
    );
}
