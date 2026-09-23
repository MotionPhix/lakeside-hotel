import { Link } from '@inertiajs/react';
import {
    BedDouble,
    CalendarRange,
    Clock,
    Compass,
    MapPin,
    Quote,
    Ruler,
    Star,
    Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { AmenityIcon } from '@/components/public/amenity-icon';
import { formatLabel, formatMoney, formatPriceBasis } from '@/lib/format';
import rooms from '@/routes/site/rooms';
import type {
    ActivityData,
    AmenitySummary,
    ConferencePackageData,
    GalleryItemData,
    NearbyAttractionData,
    OfferData,
    RoomTypeSummary,
    TestimonialData,
} from '@/types';

/**
 * The placeholder shown when a record has no photograph yet. Keeps the layout
 * intact and reads as deliberate rather than broken.
 */
function ImagePlaceholder({ label }: { label?: string }) {
    return (
        <div className="flex size-full flex-col items-center justify-center gap-2 bg-sand text-navy/35">
            <Compass className="size-8" aria-hidden />
            {label && <span className="text-xs">{label}</span>}
        </div>
    );
}

function CardImage({
    src,
    alt,
    className,
    label,
}: {
    src: string | null;
    alt: string;
    className?: string;
    label?: string;
}) {
    if (!src) {
        return <ImagePlaceholder label={label} />;
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            decoding="async"
            className={`size-full object-cover transition-transform duration-700 group-hover:scale-105 ${className ?? ''}`}
        />
    );
}

export function RoomCard({ roomType }: { roomType: RoomTypeSummary }) {
    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-xl border border-navy/10 bg-white transition-shadow duration-300 hover:shadow-md">
            <Link
                href={rooms.show(roomType.slug)}
                className="relative block aspect-4/3 overflow-hidden bg-sand"
            >
                <CardImage
                    src={roomType.cover?.card ?? null}
                    alt={roomType.cover?.alt || roomType.name}
                    label={roomType.name}
                />
                {roomType.is_featured && (
                    <span className="absolute top-3 left-3 rounded-full bg-gold px-3 py-1 text-xs font-semibold text-navy">
                        Featured
                    </span>
                )}
            </Link>

            <div className="flex flex-1 flex-col p-5">
                <h3 className="font-display text-xl font-semibold text-navy">
                    <Link href={rooms.show(roomType.slug)}>
                        {roomType.name}
                    </Link>
                </h3>

                {roomType.tagline && (
                    <p className="mt-1.5 text-sm leading-relaxed text-navy/60">
                        {roomType.tagline}
                    </p>
                )}

                <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-navy/60">
                    <li className="flex items-center gap-1.5">
                        <Users className="size-3.5" aria-hidden />
                        Up to {roomType.max_occupancy} guests
                    </li>
                    {roomType.size_sqm !== null && (
                        <li className="flex items-center gap-1.5">
                            <Ruler className="size-3.5" aria-hidden />
                            {roomType.size_sqm} m²
                        </li>
                    )}
                    {roomType.bed_configuration && (
                        <li className="flex items-center gap-1.5">
                            <BedDouble className="size-3.5" aria-hidden />
                            {roomType.bed_configuration}
                        </li>
                    )}
                </ul>

                <div className="mt-auto flex items-end justify-between gap-3 pt-5">
                    <div>
                        <p className="text-xs text-navy/50">From</p>
                        <p className="font-display text-lg font-semibold text-lake">
                            {formatMoney(roomType.from_price)}
                            <span className="ml-1 text-xs font-normal text-navy/50">
                                / night
                            </span>
                        </p>
                    </div>
                    <Button asChild variant="outline" size="sm">
                        <Link href={rooms.show(roomType.slug)}>View room</Link>
                    </Button>
                </div>
            </div>
        </article>
    );
}

