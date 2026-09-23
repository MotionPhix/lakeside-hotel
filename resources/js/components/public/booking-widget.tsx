import { router, usePage } from '@inertiajs/react';
import { CalendarDays, Search, Users } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { whatsappLink } from '@/lib/site-nav';
import siteRoutes from '@/routes/site';
import type { SharedData } from '@/types';

type BookableRoom = {
    slug: string;
    name: string;
};

const fieldClass =
    'border-navy/15 h-11 w-full rounded-md border bg-white px-3 text-sm text-navy outline-none transition-colors focus-visible:border-lake focus-visible:ring-2 focus-visible:ring-lake/30';

/**
 * The quick booking bar on the homepage: dates, party size and room preference.
 *
 * Until the online reservation flow ships, the request is handed to WhatsApp with
 * every detail already filled in, which is the channel the hotel actually answers
 * on. `resources/js/pages/public/booking.tsx` replaces this later.
 */
export function BookingWidget({ roomTypes }: { roomTypes: BookableRoom[] }) {
    const { site } = usePage<SharedData>().props;

    const [checkIn, setCheckIn] = useState('');
    const [checkOut, setCheckOut] = useState('');
    const [adults, setAdults] = useState('2');
    const [children, setChildren] = useState('0');
    const [roomSlug, setRoomSlug] = useState('');

    const today = new Date().toISOString().slice(0, 10);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const chosen = roomTypes.find((room) => room.slug === roomSlug);

        const message = [
            `Hello ${site.name}, I would like to check availability.`,
            checkIn && `Check in: ${checkIn}`,
            checkOut && `Check out: ${checkOut}`,
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
                <div className="grid gap-1.5 lg:col-span-3">
                    <Label htmlFor="check_in" className="text-xs text-navy/60">
                        Check in
                    </Label>
                    <input
                        id="check_in"
                        type="date"
                        min={today}
                        value={checkIn}
                        onChange={(event) => setCheckIn(event.target.value)}
                        className={fieldClass}
                    />
                </div>

                <div className="grid gap-1.5 lg:col-span-3">
                    <Label htmlFor="check_out" className="text-xs text-navy/60">
                        Check out
                    </Label>
                    <input
                        id="check_out"
                        type="date"
                        min={checkIn || today}
                        value={checkOut}
                        onChange={(event) => setCheckOut(event.target.value)}
                        className={fieldClass}
                    />
                </div>

                <div className="grid gap-1.5 lg:col-span-2">
                    <Label htmlFor="adults" className="text-xs text-navy/60">
                        Guests
                    </Label>
                    <div className="flex gap-2">
                        <select
                            id="adults"
                            value={adults}
                            onChange={(event) => setAdults(event.target.value)}
                            className={fieldClass}
                            aria-label="Adults"
                        >
                            {[1, 2, 3, 4, 5, 6].map((count) => (
                                <option key={count} value={count}>
                                    {count} adult{count === 1 ? '' : 's'}
                                </option>
                            ))}
                        </select>
                        <select
                            value={children}
                            onChange={(event) =>
                                setChildren(event.target.value)
                            }
                            className={fieldClass}
                            aria-label="Children"
                        >
                            {[0, 1, 2, 3, 4].map((count) => (
                                <option key={count} value={count}>
                                    {count} child{count === 1 ? '' : 'ren'}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="grid gap-1.5 lg:col-span-2">
                    <Label htmlFor="room_slug" className="text-xs text-navy/60">
                        Room type
                    </Label>
                    <select
                        id="room_slug"
                        value={roomSlug}
                        onChange={(event) => setRoomSlug(event.target.value)}
                        className={fieldClass}
                    >
                        <option value="">Any room</option>
                        {roomTypes.map((room) => (
                            <option key={room.slug} value={room.slug}>
                                {room.name}
                            </option>
                        ))}
                    </select>
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
