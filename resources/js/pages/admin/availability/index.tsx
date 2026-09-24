import { Head, router, useForm } from '@inertiajs/react';
import { Plus, RotateCcw } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DatePicker } from '@/components/date-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { usePermissions } from '@/lib/permissions';
import availability from '@/routes/admin/availability';
import type { AvailabilityClosure, SelectOption } from '@/types';

type Props = {
    blocks: AvailabilityClosure[];
    rooms: SelectOption[];
    reasons: SelectOption[];
    stats: {
        closed_tonight: number;
        upcoming: number;
        out_of_service: number;
    };
};

/**
 * Where the hotel takes a room off sale: servicing, a refurbishment, house use.
 *
 * A closure is stored as two inclusive dates, which is how the booking engine
 * reads it, so the form asks for the first and last night rather than a start and
 * an end. The list puts what is in force or still to come at the top, because that
 * is what the desk is answering the phone about.
 */
const stateBadge: Record<
    AvailabilityClosure['state'],
    { label: string; variant: 'default' | 'secondary' | 'outline' }
> = {
    running: { label: 'In force', variant: 'default' },
    upcoming: { label: 'Coming up', variant: 'secondary' },
    past: { label: 'Finished', variant: 'outline' },
};

export default function AvailabilityIndex({
    blocks,
    rooms,
    reasons,
    stats,
}: Props) {
    const { can } = usePermissions();
    const canManage = can('availability.manage');

    const [pendingDelete, setPendingDelete] =
        useState<AvailabilityClosure | null>(null);

    const confirmDelete = () => {
        if (!pendingDelete) {
            return;
        }

        router.delete(availability.destroy.url(pendingDelete.id), {
            preserveScroll: true,
            onFinish: () => setPendingDelete(null),
        });
    };

    return (
        <>
            <Head title="Availability" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Availability"
                        description="Take a room off sale for servicing or house use, and put it back when it is ready."
                    />
                    {canManage && rooms.length > 0 && (
                        <CloseRoomDialog rooms={rooms} reasons={reasons} />
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        {
                            label: 'Closed tonight',
                            value: stats.closed_tonight,
                        },
                        { label: 'Still to come', value: stats.upcoming },
                        {
                            label: 'Out of service',
                            value: stats.out_of_service,
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

                <Card>
                    <CardContent className="pt-6">
                        {blocks.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Every room is on sale. Nothing has been closed.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Room</TableHead>
                                        <TableHead>First night</TableHead>
                                        <TableHead>Last night</TableHead>
                                        <TableHead className="text-right">
                                            Nights
                                        </TableHead>
                                        <TableHead>Reason</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {blocks.map((block) => {
                                        const badge = stateBadge[block.state];

                                        return (
                                            <TableRow key={block.id}>
                                                <TableCell className="font-medium">
                                                    {block.room ?? '—'}
                                                    <p className="text-xs text-muted-foreground">
                                                        {block.room_type ?? '—'}
                                                    </p>
                                                </TableCell>
                                                <TableCell>
                                                    {block.starts_label}
                                                </TableCell>
                                                <TableCell>
                                                    {block.ends_label}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {block.nights}
                                                </TableCell>
                                                <TableCell>
                                                    {block.reason_label}
                                                    {block.notes && (
                                                        <p className="max-w-xs text-xs text-muted-foreground">
                                                            {block.notes}
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={badge.variant}
                                                    >
                                                        {badge.label}
                                                    </Badge>
                                                    {block.created_by && (
                                                        <p className="pt-1 text-xs text-muted-foreground">
                                                            by{' '}
                                                            {block.created_by}
                                                        </p>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {canManage && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                setPendingDelete(
                                                                    block,
                                                                )
                                                            }
                                                        >
                                                            <RotateCcw />
                                                            Reopen
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={pendingDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPendingDelete(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Put {pendingDelete?.room ?? 'this room'} back on
                            sale?
                        </DialogTitle>
                        <DialogDescription>
                            The closure from {pendingDelete?.starts_label ?? ''}{' '}
                            to {pendingDelete?.ends_label ?? ''} is removed, and
                            the room can be booked for those nights again.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setPendingDelete(null)}
                        >
                            Keep it closed
                        </Button>
                        <Button onClick={confirmDelete}>
                            <RotateCcw />
                            Reopen the room
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/**
 * Closing a room for a range of nights.
 *
 * The dates are the nights themselves rather than an arrival and a departure,
 * because a closure has no guest: from the 10th to the 12th is two nights, and
 * that is what the desk is asked for.
 */
function CloseRoomDialog({
    rooms,
    reasons,
}: {
    rooms: SelectOption[];
    reasons: SelectOption[];
}) {
    const [open, setOpen] = useState(false);
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
        transform,
    } = useForm({
        room_id: '',
        starts_on: '',
        ends_on: '',
        reason: reasons[0]?.value ?? '',
        notes: '',
    });

    // A closure with a single night sends the same date twice, which is how the
    // server reads "one night" rather than having to infer it from an empty box.
    const submit = (event: FormEvent) => {
        event.preventDefault();

        transform((values) => ({
            ...values,
            ends_on: values.ends_on === '' ? values.starts_on : values.ends_on,
        }));

        post(availability.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    /*
     * Leaving the dialog has to put it back as it was found, whichever way it is
     * left. Radix only reports its own dismissals - Escape, a click outside, the
     * close cross - through onOpenChange, so the Cancel button cannot rely on
     * that and calls this directly.
     */
    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                if (next) {
                    setOpen(true);
                } else {
                    close();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    Close a room
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Close a room</DialogTitle>
                    <DialogDescription>
                        The room stops being sold and stops being offered for
                        these nights. A room a guest is already in cannot be
                        closed until they have been moved.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="room_id">Room</Label>
                        <Select
                            value={data.room_id}
                            onValueChange={(value) => setData('room_id', value)}
                        >
                            <SelectTrigger
                                id="room_id"
                                className="w-full"
                                aria-invalid={Boolean(errors.room_id)}
                            >
                                <SelectValue placeholder="Choose a room" />
                            </SelectTrigger>
                            <SelectContent>
                                {rooms.map((room) => (
                                    <SelectItem
                                        key={room.value}
                                        value={room.value}
                                    >
                                        {room.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.room_id} />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="starts_on">First night</Label>
                            <DatePicker
                                id="starts_on"
                                value={data.starts_on}
                                onChange={(value) =>
                                    setData('starts_on', value)
                                }
                                placeholder="Choose a date"
                            />
                            <InputError message={errors.starts_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ends_on">
                                Last night (optional)
                            </Label>
                            <DatePicker
                                id="ends_on"
                                value={data.ends_on}
                                onChange={(value) => setData('ends_on', value)}
                                placeholder="Same as the first night"
                            />
                            <InputError message={errors.ends_on} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="reason">Reason</Label>
                        <Select
                            value={data.reason}
                            onValueChange={(value) => setData('reason', value)}
                        >
                            <SelectTrigger id="reason" className="w-full">
                                <SelectValue placeholder="Choose a reason" />
                            </SelectTrigger>
                            <SelectContent>
                                {reasons.map((reason) => (
                                    <SelectItem
                                        key={reason.value}
                                        value={reason.value}
                                    >
                                        {reason.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.reason} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Note (optional)</Label>
                        <Textarea
                            id="notes"
                            rows={3}
                            value={data.notes}
                            onChange={(event) =>
                                setData('notes', event.target.value)
                            }
                            placeholder="What is being done, and who is doing it."
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Take it off sale
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