export function OfferCard({
    offer,
    tone = 'white',
}: {
    offer: OfferData;
    tone?: 'white' | 'navy';
}) {
    return (
        <article
            className={`group flex h-full flex-col overflow-hidden rounded-xl border transition-shadow duration-300 hover:shadow-md ${
                tone === 'navy'
                    ? 'border-white/15 bg-navy-deep/60'
                    : 'border-navy/10 bg-white'
            }`}
        >
            <div className="relative aspect-16/9 overflow-hidden bg-sand">
                <CardImage
                    src={offer.image?.card ?? null}
                    alt={offer.image?.alt || offer.title}
                    label={offer.title}
                />
                {offer.discount_label && (
                    <span className="absolute top-3 left-3 rounded-full bg-gold px-3 py-1 text-xs font-semibold text-navy">
                        {offer.discount_label}
                    </span>
                )}
            </div>

            <div className="flex flex-1 flex-col p-5">
                <p
                    className={`text-xs font-semibold tracking-[0.15em] uppercase ${
                        tone === 'navy' ? 'text-gold' : 'text-gold-dark'
                    }`}
                >
                    {formatLabel(offer.type)}
                </p>
                <h3
                    className={`mt-2 font-display text-xl font-semibold ${
                        tone === 'navy' ? 'text-white' : 'text-navy'
                    }`}
                >
                    {offer.title}
                </h3>
                {offer.subtitle && (
                    <p
                        className={`mt-1.5 text-sm ${
                            tone === 'navy' ? 'text-sand/70' : 'text-navy/60'
                        }`}
                    >
                        {offer.subtitle}
                    </p>
                )}
                <p
                    className={`mt-3 line-clamp-3 text-sm leading-relaxed ${
                        tone === 'navy' ? 'text-sand/75' : 'text-navy/70'
                    }`}
                >
                    {offer.description}
                </p>

                <div className="mt-auto pt-5">
                    {offer.coupon_code && (
                        <p
                            className={`mb-3 text-xs ${
                                tone === 'navy'
                                    ? 'text-sand/60'
                                    : 'text-navy/50'
                            }`}
                        >
                            Use code{' '}
                            <span
                                className={`font-semibold ${
                                    tone === 'navy' ? 'text-gold' : 'text-lake'
                                }`}
                            >
                                {offer.coupon_code}
                            </span>
                        </p>
                    )}
                    {offer.ends_on && (
                        <p
                            className={`mb-3 flex items-center gap-1.5 text-xs ${
                                tone === 'navy'
                                    ? 'text-sand/60'
                                    : 'text-navy/50'
                            }`}
                        >
                            <CalendarRange className="size-3.5" aria-hidden />
                            Until {offer.ends_on}
                        </p>
                    )}
                    <Button
                        asChild
                        variant={tone === 'navy' ? 'secondary' : 'default'}
                        size="sm"
                        className="w-full"
                    >
                        <Link href="/contact">Enquire about this offer</Link>
                    </Button>
                </div>
            </div>
        </article>
    );
}

export function TestimonialCard({
    testimonial,
}: {
    testimonial: TestimonialData;
}) {
    return (
        <figure className="flex h-full flex-col rounded-xl border border-navy/10 bg-white p-6 shadow-sm">
            <div className="flex items-center gap-1 text-gold">
                <span className="sr-only">{testimonial.rating} out of 5</span>
                {Array.from({ length: testimonial.rating }).map((_, index) => (
                    <Star
                        key={index}
                        className="size-4 fill-current"
                        aria-hidden
                    />
                ))}
            </div>

            {testimonial.title && (
                <figcaption className="mt-4 font-display text-lg font-semibold text-navy">
                    {testimonial.title}
                </figcaption>
            )}

            <blockquote className="mt-3 text-sm leading-relaxed text-navy/70">
                <Quote className="mb-2 size-5 text-gold/60" aria-hidden />
                {testimonial.quote}
            </blockquote>

            {testimonial.response && (
                <div className="mt-4 rounded-lg bg-mist p-3 text-xs leading-relaxed text-navy/70">
                    <p className="mb-1 font-semibold text-lake">
                        Lakeside Hotel replied
                    </p>
                    {testimonial.response}
                </div>
            )}

            <footer className="mt-5 border-t border-navy/10 pt-4 text-xs text-navy/55">
                <p className="font-medium text-navy">
                    {testimonial.guest_name}
                </p>
                <p>
                    {[testimonial.guest_country, testimonial.stayed_on]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </footer>
        </figure>
    );
}

export function ActivityCard({ activity }: { activity: ActivityData }) {
    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-xl border border-navy/10 bg-white transition-shadow duration-300 hover:shadow-md">
            <div className="relative aspect-16/10 overflow-hidden bg-sand">
                <CardImage
                    src={activity.cover?.card ?? null}
                    alt={activity.cover?.alt || activity.name}
                    label={activity.name}
                />
            </div>
            <div className="flex flex-1 flex-col p-5">
                <h3 className="font-display text-lg font-semibold text-navy">
                    {activity.name}
                </h3>
                <p className="mt-2 line-clamp-3 text-sm leading-relaxed text-navy/65">
                    {activity.description}
                </p>

                <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-navy/60">
                    {activity.duration && (
                        <li className="flex items-center gap-1.5">
                            <Clock className="size-3.5" aria-hidden />
                            {activity.duration}
                        </li>
                    )}
                    {activity.max_participants !== null && (
                        <li className="flex items-center gap-1.5">
                            <Users className="size-3.5" aria-hidden />
                            Up to {activity.max_participants}
                        </li>
                    )}
                </ul>

                <p className="mt-auto pt-5 font-display text-lg font-semibold text-lake">
                    {activity.is_complimentary
                        ? 'Complimentary'
                        : `${formatMoney(activity.price)} `}
                    {!activity.is_complimentary && (
                        <span className="text-xs font-normal text-navy/50">
                            {formatPriceBasis(activity.price_basis)}
                        </span>
                    )}
                </p>
            </div>
        </article>
    );
}

