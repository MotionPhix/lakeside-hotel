import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BedDouble,
    CalendarClock,
    Clock,
    LogIn,
    LogOut,
    Mail,
    Wallet,
} from 'lucide-react';
import type { ReactNode } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatMoney } from '@/lib/format';
import { hotelModules } from '@/lib/modules';
import { usePermissions } from '@/lib/permissions';
import { dashboard } from '@/routes';
import bookings from '@/routes/admin/bookings';
import type { DashboardOverview, StayRow } from '@/types';

export default function Dashboard({
    overview,
}: {
    overview: DashboardOverview;
}) {
    const { user, can, canAny } = usePermissions();

    if (!user) {
        return null;
    }

    const upcoming = hotelModules.filter(
        (module) => !isBuilt(module.key) && can(module.permission),
    );

    const canSeeBookings = can('bookings.view');

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Good day, ${user.name.split(' ')[0]}`}
                        description={overview.date_label}
                    />
                    <Badge variant="secondary">{user.role_label}</Badge>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Metric
                        icon={<BedDouble className="size-4" />}
                        label="Occupancy tonight"
                        value={`${overview.occupancy.percentage}%`}
                        note={`${overview.occupancy.occupied} of ${overview.occupancy.sellable} rooms`}
                    />
                    <Metric
                        icon={<CalendarClock className="size-4" />}
                        label="In house"
                        value={String(overview.in_house)}
                        note={`${overview.arrivals.length} arriving, ${overview.departures.length} leaving`}
                    />
                    <Metric
                        icon={<Wallet className="size-4" />}
                        label="Taken today"
                        value={formatMoney(overview.revenue.taken_today)}
                        note={`${formatMoney(overview.revenue.taken_this_month)} in ${overview.revenue.month_label}`}
                    />
                    <Metric
                        icon={<Clock className="size-4" />}
                        label="Awaiting action"
                        value={String(overview.pending)}
                        note={
                            Number(overview.outstanding) > 0
                                ? `${formatMoney(overview.outstanding)} outstanding`
                                : 'nothing outstanding'
                        }
                    />
                </div>

                {canSeeBookings && (
                    <div className="grid gap-6 xl:grid-cols-2">
                        <StayTable
                            title="Arriving today"
                            icon={
                                <LogIn className="size-4 text-muted-foreground" />
                            }
                            rows={overview.arrivals}
                            empty="Nobody is due to arrive today."
                        />
                        <StayTable
                            title="Leaving today"
                            icon={
                                <LogOut className="size-4 text-muted-foreground" />
                            }
                            rows={overview.departures}
                            empty="Nobody is checking out today."
                        />
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarClock className="size-4 text-muted-foreground" />
                                The week ahead
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {overview.arriving_soon.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No arrivals booked over the next seven days.
                                </p>
                            ) : (
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    {overview.arriving_soon.map((day) => (
                                        <div
                                            key={day.date}
                                            className="rounded-lg border p-3"
                                        >
                                            <p className="text-sm font-medium">
                                                {day.label}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {day.bookings}{' '}
                                                {day.bookings === 1
                                                    ? 'booking'
                                                    : 'bookings'}{' '}
                                                · {day.guests}{' '}
                                                {day.guests === 1
                                                    ? 'guest'
                                                    : 'guests'}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Mail className="size-4 text-muted-foreground" />
                                Recent enquiries
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {overview.inquiries.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No enquiries yet.
                                </p>
                            ) : (
                                overview.inquiries.map((inquiry) => (
                                    <div
                                        key={inquiry.id}
                                        className="space-y-1 border-b pb-3 last:border-0 last:pb-0"
                                    >
                                        <p className="truncate text-sm font-medium">
                                            {inquiry.subject}
                                        </p>
                                        <p className="truncate text-sm text-muted-foreground">
                                            {inquiry.name} · {inquiry.type}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {inquiry.status}
                                            {inquiry.received
                                                ? ` · ${inquiry.received}`
                                                : ''}
                                        </p>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                {canSeeBookings && (
                    <div className="flex flex-wrap gap-3">
                        <Button asChild>
                            <Link href={bookings.index()}>
                                All reservations
                                <ArrowRight />
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link
                                href={bookings.index({
                                    query: { status: 'pending' },
                                })}
                            >
                                Confirm {overview.pending} pending
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link
                                href={bookings.index({
                                    query: { unpaid: '1' },
                                })}
                            >
                                Chasing balances
                            </Link>
                        </Button>
                    </div>
                )}

                {upcoming.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="size-4 text-muted-foreground" />
                                Still to come
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {upcoming.map((module) => (
                                <div
                                    key={module.key}
                                    className="flex items-start gap-3 rounded-lg border border-dashed p-4"
                                >
                                    <module.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            {module.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {module.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {upcoming.length === 0 && !canAny(['bookings.view']) && (
                    <p className="text-sm text-muted-foreground">
                        Your role is limited to the modules above. Ask an
                        administrator if you need wider access.
                    </p>
                )}
            </div>
        </>
    );
}

/** Which modules have a screen behind them today. */
function isBuilt(key: string): boolean {
    return key === 'dashboard' || key === 'users' || key === 'bookings';
}

function Metric({
    icon,
    label,
    value,
    note,
}: {
    icon: ReactNode;
    label: string;
    value: string;
    note: string;
}) {
    return (
        <Card className="py-4">
            <CardContent className="space-y-1">
                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                    {icon}
                    {label}
                </p>
                <p className="text-2xl font-semibold">{value}</p>
                <p className="text-xs text-muted-foreground">{note}</p>
            </CardContent>
        </Card>
    );
}

/**
 * Today's arrivals or departures. Rows link into the reservation, because the
 * only reason to look at this list is to do something about one of them.
 */
function StayTable({
    title,
    icon,
    rows,
    empty,
}: {
    title: string;
    icon: ReactNode;
    rows: StayRow[];
    empty: string;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    {icon}
                    {title}
                </CardTitle>
            </CardHeader>
            <CardContent>
                {rows.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{empty}</p>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Guest</TableHead>
                                <TableHead>Room</TableHead>
                                <TableHead>Nights</TableHead>
                                <TableHead className="text-right">
                                    Balance
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow key={row.reference}>
                                    <TableCell>
                                        <Link
                                            href={bookings.show(row.reference)}
                                            className="font-medium hover:underline"
                                        >
                                            {row.guest}
                                        </Link>
                                        <p className="text-xs text-muted-foreground">
                                            {row.reference}
                                            {row.airport_transfer
                                                ? ' · transfer'
                                                : ''}
                                        </p>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {row.room ?? '—'}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {row.nights}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {row.unpaid ? (
                                            <Badge variant="outline">
                                                {formatMoney(row.balance)}
                                            </Badge>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                Paid
                                            </span>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
