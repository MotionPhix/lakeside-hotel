import { Head, Link } from '@inertiajs/react';
import { Search } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { useFilters } from '@/hooks/use-filters';
import { formatLongDate } from '@/lib/format';
import { dashboard } from '@/routes';
import inquiries from '@/routes/admin/inquiries';
import type { AdminInquiryRow, Paginated, SelectOption } from '@/types';

type Filters = {
    search: string;
    status: string;
    type: string;
};

type Props = {
    inquiries: Paginated<AdminInquiryRow>;
    filters: Filters;
    totals: {
        matching: number;
        open: number;
        unclaimed: number;
    };
    statuses: SelectOption[];
    types: SelectOption[];
};

const ANY = 'any';

/**
 * The enquiry queue.
 *
 * Everything the hotel has not dealt with sits at the top, and the numbers above
 * the list say how many of those nobody has picked up - which is the number that
 * actually matters, because an enquiry nobody owns is an enquiry nobody answers.
 */
export default function InquiriesIndex({
    inquiries: page,
    filters,
    totals,
    statuses,
    types,
}: Props) {
    /*
     * The filters apply themselves: typing asks once the typist pauses, and
     * choosing from a select asks at once. The controls say "any" for a filter
     * that is not set, but the query says nothing at all, because the back end
     * takes a status or a kind that is one of its own and would refuse the word.
     */
    const { values, set, reset, active } = useFilters(inquiries.index.url(), {
        search: filters.search,
        status: filters.status,
        type: filters.type,
    });

    return (
        <>
            <Head title="Inquiries" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Inquiries"
                        description="Website enquiries, event requests and follow ups."
                    />
                    <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                        <Badge variant="outline">{totals.open} open</Badge>
                        {totals.unclaimed > 0 && (
                            <Badge variant="secondary">
                                {totals.unclaimed} nobody has picked up
                            </Badge>
                        )}
                    </div>
                </div>

                {/*
                 * No card around the filters: they belong to the list below them
                 * rather than sitting in a box of their own, and a border here
                 * would draw a line between a screen's controls and the thing
                 * they control.
                 */}
                <div className="flex flex-wrap items-end gap-3">
                    <div className="relative grid min-w-[16rem] flex-1 gap-2">
                        <Label htmlFor="search">Search</Label>
                        <Search
                            className="pointer-events-none absolute bottom-3 left-3 size-4 text-muted-foreground"
                            aria-hidden
                        />
                        <Input
                            id="search"
                            value={values.search}
                            onChange={(event) =>
                                set('search', event.target.value)
                            }
                            placeholder="Name, email or subject"
                            className="pl-9"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>
                        {/*
                         * Blank is what "any" means, both in the query and in the
                         * hook: sending the word "any" would be refused by the
                         * back end, and holding it in state would leave the list
                         * looking filtered when it is not. The control is the only
                         * place that needs the word.
                         */}
                        <Select
                            value={values.status === '' ? ANY : values.status}
                            onValueChange={(value) =>
                                set('status', value === ANY ? '' : value, true)
                            }
                        >
                            <SelectTrigger id="status" className="w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ANY}>Any status</SelectItem>
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

                    <div className="grid gap-2">
                        <Label htmlFor="type">Kind</Label>
                        <Select
                            value={values.type === '' ? ANY : values.type}
                            onValueChange={(value) =>
                                set('type', value === ANY ? '' : value, true)
                            }
                        >
                            <SelectTrigger id="type" className="w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ANY}>Any kind</SelectItem>
                                {types.map((option) => (
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

                    {active && (
                        <Button type="button" variant="ghost" onClick={reset}>
                            Clear
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {page.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {totals.open === 0
                                    ? 'Nothing is waiting. Every enquiry has been dealt with.'
                                    : 'No enquiries match those filters.'}
                            </p>
                        ) : (
                            <div className="-mx-6 overflow-x-auto px-6">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>From</TableHead>
                                            <TableHead>Enquiry</TableHead>
                                            <TableHead>Kind</TableHead>
                                            <TableHead>Received</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Owner</TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {page.data.map((inquiry) => (
                                            <TableRow key={inquiry.id}>
                                                <TableCell className="font-medium">
                                                    {inquiry.name}
                                                    <p className="text-xs text-muted-foreground">
                                                        {inquiry.email}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="max-w-xs">
                                                    <span className="font-medium">
                                                        {inquiry.subject}
                                                    </span>
                                                    <p className="truncate text-xs text-muted-foreground">
                                                        {inquiry.excerpt}
                                                    </p>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {inquiry.type_label}
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {inquiry.received_at
                                                        ? formatLongDate(
                                                              inquiry.received_at,
                                                          )
                                                        : '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={
                                                            inquiry.status_variant
                                                        }
                                                    >
                                                        {inquiry.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm">
                                                    {inquiry.assigned_name ??
                                                        'Nobody yet'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={inquiries.show.url(
                                                                inquiry.id,
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

InquiriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Inquiries', href: inquiries.index() },
    ],
};
