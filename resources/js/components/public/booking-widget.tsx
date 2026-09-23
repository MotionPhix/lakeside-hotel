import { router, usePage } from '@inertiajs/react';
import { CalendarDays, Search, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DateRangePicker } from '@/components/date-picker';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { whatsappLink } from '@/lib/site-nav';
import siteRoutes from '@/routes/site';
import type { SharedData } from '@/types';

type BookableRoom = {
    slug: string;
    name: string;
};

/** shadcn's Select reserves the empty string, so "no preference" needs a value. */
const ANY_ROOM = 'any';

/**
 * The field styling shared by every control in the booking bar, so the date
 * picker and the selects sit level with each other.
 */
const fieldClass =
    'border-navy/15 h-11 w-full rounded-md bg-white text-sm text-navy';

/**
 * The quick booking bar on the homepage: dates, party size and room preference.
 *
 * Until the online reservation flow ships, the request is handed to WhatsApp with
 * every detail already filled in, which is the channel the hotel actually answers
 * on. The dates use the shadcn date range picker and the party controls use the
 * shadcn select, so this matches the rest of the interface.
 */
export function BookingWidget({ roomTypes }: { roomTypes: BookableRoom[] }) {
    const { site } = usePage<SharedData>().props;

    const [range, setRange] = useState({ from: '', to: '' });
    const [adults, setAdults] = useState('2');
    const [children, setChildren] = useState('0');
    const [roomSlug, setRoomSlug] = useState(ANY_ROOM);

    const today = new Date().toISOString().slice(0, 10);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const chosen = roomTypes.find((room) => room.slug === roomSlug);

        const message = [
            `Hello ${site.name}, I would like to check availability.`,
            range.from && `Check in: ${range.from}`,
            range.to && `Check out: ${range.to}`,
            `Guests: ${adults} adult${adults === '1' ? '' : 's'}${
                children !== '0'
                    ? ` and ${children} child${children === '1' ? '' : 'ren'}`
                    : ''
            }`,
            chosen && `Preferred room: ${chosen.name}`,
        ]
            .filter(Boolean)
            .join('\n');

        const link = whatsappLink(site, message);

        if (link) {
            window.open(link, '_blank', 'noopener');

            return;
        }

        router.visit(siteRoutes.contact());
    };

    return (
        <form
            onSubmit={submit}
            className="rounded-xl border border-navy/10 bg-white/95 p-4 shadow-xl backdrop-blur sm:p-5"
        >
            <div className="grid gap-4 lg:grid-cols-12">
                <div className="grid gap-1.5 lg:col-span-4">
                    <Label className="text-xs text-navy/60">
                        Check in – check out
                    </Label>
                    <DateRangePicker
                        from={range.from}
                        to={range.to}
                        onChange={setRange}
                        min={today}
                        placeholder="Choose your dates"
                        className={fieldClass}
                    />
                </div>

                <div className="grid gap-1.5 lg:col-span-3">
                    <Label className="text-xs text-navy/60">Guests</Label>
                    <div className="grid grid-cols-2 gap-2">
                        <Select value={adults} onValueChange={setAdults}>
                            <SelectTrigger
                                className={fieldClass}
                                aria-label="Adults"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {[1, 2, 3, 4, 5, 6].map((count) => (
                                    <SelectItem
                                        key={count}
                                        value={String(count)}
                                    >
                                        {count}{' '}
                                        {count === 1 ? 'adult' : 'adults'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select value={children} onValueChange={setChildren}>
                            <SelectTrigger
                                className={fieldClass}
                                aria-label="Children"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {[0, 1, 2, 3, 4].map((count) => (
                                    <SelectItem
                                        key={count}
                                        value={String(count)}
                                    >
                                        {count}{' '}
                                        {count === 1 ? 'child' : 'children'}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-1.5 lg:col-span-3">
                    <Label htmlFor="room_slug" className="text-xs text-navy/60">
                        Room type
                    </Label>
                    <Select value={roomSlug} onValueChange={setRoomSlug}>
                        <SelectTrigger id="room_slug" className={fieldClass}>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ANY_ROOM}>Any room</SelectItem>
                            {roomTypes.map((room) => (
                                <SelectItem key={room.slug} value={room.slug}>
                                    {room.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="flex items-end lg:col-span-2">
                    <Button type="submit" size="lg" className="h-11 w-full">
                        <Search />
                        Check Availability
                    </Button>
                </div>
            </div>

            <p className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-navy/55">
                <span className="flex items-center gap-1.5">
                    <CalendarDays className="size-3.5" aria-hidden />
                    Check in from {site.contact.check_in_time}
                </span>
                <span className="flex items-center gap-1.5">
                    <Users className="size-3.5" aria-hidden />
                    Best rate guaranteed when you book direct
                </span>
            </p>
        </form>
    );
}
