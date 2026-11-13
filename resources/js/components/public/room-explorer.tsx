import { Link } from '@inertiajs/react';
import { ArrowRight, BedDouble, Check, Ruler, Users, X } from 'lucide-react';
import { useId, useState } from 'react';
import {
    ResponsiveImage,
    imageBoxes,
} from '@/components/public/responsive-image';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import { cn } from '@/lib/utils';
import rooms from '@/routes/site/rooms';
import booking from '@/routes/site/booking';
import type { RoomTypeDetail } from '@/types';

const price = (room: RoomTypeDetail) =>
    Number(room.from?.amount ?? room.from_price);

export function RoomExplorer({ roomTypes }: { roomTypes: RoomTypeDetail[] }) {
    const id = useId();
    const [adults, setAdults] = useState(0);
    const [children, setChildren] = useState(0);
    const [sort, setSort] = useState('recommended');
    const [selected, setSelected] = useState<number[]>([]);
    const visible = roomTypes.filter(
        (room) =>
            room.capacity_adults >= adults &&
            room.capacity_children >= children &&
            room.max_occupancy >= adults + children,
    );
    if (sort === 'price-low') visible.sort((a, b) => price(a) - price(b));
    if (sort === 'price-high') visible.sort((a, b) => price(b) - price(a));
    if (sort === 'space')
        visible.sort((a, b) => (b.size_sqm ?? 0) - (a.size_sqm ?? 0));
    const compared = selected
        .map((key) => roomTypes.find((room) => room.id === key))
        .filter((room): room is RoomTypeDetail => Boolean(room));
    const maxAdults = Math.max(
        1,
        ...roomTypes.map((room) => room.capacity_adults),
    );
    const maxChildren = Math.max(
        0,
        ...roomTypes.map((room) => room.capacity_children),
    );

    function toggle(key: number) {
        setSelected((current) =>
            current.includes(key)
                ? current.filter((value) => value !== key)
                : current.length < 3
                  ? [...current, key]
                  : current,
        );
    }

    const fieldClass =
        'mt-2 h-11 w-full rounded-lg border border-navy/20 bg-white px-3 text-base text-navy focus-visible:ring-2 focus-visible:ring-lake focus-visible:outline-none sm:text-sm';
    return (
        <div>
            <div className="rounded-xl border border-navy/10 bg-sand/50 p-5 sm:p-6">
                <div className="grid gap-4 sm:grid-cols-3">
                    <label
                        htmlFor={`${id}-adults`}
                        className="text-sm font-medium text-navy"
                    >
                        Adults
                        <select
                            id={`${id}-adults`}
                            value={adults}
                            onChange={(event) =>
                                setAdults(Number(event.target.value))
                            }
                            className={fieldClass}
                        >
                            <option value={0}>Any number</option>
                            {Array.from(
                                { length: maxAdults },
                                (_, index) => index + 1,
                            ).map((value) => (
                                <option key={value} value={value}>
                                    {value} {value === 1 ? 'adult' : 'adults'}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label
                        htmlFor={`${id}-children`}
                        className="text-sm font-medium text-navy"
                    >
                        Children
                        <select
                            id={`${id}-children`}
                            value={children}
                            onChange={(event) =>
                                setChildren(Number(event.target.value))
                            }
                            className={fieldClass}
                        >
                            <option value={0}>No preference</option>
                            {Array.from(
                                { length: maxChildren },
                                (_, index) => index + 1,
                            ).map((value) => (
                                <option key={value} value={value}>
                                    {value} {value === 1 ? 'child' : 'children'}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label
                        htmlFor={`${id}-sort`}
                        className="text-sm font-medium text-navy"
                    >
                        Sort rooms
                        <select
                            id={`${id}-sort`}
                            value={sort}
                            onChange={(event) => setSort(event.target.value)}
                            className={fieldClass}
                        >
                            <option value="recommended">Hotel order</option>
                            <option value="price-low">
                                Price: low to high
                            </option>
                            <option value="price-high">
                                Price: high to low
                            </option>
                            <option value="space">Largest rooms first</option>
                        </select>
                    </label>
                </div>
                <p className="mt-4 text-xs leading-relaxed text-navy/70">
                    Filter by your party, then check availability for your
                    travel dates. Rates shown include VAT and the tourism levy.
                </p>
            </div>
            <div className="my-7 flex flex-wrap items-center justify-between gap-3">
                <p role="status" className="text-sm text-navy/70">
                    {visible.length} of {roomTypes.length} room categories
                </p>
                <p className="text-sm text-navy/70">
                    Choose up to 3 rooms to compare.
                </p>
            </div>
            {selected.length > 0 && (
                <div className="mb-7 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-lake/20 bg-lake-light p-4">
                    <p className="text-sm font-medium text-navy">
                        {selected.length}{' '}
                        {selected.length === 1
                            ? 'room selected'
                            : 'rooms selected'}
                    </p>
                    <div className="flex items-center gap-4">
                        <a
                            href={`#${id}-comparison`}
                            className="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-lake underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-lake"
                        >
                            Compare selected{' '}
                            <ArrowRight className="size-4" aria-hidden />
                        </a>
                        <button
                            type="button"
                            onClick={() => setSelected([])}
                            className="min-h-11 cursor-pointer px-2 text-sm text-navy/70 hover:text-navy"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            )}
            <div className="grid gap-7 lg:grid-cols-2">
                {visible.map((room) => {
                    const checked = selected.includes(room.id);
                    return (
                        <article
                            key={room.id}
                            className="flex min-w-0 flex-col overflow-hidden rounded-xl border border-navy/10 bg-white"
                        >
                            <div className="relative aspect-16/10 bg-sand">
                                <Link
                                    href={rooms.show(room.slug)}
                                    className="block size-full"
                                    aria-label={`View ${room.name}`}
                                >
                                    {room.cover && (
                                        <ResponsiveImage
                                            image={room.cover}
                                            sizes={imageBoxes.half}
                                            alt={room.cover.alt || room.name}
                                            className="size-full object-cover"
                                        />
                                    )}
                                </Link>
                                <button
                                    type="button"
                                    aria-pressed={checked}
                                    aria-label={`Compare ${room.name}`}
                                    disabled={!checked && selected.length >= 3}
                                    onClick={() => toggle(room.id)}
                                    className={cn(
                                        'absolute top-3 right-3 inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 text-sm font-medium shadow-sm focus-visible:ring-2 focus-visible:ring-lake focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                                        checked
                                            ? 'border-navy bg-navy text-white'
                                            : 'border-white/70 bg-white text-navy',
                                    )}
                                >
                                    {checked && (
                                        <Check className="size-4" aria-hidden />
                                    )}{' '}
                                    {checked ? 'Selected' : 'Compare'}
                                </button>
                            </div>
                            <div className="flex flex-1 flex-col p-5 sm:p-7">
                                <h3 className="font-display text-2xl font-semibold text-navy">
                                    <Link href={rooms.show(room.slug)}>
                                        {room.name}
                                    </Link>
                                </h3>
                                {room.tagline && (
                                    <p className="mt-2 text-sm leading-relaxed text-navy/70">
                                        {room.tagline}
                                    </p>
                                )}
                                <dl className="my-6 grid grid-cols-2 gap-4 border-y border-navy/10 py-5 text-sm">
                                    <div>
                                        <dt className="flex items-center gap-2 text-navy/65">
                                            <Users
                                                className="size-4"
                                                aria-hidden
                                            />
                                            Sleeps up to
                                        </dt>
                                        <dd className="mt-1 font-medium text-navy">
                                            {room.max_occupancy} guests
                                        </dd>
                                        <dd className="mt-1 text-xs text-navy/65">
                                            Up to {room.capacity_adults} adults
                                            {room.capacity_children > 0
                                                ? ` · ${room.capacity_children} children`
                                                : ''}
                                        </dd>
                                    </div>
                                    {room.size_sqm !== null && (
                                        <div>
                                            <dt className="flex items-center gap-2 text-navy/65">
                                                <Ruler
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                                Room size
                                            </dt>
                                            <dd className="mt-1 font-medium text-navy">
                                                {room.size_sqm} m²
                                            </dd>
                                        </div>
                                    )}
                                    {room.bed_configuration && (
                                        <div className="col-span-2">
                                            <dt className="flex items-center gap-2 text-navy/65">
                                                <BedDouble
                                                    className="size-4"
                                                    aria-hidden
                                                />
                                                Beds
                                            </dt>
                                            <dd className="mt-1 font-medium text-navy">
                                                {room.bed_configuration}
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                                {room.amenities.length > 0 && (
                                    <ul className="mb-6 flex flex-wrap gap-2">
                                        {room.amenities
                                            .slice(0, 4)
                                            .map((amenity) => (
                                                <li
                                                    key={amenity.id}
                                                    className="rounded-md bg-sand/70 px-2.5 py-1.5 text-xs text-navy/80"
                                                >
                                                    {amenity.name}
                                                </li>
                                            ))}
                                    </ul>
                                )}
                                <div className="mt-auto border-t border-navy/10 pt-5">
                                    <p className="text-xs text-navy/65">
                                        From, per night
                                    </p>
                                    <p className="mt-1 font-display text-2xl font-semibold text-lake">
                                        {formatMoney(String(price(room)))}
                                    </p>
                                    <p className="mt-1 text-xs text-navy/65">
                                        Breakfast, VAT &amp; tourism levy
                                        included
                                    </p>
                                    <div className="mt-5 grid gap-3 sm:grid-cols-2">
                                        <Button asChild size="lg">
                                            <Link
                                                href={booking.index.url({
                                                    query: {
                                                        room_type: room.slug,
                                                    },
                                                })}
                                            >
                                                Check availability
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            size="lg"
                                            variant="outline"
                                        >
                                            <Link href={rooms.show(room.slug)}>
                                                Explore this room
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    );
                })}
            </div>
            {visible.length === 0 && (
                <div className="rounded-xl border border-navy/10 bg-sand/40 px-6 py-12 text-center">
                    <h3 className="font-display text-2xl text-navy">
                        No single room matches this party
                    </h3>
                    <p className="mx-auto mt-3 max-w-md text-sm leading-relaxed text-navy/70">
                        Try different guest numbers, or contact us about booking
                        more than one room.
                    </p>
                    <button
                        type="button"
                        onClick={() => {
                            setAdults(0);
                            setChildren(0);
                        }}
                        className="mt-5 min-h-11 cursor-pointer rounded-lg border border-navy/20 px-5 text-sm text-navy hover:bg-sand"
                    >
                        Show all rooms
                    </button>
                </div>
            )}
            {compared.length > 0 && (
                <section
                    id={`${id}-comparison`}
                    className="mt-14 scroll-mt-28"
                    aria-labelledby={`${id}-comparison-title`}
                >
                    <h2
                        id={`${id}-comparison-title`}
                        className="font-display text-3xl font-semibold text-navy"
                    >
                        Your rooms, side by side
                    </h2>
                    <p className="mt-3 text-sm text-navy/70">
                        Compare the essentials. Check your dates for the final
                        stay total.
                    </p>
                    <div className="mt-7 grid gap-5 md:grid-cols-3">
                        {compared.map((room) => (
                            <article
                                key={room.id}
                                className="min-w-0 rounded-xl border border-navy/15 bg-sand/50 p-5"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h3 className="font-display text-xl font-semibold text-navy">
                                        {room.name}
                                    </h3>
                                    <button
                                        type="button"
                                        onClick={() => toggle(room.id)}
                                        aria-label={`Remove ${room.name} from comparison`}
                                        className="-mt-2 -mr-2 flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-lg text-navy/70 hover:bg-sand focus-visible:ring-2 focus-visible:ring-lake"
                                    >
                                        <X className="size-4" aria-hidden />
                                    </button>
                                </div>
                                <dl className="mt-5 space-y-4 text-sm">
                                    {[
                                        [
                                            'From per night, incl. taxes',
                                            formatMoney(String(price(room))),
                                        ],
                                        [
                                            'Capacity',
                                            `${room.capacity_adults} adults · ${room.capacity_children} children`,
                                        ],
                                        [
                                            'Room size',
                                            room.size_sqm === null
                                                ? 'Not specified'
                                                : `${room.size_sqm} m²`,
                                        ],
                                        [
                                            'Beds',
                                            room.bed_configuration ??
                                                'Ask reception',
                                        ],
                                        [
                                            'Minimum stay',
                                            `${room.min_nights} ${room.min_nights === 1 ? 'night' : 'nights'}`,
                                        ],
                                    ].map(([label, value]) => (
                                        <div
                                            key={label}
                                            className="border-t border-navy/10 pt-3"
                                        >
                                            <dt className="text-xs text-navy/65">
                                                {label}
                                            </dt>
                                            <dd className="mt-1 font-medium text-navy">
                                                {value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                                <ul className="mt-5 space-y-2 text-xs text-navy/75">
                                    {room.amenities.map((amenity) => (
                                        <li
                                            key={amenity.id}
                                            className="flex items-start gap-2"
                                        >
                                            <Check
                                                className="size-3.5 shrink-0 text-lake"
                                                aria-hidden
                                            />
                                            {amenity.name}
                                        </li>
                                    ))}
                                </ul>
                                <Button asChild className="mt-6 w-full">
                                    <Link
                                        href={booking.index.url({
                                            query: { room_type: room.slug },
                                        })}
                                    >
                                        Check availability
                                    </Link>
                                </Button>
                            </article>
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
}
