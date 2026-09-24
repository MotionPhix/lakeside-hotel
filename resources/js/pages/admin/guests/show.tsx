import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatLongDate, formatMoney } from '@/lib/format';
import { usePermissions } from '@/lib/permissions';
import { dashboard } from '@/routes';
import bookings from '@/routes/admin/bookings';
import guests from '@/routes/admin/guests';
import type { AdminGuestDetail, AdminGuestStats } from '@/types';

type Props = {
    guest: AdminGuestDetail;
    stats: AdminGuestStats;
};

/** The four states the desk reads at a glance on a guest's history. */
const STATUS_VARIANTS: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'secondary',
    confirmed: 'default',
    checked_in: 'default',
    checked_out: 'outline',
    cancelled: 'destructive',
    no_show: 'destructive',
};

/**
 * One guest: their record, and everything they have done with the hotel.
 *
 * The record is editable because it is built from whatever the guest typed at
 * booking time on a phone - a misspelled name, a country that is really a town -
 * and the desk is the only one who can put it right.
 */
export default function GuestShow({ guest, stats }: Props) {
    const { can } = usePermissions();
    const canManage = can('guests.manage');

    const { data, setData, patch, processing, errors } = useForm({
        first_name: guest.first_name,
        last_name: guest.last_name,
        email: guest.email,
        phone: guest.phone ?? '',
        country: guest.country ?? '',
        city: guest.city ?? '',
        address: guest.address ?? '',
        id_number: guest.id_number ?? '',
        notes: guest.notes ?? '',
        marketing_opt_in: guest.marketing_opt_in,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        patch(guests.update.url(guest.id), { preserveScroll: true });
    };

    return (
        <>
            <Head title={guest.name} />

            <div className="flex flex-col gap-6">
                <Heading
                    title={guest.name}
                    description={
                        guest.since
                            ? `On file since ${formatLongDate(guest.since)}.`
                            : 'Guest record.'
                    }
                />

                <div className="grid gap-4 sm:grid-cols-4">
                    {[
                        { label: 'Stays', value: String(stats.stays) },
                        { label: 'Nights', value: String(stats.nights) },
                        { label: 'Spent', value: formatMoney(stats.spend) },
                        {
                            label: 'Cancelled',
                            value: String(stats.cancelled),
                        },
                    ].map((stat) => (
                        <Card key={stat.label} className="py-4">
                            <CardContent className="space-y-1">
                                <p className="text-sm text-muted-foreground">
                                    {stat.label}
                                </p>
                                <p className="text-2xl font-semibold tracking-tight">
                                    {stat.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-12">
                    <Card className="lg:col-span-5">
                        <CardHeader>
                            <CardTitle>Contact details</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {canManage ? (
                                <form onSubmit={submit} className="grid gap-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="First name"
                                            htmlFor="first_name"
                                            error={errors.first_name}
                                        >
                                            <Input
                                                id="first_name"
                                                value={data.first_name}
                                                onChange={(event) =>
                                                    setData(
                                                        'first_name',
                                                        event.target.value,
                                                    )
                                                }
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
                                                onChange={(event) =>
                                                    setData(
                                                        'last_name',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={Boolean(
                                                    errors.last_name,
                                                )}
                                            />
                                        </Field>
                                    </div>

                                    <Field
                                        label="Email"
                                        htmlFor="email"
                                        error={errors.email}
                                    >
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(event) =>
                                                setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
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
                                            onChange={(event) =>
                                                setData(
                                                    'phone',
                                                    event.target.value,
                                                )
                                            }
                                            inputMode="tel"
                                            placeholder="0999 123 456 or +44 …"
                                            aria-invalid={Boolean(errors.phone)}
                                        />
                                    </Field>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field
                                            label="Country"
                                            htmlFor="country"
                                            error={errors.country}
                                        >
                                            <Input
                                                id="country"
                                                value={data.country}
                                                onChange={(event) =>
                                                    setData(
                                                        'country',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>

                                        <Field
                                            label="Town or city"
                                            htmlFor="city"
                                            error={errors.city}
                                        >
                                            <Input
                                                id="city"
                                                value={data.city}
                                                onChange={(event) =>
                                                    setData(
                                                        'city',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                        </Field>
                                    </div>

                                    <Field
                                        label="Address"
                                        htmlFor="address"
                                        error={errors.address}
                                    >
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(event) =>
                                                setData(
                                                    'address',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Passport or ID number"
                                        htmlFor="id_number"
                                        error={errors.id_number}
                                    >
                                        <Input
                                            id="id_number"
                                            value={data.id_number}
                                            onChange={(event) =>
                                                setData(
                                                    'id_number',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </Field>

                                    <Field
                                        label="Internal notes"
                                        htmlFor="notes"
                                        error={errors.notes}
                                    >
                                        <Textarea
                                            id="notes"
                                            rows={3}
                                            value={data.notes}
                                            onChange={(event) =>
                                                setData(
                                                    'notes',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Anything the desk should know about this guest."
                                        />
                                    </Field>

                                    <label className="flex items-start gap-3 text-sm">
                                        <Checkbox
                                            checked={data.marketing_opt_in}
                                            onCheckedChange={(checked) =>
                                                setData(
                                                    'marketing_opt_in',
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <span>
                                            Happy to receive offers
                                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                                Only set this if the guest has
                                                agreed to it.
                                            </span>
                                        </span>
                                    </label>

                                    {/*
                                     * The toast confirms the save, as it does
                                     * for every other action on this page. A
                                     * second "saved" line here would say the
                                     * same thing twice, at a different moment.
                                     */}
                                    <div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Save changes
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <dl className="grid gap-3 text-sm">
                                    {[
                                        ['Email', guest.email],
                                        ['Phone', guest.phone],
                                        ['Country', guest.country],
                                        ['Town or city', guest.city],
                                        ['Address', guest.address],
                                        [
                                            'Passport or ID number',
                                            guest.id_number,
                                        ],
                                        ['Notes', guest.notes],
                                    ].map(([label, value]) => (
                                        <div key={label}>
                                            <dt className="text-muted-foreground">
                                                {label}
                                            </dt>
                                            <dd>{value || '—'}</dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-7">
                        <CardHeader>
                            <CardTitle>Stays</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {/*
                             * Three columns, because this card is half the page
                             * wide: a separate action column pushed the status
                             * and the Open button out of sight behind an inner
                             * scrollbar. The reference carries the link instead,
                             * with the status and the room stacked under it.
                             */}
                            {guest.stays.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No stays on file yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Reference</TableHead>
                                            <TableHead>Stay</TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {guest.stays.map((stay) => (
                                            <TableRow key={stay.reference}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        href={bookings.show.url(
                                                            stay.reference,
                                                        )}
                                                        className="underline-offset-4 hover:underline"
                                                    >
                                                        {stay.reference}
                                                    </Link>
                                                    <p className="pt-1">
                                                        <Badge
                                                            variant={
                                                                STATUS_VARIANTS[
                                                                    stay.status
                                                                ] ?? 'outline'
                                                            }
                                                        >
                                                            {stay.status_label}
                                                        </Badge>
                                                    </p>
                                                    {stay.rooms.length > 0 && (
                                                        <p className="pt-1 text-xs text-muted-foreground">
                                                            {stay.rooms.join(
                                                                ', ',
                                                            )}
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {formatLongDate(
                                                        stay.check_in,
                                                    )}
                                                    <p className="text-xs text-muted-foreground">
                                                        {stay.nights}{' '}
                                                        {stay.nights === 1
                                                            ? 'night'
                                                            : 'nights'}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(stay.total)}
                                                    <p className="text-xs text-muted-foreground">
                                                        {stay.payment_status}
                                                    </p>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

/**
 * A labelled control with its error underneath, so a message about one field
 * cannot be mistaken for a message about the one beside it.
 */
function Field({
    label,
    htmlFor,
    error,
    children,
}: {
    label: string;
    htmlFor: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

GuestShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Guests', href: guests.index() },
    ],
};
