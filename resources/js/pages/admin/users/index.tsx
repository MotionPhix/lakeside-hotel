import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2, UserCheck, UserX } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
import { usePermissions } from '@/lib/permissions';
import users from '@/routes/admin/users';
import type { Paginated, RoleOption, StaffMember } from '@/types';

type Props = {
    users: Paginated<StaffMember>;
    filters: { search: string; role: string };
    roles: RoleOption[];
    stats: { total: number; active: number; inactive: number };
};

export default function StaffIndex({
    users: page,
    filters,
    roles,
    stats,
}: Props) {
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters.search);
    const [role, setRole] = useState(filters.role);
    const [pendingDelete, setPendingDelete] = useState<StaffMember | null>(
        null,
    );
    const skipFirstRun = useRef(true);

    useEffect(() => {
        if (skipFirstRun.current) {
            skipFirstRun.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                users.index.url(),
                { search, role },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, role]);

    const toggleStatus = (member: StaffMember) => {
        router.patch(users.status.url(member.id), {}, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (!pendingDelete) {
            return;
        }

        router.delete(users.destroy.url(pendingDelete.id), {
            onFinish: () => setPendingDelete(null),
        });
    };

    return (
        <>
            <Head title="Staff" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Staff accounts"
                        description="Who can sign in to the hotel dashboard, and what they may do."
                    />
                    {can('users.manage') && (
                        <Button asChild>
                            <Link href={users.create()}>
                                <Plus />
                                Add staff member
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        { label: 'Total accounts', value: stats.total },
                        { label: 'Active', value: stats.active },
                        { label: 'Deactivated', value: stats.inactive },
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

                <div className="flex flex-wrap items-center gap-3">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by name, email or job title"
                        className="max-w-sm"
                    />
                    <Select
                        value={role === '' ? 'all' : role}
                        onValueChange={(value) =>
                            setRole(value === 'all' ? '' : value)
                        }
                    >
                        {/* Capped rather than sized: selects are full width by
                            default now, which would stretch this toolbar. */}
                        <SelectTrigger className="max-w-56">
                            <SelectValue placeholder="All roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All roles</SelectItem>
                            {roles.map((option) => (
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

                <Card className="py-0">
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Staff member
                                    </TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Last signed in</TableHead>
                                    {can('users.manage') && (
                                        <TableHead className="pr-6 text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {page.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-10 text-center text-muted-foreground"
                                        >
                                            No staff accounts match this search.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {page.data.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell className="pl-6">
                                            <div className="font-medium">
                                                {member.name}
                                                {member.is_self && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        (you)
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-sm text-muted-foreground">
                                                {member.email}
                                            </div>
                                            {member.job_title && (
                                                <div className="text-xs text-muted-foreground">
                                                    {member.job_title}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {member.role_label}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    member.is_active
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {member.is_active
                                                    ? 'Active'
                                                    : 'Deactivated'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {member.last_login_at ?? 'Never'}
                                        </TableCell>
                                        {can('users.manage') && (
                                            <TableCell className="pr-6 text-right">
                                                {member.can_manage ? (
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={users.edit(
                                                                    member.id,
                                                                )}
                                                                aria-label={`Edit ${member.name}`}
                                                            >
                                                                <Pencil />
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                toggleStatus(
                                                                    member,
                                                                )
                                                            }
                                                            aria-label={
                                                                member.is_active
                                                                    ? `Deactivate ${member.name}`
                                                                    : `Activate ${member.name}`
                                                            }
                                                        >
                                                            {member.is_active ? (
                                                                <UserX />
                                                            ) : (
                                                                <UserCheck />
                                                            )}
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                setPendingDelete(
                                                                    member,
                                                                )
                                                            }
                                                            aria-label={`Remove ${member.name}`}
                                                        >
                                                            <Trash2 />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        {page.total === 0
                            ? 'No accounts'
                            : `Showing ${page.from}–${page.to} of ${page.total}`}
                    </p>
                    {page.last_page > 1 && (
                        <div className="flex items-center gap-1">
                            {page.links.map((link, index) =>
                                link.url ? (
                                    <Button
                                        key={index}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        asChild
                                    >
                                        <Link
                                            href={link.url}
                                            preserveScroll
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    </Button>
                                ) : (
                                    <span
                                        key={index}
                                        className="px-2 text-sm text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ),
                            )}
                        </div>
                    )}
                </div>
            </div>

            <Dialog
                open={pendingDelete !== null}
                onOpenChange={(open) => !open && setPendingDelete(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove staff account?</DialogTitle>
                        <DialogDescription>
                            {pendingDelete?.name} will lose access to the
                            dashboard immediately. This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setPendingDelete(null)}
                        >
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Remove account
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

StaffIndex.layout = {
    breadcrumbs: [
        {
            title: 'Staff',
            href: users.index(),
        },
    ],
};