export function PackageCard({ pkg }: { pkg: ConferencePackageData }) {
    return (
        <article className="flex h-full flex-col rounded-xl border border-navy/10 bg-white p-6">
            <span className="text-xs font-semibold tracking-[0.15em] text-gold-dark uppercase">
                {formatLabel(pkg.type)}
            </span>
            <h3 className="mt-2 font-display text-xl font-semibold text-navy">
                {pkg.name}
            </h3>
            {pkg.tagline && (
                <p className="mt-1.5 text-sm text-navy/60">{pkg.tagline}</p>
            )}
            <p className="mt-3 text-sm leading-relaxed text-navy/70">
                {pkg.description}
            </p>

            {pkg.includes.length > 0 && (
                <ul className="mt-5 space-y-2 text-sm">
                    {pkg.includes.map((item) => (
                        <li key={item} className="flex gap-2.5 text-navy/75">
                            <span
                                aria-hidden
                                className="mt-2 size-1.5 shrink-0 rounded-full bg-gold"
                            />
                            {item}
                        </li>
                    ))}
                </ul>
            )}

            <div className="mt-auto flex items-end justify-between gap-4 border-t border-navy/10 pt-5">
                <div>
                    <p className="font-display text-lg font-semibold text-lake">
                        {formatMoney(pkg.price)}
                    </p>
                    <p className="text-xs text-navy/50">
                        {formatPriceBasis(pkg.price_basis)}
                    </p>
                    {pkg.capacity_label && (
                        <p className="mt-1 text-xs text-navy/50">
                            {pkg.capacity_label}
                        </p>
                    )}
                </div>
                <Button asChild size="sm">
                    <Link href="/contact">Request a quote</Link>
                </Button>
            </div>
        </article>
    );
}

export function AmenityTile({ amenity }: { amenity: AmenitySummary }) {
    return (
        <div className="flex items-start gap-4 rounded-xl border border-navy/10 bg-white p-5 transition-colors duration-300 hover:border-lake/30">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-lake-light text-lake">
                <AmenityIcon name={amenity.icon} className="size-5" />
            </span>
            <div>
                <h3 className="font-medium text-navy">{amenity.name}</h3>
                {amenity.description && (
                    <p className="mt-1 text-sm leading-relaxed text-navy/60">
                        {amenity.description}
                    </p>
                )}
            </div>
        </div>
    );
}

export function AttractionCard({
    attraction,
}: {
    attraction: NearbyAttractionData;
}) {
    return (
        <div className="flex gap-4 rounded-xl border border-navy/10 bg-white p-5">
            <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-sand text-lake">
                <MapPin className="size-5" aria-hidden />
            </span>
            <div>
                <h3 className="font-medium text-navy">{attraction.name}</h3>
                {attraction.distance_km && (
                    <p className="mt-0.5 text-xs text-navy/50">
                        {attraction.distance_km} km
                        {attraction.travel_time_minutes
                            ? ` · about ${attraction.travel_time_minutes} minutes`
                            : ''}
                    </p>
                )}
                {attraction.description && (
                    <p className="mt-2 text-sm leading-relaxed text-navy/65">
                        {attraction.description}
                    </p>
                )}
            </div>
        </div>
    );
}

export function GalleryTile({ item }: { item: GalleryItemData }) {
    return (
        <figure className="group relative overflow-hidden rounded-lg bg-sand">
            <CardImage
                src={item.image?.card ?? null}
                alt={item.image?.alt || item.title || 'Lakeside Hotel'}
                label={item.title ?? undefined}
                className="aspect-4/3"
            />
            {(item.title || item.caption) && (
                <figcaption className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-navy/85 to-transparent p-4 pt-10 text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    {item.title && (
                        <p className="font-display font-semibold">
                            {item.title}
                        </p>
                    )}
                    {item.caption && (
                        <p className="mt-0.5 text-xs text-white/80">
                            {item.caption}
                        </p>
                    )}
                </figcaption>
            )}
        </figure>
    );
}
