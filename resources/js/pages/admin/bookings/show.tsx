import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CalendarCheck,
    LogIn,
    LogOut,
    Mail,
    Phone,
    UserX,
    Wallet,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatLongDate, formatMoney, formatNight } from '@/lib/format';
import { usePermissions } from '@/lib/permissions';
import { dashboard } from '@/routes';
import bookings from '@/routes/admin/bookings';
import type {
    AdminBookingDetail,
    AdminBookingItem,
    SelectOption,
} from '@/types';

type Props = {
    booking: AdminBookingDetail;
    methods: SelectOption[];
};

/**
 * Radix refuses an empty value on a select item, so "no room yet" needs a name of
 * its own. It is turned back into nothing on the way to the server.
 */
const UNASSIGNED = 'unassigned';

/**
 * The rooms offered for one line: the ones that could take these nights, with the
 * room the guest is already in kept in the list. That one stays even if it has
 * since been blocked or taken out of service, because the desk still has to be
 * able to see where the guest actually is.
 */
function roomOptions(item: AdminBookingItem): { id: number; name: string }[] {
    if (item.room_id === null || item.room_name === null) {
        return item.available_rooms;
    }

    return item.available_rooms.some((room) => room.id === item.room_id)
        ? item.available_rooms
        : [{ id: item.room_id, name: item.room_name }, ...item.available_rooms];
}

/**
 * One reservation, as the desk sees it: who is coming, what they owe, what has
 * been paid, and the handful of moves available from where the booking is.
 *
 * The buttons are driven by what the server says is legal rather than by the
 * status being re-interpreted here, so the screen cannot offer a move the
 * service would refuse.
 */
