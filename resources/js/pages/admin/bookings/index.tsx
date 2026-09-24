import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DatePicker } from '@/components/date-picker';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney, formatNight } from '@/lib/format';
import { dashboard } from '@/routes';
import bookings from '@/routes/admin/bookings';
import type { AdminBookingRow, Paginated, SelectOption } from '@/types';

type Filters = {
    search: string;
    status: string;
    from: string;
    to: string;
    unpaid: string;
};

type Props = {
    bookings: Paginated<AdminBookingRow>;
    filters: Filters;
    statuses: SelectOption[];
    totals: {
        matching: number;
        outstanding: string;
    };
};

/** shadcn's Select reserves the empty string, so "any" needs a real value. */
const ANY = 'any';

/**
 * The reservations list.
 *
 * Filters run through the URL rather than being applied in the browser, so a
 * narrowed list can be bookmarked and shared with another member of the desk -
 * which is how "the unpaid ones from last week" gets passed around.
 */
export default function BookingsIndex({
    bookings: page,
    filters,
    statuses,
    totals,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status || ANY);
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [unpaid, setUnpaid] = useState(filters.unpaid === '1');

    const apply = (event?: FormEvent) => {
        event?.preventDefault();

        router.get(
            bookings.index.url(),
            {
                search,
                status: status === ANY ? '' : status,
                from,
                to,
                unpaid: unpaid ? '1' : '',
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setStatus(ANY);
        setFrom('');
        setTo('');
        setUnpaid(false);

        router.get(bookings.index.url(), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Reservations" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Reservations"
                        description="Every booking, what it owes, and where it is up to."
                    />
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span>
                            {totals.matching}{' '}
                            {totals.matching === 1 ? 'booking' : 'bookings'}
                        </span>
                        {Number(totals.outstanding) > 0 && (
                            <Badge variant="outline">
                                {formatMoney(totals.outstanding)} outstanding
                            </Badge>
                        )}
                    </div>
                </div>

                <Card className="py-4">
                    <CardContent>
                        <form
                            onSubmit={apply}
                            className="grid gap-4 lg:grid-cols-12"
                        >
                            <div className="grid gap-1.5 lg:col-span-4">
                                <Label
                                    htmlFor="search"
                                    className="text-xs text-muted-foreground"
                                >
                                    Search
                                </Label>
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Reference, name, email or phone"
                                />
                            </div>

                            <div className="grid gap-1.5 lg:col-span-2">
                                <Label className="text-xs text-muted-foreground">
                                    Status
                                </Label>
                                <Select
                                    value={status}
                                    onValueChange={setStatus}
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-label="Status"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={ANY}>
                                            Any status
                                        </SelectItem>
                                        {statuses.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-1.5 lg:col-span-2">
                                <Label className="text-xs text-muted-foreground">
                                    Arriving from
                                </Label>
                                <DatePicker
                                    value={from}
                                    onChange={setFrom}
                                    clearable
                                    placeholder="Any date"
                                />
                            </div>

                            <div className="grid gap-1.5 lg:col-span-2">
                                <Label className="text-xs text-muted-foreground">
                                    Arriving to
                                </Label>
                                <DatePicker
                                    value={to}
                                    onChange={setTo}
                                    clearable
                                    placeholder="Any date"
                                    min={from || undefined}
                                />
                            </div>

                            <div className="flex items-end gap-2 lg:col-span-2">
                                <Button type="submit" className="w-full">
                                    <Search />
                                    Filter
                                </Button>
                            </div>

                            <div className="flex flex-wrap items-center gap-4 lg:col-span-12">
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox
                                        checked={unpaid}
                                        onCheckedChange={(checked) =>
                                            setUnpaid(checked === true)
                                        }
                                    />
                                    Only what is still owed
                                </label>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={reset}
                                >
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent>
                        {page.data.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                No reservations match those filters.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Guest</TableHead>
                                            <TableHead>Stay</TableHead>
                                            <TableHead>Room</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Balance
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {page.data.map((booking) => (
                                            <TableRow key={booking.id}>
                                                <TableCell>
                                                    <Link
                                                        href={bookings.show(
                                                            booking.reference,
                                                        )}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {booking.guest}
                                                    </Link>
                                                    <p className="text-xs text-muted-foreground">
                                                        {booking.reference} ·{' '}
                                                        {booking.source}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatNight(
                                                        booking.check_in,
                                                    )}
                                                    {' → '}
                                                    {formatNight(
                                                        booking.check_out,
                                                    )}
                                                    <p className="text-xs text-muted-foreground">
                                                        {booking.nights}{' '}
                                                        {booking.nights === 1
                                                            ? 'night'
                                                            : 'nights'}
                                                        , {booking.guests}{' '}
                                                        {booking.guests === 1
                                                            ? 'guest'
                                                            : 'guests'}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {booking.room ?? '—'}
                                                    {booking.rooms > 1 && (
                                                        <p className="text-xs">
                                                            +{booking.rooms - 1}{' '}
                                                            more
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
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
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {
                                                            booking.payment_status_label
                                                        }
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(booking.total)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {booking.unpaid ? (
                                                        <span className="font-medium">
                                                            {formatMoney(
                                                                booking.balance,
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="text-sm text-muted-foreground">
                                                            Settled
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        {page.last_page > 1 && (
                            <div className="mt-6 flex flex-wrap items-center justify-center gap-1">
                                {page.links.map((link, index) =>
                                    link.url === null ? (
                                        <span
                                            key={index}
                                            className="px-3 py-2 text-sm text-muted-foreground"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            preserveScroll
                                            className={
                                                link.active
                                                    ? 'rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground'
                                                    : 'rounded-md px-3 py-2 text-sm hover:bg-accent'
                                            }
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Reservations', href: bookings.index() },
    ],
};
