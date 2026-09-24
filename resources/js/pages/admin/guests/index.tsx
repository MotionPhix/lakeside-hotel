import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
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
import { formatLongDate, formatMoney } from '@/lib/format';
import { dashboard } from '@/routes';
import guests from '@/routes/admin/guests';
import type { AdminGuestRow, Paginated, SelectOption } from '@/types';

type Filters = {
    search: string;
    returning: string;
    sort: string;
};

type Props = {
    guests: Paginated<AdminGuestRow>;
    filters: Filters;
    totals: {
        matching: number;
        returning: number;
    };
};

const SORTS: SelectOption[] = [
    { value: 'recent', label: 'Most recent stay' },
    { value: 'spend', label: 'Most spent' },
];

/**
 * The guest list.
 *
 * Guests arrive by booking rather than being entered here, so this is a record to
 * read and correct rather than one to fill in: who has been, how often, and what
 * they have spent with the hotel. Filters travel in the URL, so "the people who
 * have stayed more than once" can be sent to somebody as a link.
 */
export default function GuestsIndex({ guests: page, filters, totals }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [returning, setReturning] = useState(filters.returning === '1');
    const [sort, setSort] = useState(filters.sort || 'recent');

    const apply = (event?: FormEvent) => {
        event?.preventDefault();

        router.get(
            guests.index.url(),
            { search, returning: returning ? '1' : '', sort },
            { preserveState: true, preserveScroll: true },
        );
    };

    const reset = () => {
        setSearch('');
        setReturning(false);
        setSort('recent');

        router.get(guests.index.url(), {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Guests" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Guests"
                        description="Everyone who has booked with the hotel, and what they have done with us."
                    />
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <span>
                            {totals.matching}{' '}
                            {totals.matching === 1 ? 'guest' : 'guests'}
                        </span>
                        {totals.returning > 0 && (
                            <Badge variant="outline">
                                {totals.returning} returning
                            </Badge>
                        )}
                    </div>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <form
                            onSubmit={apply}
                            className="flex flex-wrap items-end gap-3"
                        >
                            <div className="grid min-w-[16rem] flex-1 gap-2">
                                <Label htmlFor="search">Search</Label>
                                <Input
                                    id="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Name, email or phone"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="sort">Order by</Label>
                                <Select
                                    value={sort}
                                    onValueChange={(value) => {
                                        setSort(value);

                                        router.get(
                                            guests.index.url(),
                                            {
                                                search,
                                                returning: returning ? '1' : '',
                                                sort: value,
                                            },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                            },
                                        );
                                    }}
                                >
                                    <SelectTrigger id="sort" className="w-56">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {SORTS.map((option) => (
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

                            <label className="flex h-11 items-center gap-2 text-sm">
                                <Checkbox
                                    checked={returning}
                                    onCheckedChange={(checked) =>
                                        setReturning(checked === true)
                                    }
                                />
                                Returning only
                            </label>

                            <div className="flex items-center gap-2">
                                <Button type="submit">
                                    <Search />
                                    Filter
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={reset}
                                >
                                    Clear
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        {page.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No guests match that. Every guest appears here
                                once they have booked.
                            </p>
                        ) : (
                            <div className="-mx-6 overflow-x-auto px-6">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Guest</TableHead>
                                            <TableHead>Contact</TableHead>
                                            <TableHead className="text-right">
                                                Stays
                                            </TableHead>
                                            <TableHead>Last stay</TableHead>
                                            <TableHead className="text-right">
                                                Spent
                                            </TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {page.data.map((guest) => (
                                            <TableRow key={guest.id}>
                                                <TableCell className="font-medium">
                                                    {guest.name}
                                                    <p className="text-xs text-muted-foreground">
                                                        {guest.email}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {guest.phone ?? '—'}
                                                    {guest.location && (
                                                        <p className="text-xs text-muted-foreground">
                                                            {guest.location}
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {guest.stays}
                                                    {guest.returning && (
                                                        <p className="pt-1">
                                                            <Badge variant="secondary">
                                                                Returning
                                                            </Badge>
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {guest.last_stay
                                                        ? formatLongDate(
                                                              guest.last_stay,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(guest.spend)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={guests.show.url(
                                                                guest.id,
                                                            )}
                                                        >
                                                            Open
                                                        </Link>
                                                    </Button>
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

GuestsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Guests', href: guests.index() },
    ],
};
