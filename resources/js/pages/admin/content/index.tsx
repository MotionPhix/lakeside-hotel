import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    Eye,
    EyeOff,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import contentRoutes from '@/routes/admin/content';
import type { CmsResource, CmsRow, Paginated } from '@/types';

type Props = {
    resource: CmsResource;
    rows: Paginated<CmsRow>;
    search: string;
    media: Record<string, Record<string, { thumb: string }[]>>;
};

/**
 * The list for one content type.
 *
 * Every control on this screen is driven by the schema: which columns appear,
 * whether records can be reordered, and whether there is a publish toggle at all.
 * A content type whose state is an enum rather than a flag simply has no toggle.
 */
export default function ContentIndex({ resource, rows, search, media }: Props) {
    const [term, setTerm] = useState(search);
    const [deleting, setDeleting] = useState<CmsRow | null>(null);

    const filter = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            contentRoutes.index.url(resource.key),
            { search: term },
            { preserveState: true, preserveScroll: true },
        );
    };

    const act = (url: string, method: 'patch' | 'delete' = 'patch') => {
        router[method](url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={resource.label} />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={resource.label}
                        description={resource.description}
                    />
                    <Button asChild>
                        <Link href={contentRoutes.create(resource.key)}>
                            <Plus />
                            Add {resource.singular.toLowerCase()}
                        </Link>
                    </Button>
                </div>

                {resource.searchable.length > 0 && (
                    <form onSubmit={filter} className="flex gap-2">
                        <Input
                            value={term}
                            onChange={(event) => setTerm(event.target.value)}
                            placeholder={`Search ${resource.label.toLowerCase()}`}
                            className="max-w-sm"
                            aria-label="Search"
                        />
                        <Button type="submit" variant="outline">
                            <Search />
                            Search
                        </Button>
                    </form>
                )}

                <Card>
                    <CardContent>
                        {rows.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">
                                Nothing here yet.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            {resource.columns.map((column) => (
                                                <TableHead key={column}>
                                                    {labelFor(resource, column)}
                                                </TableHead>
                                            ))}
                                            {rows.data.some(
                                                (row) => row.published !== null,
                                            ) && (
                                                <TableHead>Published</TableHead>
                                            )}
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {rows.data.map((row, index) => (
                                            <TableRow key={row.id}>
                                                {resource.columns.map(
                                                    (column) => (
                                                        <TableCell
                                                            key={column}
                                                            className="align-top"
                                                        >
                                                            {column ===
                                                                resource
                                                                    .columns[0] &&
                                                            media[row.id] ? (
                                                                <div className="flex items-center gap-3">
                                                                    {thumbnail(
                                                                        media[
                                                                            row
                                                                                .id
                                                                        ],
                                                                    )}
                                                                    <span>
                                                                        {
                                                                            row
                                                                                .values[
                                                                                column
                                                                            ]
                                                                        }
                                                                    </span>
                                                                </div>
                                                            ) : (
                                                                (row.values[
                                                                    column
                                                                ] ?? '—')
                                                            )}
                                                        </TableCell>
                                                    ),
                                                )}

                                                {row.published !== null && (
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                row.published
                                                                    ? 'default'
                                                                    : 'outline'
                                                            }
                                                        >
                                                            {row.published
                                                                ? 'Live'
                                                                : 'Hidden'}
                                                        </Badge>
                                                    </TableCell>
                                                )}

                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-1">
                                                        {resource.sortable && (
                                                            <>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    disabled={
                                                                        index ===
                                                                        0
                                                                    }
                                                                    aria-label={`Move ${row.title} up`}
                                                                    onClick={() =>
                                                                        act(
                                                                            contentRoutes.move.url(
                                                                                {
                                                                                    resource:
                                                                                        resource.key,
                                                                                    id: row.id,
                                                                                    direction:
                                                                                        'up',
                                                                                },
                                                                            ),
                                                                        )
                                                                    }
                                                                >
                                                                    <ArrowUp />
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label={`Move ${row.title} down`}
                                                                    onClick={() =>
                                                                        act(
                                                                            contentRoutes.move.url(
                                                                                {
                                                                                    resource:
                                                                                        resource.key,
                                                                                    id: row.id,
                                                                                    direction:
                                                                                        'down',
                                                                                },
                                                                            ),
                                                                        )
                                                                    }
                                                                >
                                                                    <ArrowDown />
                                                                </Button>
                                                            </>
                                                        )}

                                                        {row.published !==
                                                            null && (
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                aria-label={
                                                                    row.published
                                                                        ? `Hide ${row.title}`
                                                                        : `Publish ${row.title}`
                                                                }
                                                                onClick={() =>
                                                                    act(
                                                                        contentRoutes.publish.url(
                                                                            {
                                                                                resource:
                                                                                    resource.key,
                                                                                id: row.id,
                                                                            },
                                                                        ),
                                                                    )
                                                                }
                                                            >
                                                                {row.published ? (
                                                                    <EyeOff />
                                                                ) : (
                                                                    <Eye />
                                                                )}
                                                            </Button>
                                                        )}

                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                            aria-label={`Edit ${row.title}`}
                                                        >
                                                            <Link
                                                                href={contentRoutes.edit(
                                                                    {
                                                                        resource:
                                                                            resource.key,
                                                                        id: row.id,
                                                                    },
                                                                )}
                                                            >
                                                                <Pencil />
                                                            </Link>
                                                        </Button>

                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label={`Delete ${row.title}`}
                                                            onClick={() =>
                                                                setDeleting(row)
                                                            }
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        {rows.last_page > 1 && (
                            <div className="mt-6 flex flex-wrap items-center justify-center gap-1">
                                {rows.links.map((link, index) =>
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

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Delete {deleting?.title ?? 'this record'}?
                        </DialogTitle>
                        <DialogDescription>
                            It is removed from the website straight away. This
                            cannot be undone from here.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            type="button"
                            onClick={() => setDeleting(null)}
                        >
                            Keep it
                        </Button>
                        <Button
                            variant="destructive"
                            type="button"
                            onClick={() => {
                                if (deleting !== null) {
                                    act(
                                        contentRoutes.destroy.url({
                                            resource: resource.key,
                                            id: deleting.id,
                                        }),
                                        'delete',
                                    );
                                }

                                setDeleting(null);
                            }}
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/** The column heading, taken from the field's own label. */
function labelFor(resource: CmsResource, column: string): string {
    const field = resource.fields.find((item) => item.name === column);

    return field?.label ?? column;
}

/**
 * The first image in the record's first collection, if it has one.
 *
 * Showing the picture next to its name is the difference between a gallery list
 * that can be worked with and one that cannot.
 */
function thumbnail(
    collections: Record<string, { thumb: string }[]> | undefined,
): React.ReactNode {
    const first =
        collections === undefined
            ? undefined
            : Object.values(collections)[0]?.[0];

    if (first === undefined) {
        return null;
    }

    return (
        <img
            src={first.thumb}
            alt=""
            loading="lazy"
            decoding="async"
            className="h-10 w-14 shrink-0 rounded object-cover"
        />
    );
}

ContentIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Website content', href: contentRoutes.hub() },
    ],
};