export default function BookingShow({ booking, methods }: Props) {
    const { can } = usePermissions();

    /*
     * The server already refuses these moves for a role without the permission -
     * a marketing account gets a 403 from the route. This only stops the screen
     * offering buttons that would come straight back refused.
     */
    const canManage = can('bookings.manage');

    const money = (amount: string) => formatMoney(amount, booking.currency);

    const act = (
        url: string,
        data: Record<string, string | number | null> = {},
    ) => {
        router.patch(url, data, { preserveScroll: true });
    };

    const balance = Number(booking.balance);

    return (
        <>
            <Head title={`Booking ${booking.reference}`} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-2"
                        >
                            <Link href={bookings.index()}>
                                <ArrowLeft />
                                All reservations
                            </Link>
                        </Button>
                        <Heading
                            title={booking.reference}
                            description={`${booking.guest} · ${formatLongDate(booking.check_in)} to ${formatLongDate(booking.check_out)}`}
                        />
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={
                                booking.status_variant as
                                    | 'default'
                                    | 'secondary'
                                    | 'destructive'
                                    | 'outline'
                            }
                        >
                            {booking.status_label}
                        </Badge>
                        <Badge variant="outline">
                            {booking.payment_status_label}
                        </Badge>
                        {booking.airport_transfer && (
                            <Badge variant="secondary">Transfer</Badge>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Actions</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {canManage ? (
                            <>
                                {booking.can.confirm && (
                                    <Button
                                        onClick={() =>
                                            act(
                                                bookings.confirm.url(
                                                    booking.reference,
                                                ),
                                            )
                                        }
                                    >
                                        <CalendarCheck />
                                        Confirm booking
                                    </Button>
                                )}

                                {booking.can.check_in && (
                                    <Button
                                        onClick={() =>
                                            act(
                                                bookings.checkIn.url(
                                                    booking.reference,
                                                ),
                                            )
                                        }
                                    >
                                        <LogIn />
                                        Check in
                                    </Button>
                                )}

                                {booking.can.check_out && (
                                    <Button
                                        onClick={() =>
                                            act(
                                                bookings.checkOut.url(
                                                    booking.reference,
                                                ),
                                            )
                                        }
                                    >
                                        <LogOut />
                                        Check out
                                    </Button>
                                )}

                                {booking.can.no_show && (
                                    <Button
                                        variant="outline"
                                        onClick={() =>
                                            act(
                                                bookings.noShow.url(
                                                    booking.reference,
                                                ),
                                            )
                                        }
                                    >
                                        <UserX />
                                        Mark as no-show
                                    </Button>
                                )}

                                {booking.can.cancel && (
                                    <CancelDialog
                                        reference={booking.reference}
                                        onCancel={(reason) =>
                                            act(
                                                bookings.cancel.url(
                                                    booking.reference,
                                                ),
                                                {
                                                    reason,
                                                },
                                            )
                                        }
                                    />
                                )}

                                {!booking.can.confirm &&
                                    !booking.can.check_in &&
                                    !booking.can.check_out &&
                                    !booking.can.cancel && (
                                        <p className="text-sm text-muted-foreground">
                                            This booking has finished. Nothing
                                            further to do.
                                        </p>
                                    )}
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Your role can read reservations but not change
                                them. Ask a manager if one needs moving.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="flex flex-col gap-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Stay</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <dl className="grid gap-3 sm:grid-cols-2">
                                    <Detail
                                        label="Arrives"
                                        value={formatLongDate(booking.check_in)}
                                    />
                                    <Detail
                                        label="Departs"
                                        value={formatLongDate(
                                            booking.check_out,
                                        )}
                                    />
                                    <Detail
                                        label="Nights"
                                        value={String(booking.nights)}
                                    />
                                    <Detail
                                        label="Guests"
                                        value={`${booking.adults} ${booking.adults === 1 ? 'adult' : 'adults'}${
                                            booking.children > 0
                                                ? `, ${booking.children} ${booking.children === 1 ? 'child' : 'children'}`
                                                : ''
                                        }`}
                                    />
                                    <Detail
                                        label="Booked"
                                        value={booking.created_at ?? '—'}
                                    />
                                    <Detail
                                        label="Taken by"
                                        value={booking.created_by ?? 'Online'}
                                    />
                                </dl>

                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Room</TableHead>
                                            <TableHead>Rate plan</TableHead>
                                            <TableHead className="text-right">
                                                Per night
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Subtotal
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {booking.items.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="font-medium">
                                                    {item.room_type ?? '—'}
                                                    {canManage &&
                                                    booking.can.assign_rooms ? (
                                                        <Select
                                                            value={
                                                                item.room_id ===
                                                                null
                                                                    ? UNASSIGNED
                                                                    : String(
                                                                          item.room_id,
                                                                      )
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                act(
                                                                    bookings.items.room.url(
                                                                        {
                                                                            booking:
                                                                                booking.reference,
                                                                            item: item.id,
                                                                        },
                                                                    ),
                                                                    {
                                                                        room_id:
                                                                            value ===
                                                                            UNASSIGNED
                                                                                ? null
                                                                                : Number(
                                                                                      value,
                                                                                  ),
                                                                    },
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger
                                                                className="mt-1 w-full"
                                                                aria-label={`Room for ${item.room_type ?? 'this line'}`}
                                                            >
                                                                <SelectValue placeholder="No room yet" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem
                                                                    value={
                                                                        UNASSIGNED
                                                                    }
                                                                >
                                                                    No room yet
                                                                </SelectItem>
                                                                {roomOptions(
                                                                    item,
                                                                ).map(
                                                                    (room) => (
                                                                        <SelectItem
                                                                            key={
                                                                                room.id
                                                                            }
                                                                            value={String(
                                                                                room.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                room.name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    ) : (
                                                        item.room && (
                                                            <p className="text-xs text-muted-foreground">
                                                                Room {item.room}
                                                            </p>
                                                        )
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {item.rate_plan ??
                                                        'Standard rate'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {money(
                                                        item.price_per_night,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {money(item.subtotal)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {booking.items[0]?.nightly_rates && (
                                    <div className="text-sm">
                                        <p className="mb-2 text-xs tracking-wide text-muted-foreground uppercase">
                                            Priced night by night
                                        </p>
                                        <div className="grid gap-1 sm:grid-cols-2">
                                            {Object.entries(
                                                booking.items[0].nightly_rates,
                                            ).map(([date, rate]) => (
                                                <div
                                                    key={date}
                                                    className="flex justify-between gap-4 border-b py-1 last:border-0"
                                                >
                                                    <span className="text-muted-foreground">
                                                        {formatNight(date)}
                                                    </span>
                                                    <span>{money(rate)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Payments</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {booking.payments.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        Nothing has been paid yet.
                                    </p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>When</TableHead>
                                                <TableHead>Method</TableHead>
                                                <TableHead>Reference</TableHead>
                                                <TableHead className="text-right">
                                                    Amount
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {booking.payments.map((payment) => (
                                                <TableRow key={payment.id}>
                                                    <TableCell className="text-muted-foreground">
                                                        {payment.paid_at ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {payment.method ?? '—'}
                                                        <p className="text-xs text-muted-foreground">
                                                            {payment.status}
                                                            {payment.recorded_by
                                                                ? ` · ${payment.recorded_by}`
                                                                : ''}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {payment.reference ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {money(payment.amount)}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}

                                {canManage && balance > 0 && (
                                    <PaymentForm
                                        reference={booking.reference}
                                        methods={methods}
                                        balance={booking.balance}
                                        currency={booking.currency}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Folio</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                <Line
                                    label="Room"
                                    value={money(booking.subtotal)}
                                />
                                {Number(booking.discount_total) > 0 && (
                                    <Line
                                        label={`Discount${booking.coupon ? ` (${booking.coupon})` : ''}`}
                                        value={`−${money(booking.discount_total)}`}
                                    />
                                )}
                                <Line
                                    label="VAT and tourism levy"
                                    value={money(booking.tax_total)}
                                />
                                <Line
                                    label="Total"
                                    value={money(booking.total)}
                                    emphasis
                                />
                                <Line
                                    label="Paid"
                                    value={money(booking.amount_paid)}
                                />
                                <Line
                                    label="Balance"
                                    value={money(booking.balance)}
                                    emphasis
                                />
                                {booking.payment_method && (
                                    <p className="pt-2 text-xs text-muted-foreground">
                                        Chosen at booking:{' '}
                                        {booking.payment_method}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Guest</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <p className="font-medium">
                                    {booking.guest_detail.name}
                                </p>
                                <p className="flex items-center gap-2 text-muted-foreground">
                                    <Mail className="size-3.5" />
                                    {booking.guest_detail.email}
                                </p>
                                {booking.guest_detail.phone && (
                                    <p className="flex items-center gap-2 text-muted-foreground">
                                        <Phone className="size-3.5" />
                                        {booking.guest_detail.phone}
                                    </p>
                                )}
                                {(booking.guest_detail.city ||
                                    booking.guest_detail.country) && (
                                    <p className="text-muted-foreground">
                                        {[
                                            booking.guest_detail.city,
                                            booking.guest_detail.country,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </p>
                                )}
                                <p className="text-muted-foreground">
                                    {booking.guest_detail.stays}{' '}
                                    {booking.guest_detail.stays === 1
                                        ? 'stay'
                                        : 'stays'}{' '}
                                    with the hotel
                                </p>
                            </CardContent>
                        </Card>

                        <NotesCard
                            reference={booking.reference}
                            notes={booking.internal_notes}
                            editable={canManage}
                        />

                        {(booking.special_requests ||
                            booking.transfer_details ||
                            booking.cancellation_reason) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Requests and notes</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    {booking.special_requests && (
                                        <p>{booking.special_requests}</p>
                                    )}
                                    {booking.transfer_details && (
                                        <p className="text-muted-foreground">
                                            Transfer:{' '}
                                            {Object.entries(
                                                booking.transfer_details,
                                            )
                                                .map(
                                                    ([key, value]) =>
                                                        `${key.replace(/_/g, ' ')} — ${value}`,
                                                )
                                                .join(', ')}
                                        </p>
                                    )}
                                    {booking.cancellation_reason && (
                                        <p className="text-muted-foreground">
                                            Cancelled:{' '}
                                            {booking.cancellation_reason}
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>History</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2 text-sm">
                                {Object.entries(booking.timeline)
                                    .filter(([, value]) => value)
                                    .map(([key, value]) => (
                                        <Line
                                            key={key}
                                            label={
                                                key.charAt(0).toUpperCase() +
                                                key.slice(1)
                                            }
                                            value={String(value)}
                                        />
                                    ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

function CancelDialog({
    reference,
    onCancel,
}: {
    reference: string;
    onCancel: (reason: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, reset } = useForm({ reason: '' });

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) {
                    reset();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button variant="destructive">
                    <Ban />
                    Cancel booking
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel {reference}?</DialogTitle>
                    <DialogDescription>
                        The room is released straight away and the booking is
                        closed. This cannot be undone from here.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-2">
                    <Label htmlFor="reason">Reason (optional)</Label>
                    <Textarea
                        id="reason"
                        rows={3}
                        value={data.reason}
                        onChange={(event) =>
                            setData('reason', event.target.value)
                        }
                        placeholder="Guest changed plans, flight cancelled…"
                    />
                </div>

                <DialogFooter>
                    <Button
                        variant="ghost"
                        onClick={() => setOpen(false)}
                        type="button"
                    >
                        Keep booking
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        onClick={() => {
                            onCancel(data.reason);
                            setOpen(false);
                        }}
                    >
                        Cancel booking
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function PaymentForm({
    reference,
    methods,
    balance,
    currency,
}: {
    reference: string;
    methods: SelectOption[];
    balance: string;
    currency: string;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        amount: balance,
        method: methods[0]?.value ?? 'cash',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(bookings.payments.store.url(reference), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-4 border-t pt-4 sm:grid-cols-12 sm:items-end"
        >
            <div className="grid gap-1.5 sm:col-span-4">
                <Label
                    htmlFor="amount"
                    className="text-xs text-muted-foreground"
                >
                    Amount
                </Label>
                <Input
                    id="amount"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.amount}
                    onChange={(event) => setData('amount', event.target.value)}
                    aria-invalid={Boolean(errors.amount)}
                />
                {errors.amount && (
                    <p className="text-xs text-destructive">{errors.amount}</p>
                )}
            </div>

            <div className="grid gap-1.5 sm:col-span-4">
                <Label className="text-xs text-muted-foreground">Method</Label>
                <Select
                    value={data.method}
                    onValueChange={(value) => setData('method', value)}
                >
                    <SelectTrigger className="w-full" aria-label="Method">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {methods.map((method) => (
                            <SelectItem key={method.value} value={method.value}>
                                {method.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="sm:col-span-4">
                <Button type="submit" disabled={processing} className="w-full">
                    <Wallet />
                    Record {formatMoney(balance, currency)}
                </Button>
            </div>
        </form>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-0.5">{value}</dd>
        </div>
    );
}

function Line({
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
                    ? 'flex justify-between gap-4 border-t pt-2 font-medium'
                    : 'flex justify-between gap-4'
            }
        >
            <span className="text-muted-foreground">{label}</span>
            <span>{value}</span>
        </div>
    );
}

/**
 * The desk's own note about a reservation: a late arrival, who is paying, a
 * birthday. Kept apart from the guest's special requests, and never sent to them.
 */
function NotesCard({
    reference,
    notes,
    editable,
}: {
    reference: string;
    notes: string | null;
    editable: boolean;
}) {
    const { data, setData, patch, processing, errors } = useForm({
        internal_notes: notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        patch(bookings.notes.url(reference), { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Internal notes</CardTitle>
            </CardHeader>
            <CardContent>
                {editable ? (
                    <form onSubmit={submit} className="grid gap-3">
                        <Textarea
                            id="internal_notes"
                            rows={4}
                            value={data.internal_notes}
                            onChange={(event) =>
                                setData('internal_notes', event.target.value)
                            }
                            placeholder="Late arrival, cash on the day, the name of whoever is paying…"
                            aria-label="Internal note"
                            aria-invalid={Boolean(errors.internal_notes)}
                        />
                        <InputError message={errors.internal_notes} />
                        {/*
                         * The toast is what confirms this, exactly as it does for
                         * every other move on this page. A second "saved" line
                         * beside the button said the same thing twice, and said it
                         * at a different moment.
                         */}
                        <div>
                            <Button
                                type="submit"
                                size="sm"
                                disabled={processing}
                            >
                                Save note
                            </Button>
                        </div>
                    </form>
                ) : notes ? (
                    <p className="text-sm">{notes}</p>
                ) : (
                    <p className="text-sm text-muted-foreground">
                        No note on this reservation.
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

BookingShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Reservations', href: bookings.index() },
    ],
};
