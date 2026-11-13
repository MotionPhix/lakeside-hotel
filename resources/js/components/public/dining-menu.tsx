import { Leaf, Search, Star, X } from 'lucide-react';
import { useId, useState } from 'react';
import { cn } from '@/lib/utils';
import { formatMoney } from '@/lib/format';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { DiningVenueData, MenuItemData } from '@/types';

function MenuDish({ dish }: { dish: MenuItemData }) {
    return (
        <li className="min-w-0 border-b border-navy/10 py-5">
            <div className="grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-4">
                <p className="min-w-0 text-base leading-relaxed font-medium break-words text-navy">
                    {dish.name}
                </p>
                <p className="text-sm font-medium whitespace-nowrap text-navy tabular-nums sm:text-base">
                    {formatMoney(dish.price)}
                </p>
            </div>
            {dish.description && (
                <p className="mt-1.5 max-w-lg text-sm leading-relaxed text-navy/70">
                    {dish.description}
                </p>
            )}
            {(dish.is_vegetarian || dish.is_signature) && (
                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-medium">
                    {dish.is_vegetarian && (
                        <span className="inline-flex items-center gap-1.5 text-lake">
                            <Leaf className="size-3.5" aria-hidden />
                            Vegetarian
                        </span>
                    )}
                    {dish.is_signature && (
                        <span className="inline-flex items-center gap-1.5 text-navy/70">
                            <Star
                                className="size-3.5 text-gold-dark"
                                aria-hidden
                            />
                            House favourite
                        </span>
                    )}
                </div>
            )}
        </li>
    );
}

/** The kitchen's existing menu, with one readable category at a time. */
export function DiningMenu({ venue }: { venue: DiningVenueData }) {
    const id = useId();
    // Open on the full menu. Landing on the first category showed a visitor
    // three salads while the copy invited them to browse everything.
    const [category, setCategory] = useState('all');
    const [search, setSearch] = useState('');
    const [vegetarianOnly, setVegetarianOnly] = useState(false);
    const query = search.trim().toLocaleLowerCase();
    const selectedCategory = venue.menu.some(
        (section) => section.key === category,
    )
        ? category
        : 'all';
    const visibleSections = venue.menu
        .filter(
            (section) =>
                query !== '' ||
                selectedCategory === 'all' ||
                section.key === selectedCategory,
        )
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (dish) =>
                    (!vegetarianOnly || dish.is_vegetarian) &&
                    (query === '' ||
                        `${dish.name} ${dish.description ?? ''} ${section.label}`
                            .toLocaleLowerCase()
                            .includes(query)),
            ),
        }))
        .filter((section) => section.items.length > 0);
    const resultCount = visibleSections.reduce(
        (total, section) => total + section.items.length,
        0,
    );

    function chooseCategory(value: string) {
        setCategory(value);
        setSearch('');
    }

    function reset() {
        setSearch('');
        setVegetarianOnly(false);
        setCategory(venue.menu[0]?.key ?? 'all');
    }

    return (
        <div className="mt-16 min-w-0 border-t border-navy/10 pt-12">
            <div className="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <span
                        className="mb-4 block h-px w-12 bg-gold"
                        aria-hidden
                    />
                    <h3
                        id={`${id}-title`}
                        className="font-display text-3xl font-medium text-navy sm:text-4xl"
                    >
                        The menu
                    </h3>
                    <p className="mt-3 text-sm leading-relaxed text-navy/70">
                        Choose a category, or find a favourite across the full
                        menu.
                    </p>
                </div>
                {venue.menu_note && (
                    <p className="text-sm text-navy/70">{venue.menu_note}</p>
                )}
            </div>

            <div className="min-w-0 overflow-hidden rounded-2xl border border-navy/10 bg-white">
                <div className="border-b border-navy/10 bg-sand/35 p-5 sm:p-8">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
                        <div className="w-full sm:max-w-md">
                            <label
                                htmlFor={`${id}-search`}
                                className="mb-2 block text-xs font-semibold tracking-wider text-navy/70 uppercase"
                            >
                                Find a dish
                            </label>
                            <div className="relative">
                                <Search
                                    className="pointer-events-none absolute top-3.5 left-3.5 size-4 text-navy/60"
                                    aria-hidden
                                />
                                <input
                                    id={`${id}-search`}
                                    type="search"
                                    value={search}
                                    onChange={(event) =>
                                        setSearch(event.target.value)
                                    }
                                    placeholder="Try chambo, pasta or something sweet…"
                                    autoComplete="off"
                                    aria-controls={`${id}-results`}
                                    className="h-11 w-full min-w-0 rounded-lg border border-navy/20 bg-white pr-11 pl-10 text-base text-navy outline-none placeholder:text-navy/55 focus-visible:border-lake focus-visible:ring-2 focus-visible:ring-lake/30 sm:text-sm"
                                />
                                {search && (
                                    <button
                                        type="button"
                                        onClick={() => setSearch('')}
                                        aria-label="Clear dish search"
                                        className="absolute inset-y-0 right-0 flex w-11 cursor-pointer items-center justify-center rounded-r-lg text-navy/70 hover:text-lake focus-visible:ring-2 focus-visible:ring-lake focus-visible:outline-none"
                                    >
                                        <X className="size-4" aria-hidden />
                                    </button>
                                )}
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setVegetarianOnly(!vegetarianOnly)}
                            aria-pressed={vegetarianOnly}
                            aria-controls={`${id}-results`}
                            className={cn(
                                'inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-4 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-lake focus-visible:ring-offset-2 focus-visible:outline-none',
                                vegetarianOnly
                                    ? 'border-lake bg-lake text-white'
                                    : 'border-navy/20 bg-white text-navy hover:border-lake hover:text-lake',
                            )}
                        >
                            <Leaf className="size-4" aria-hidden />
                            Vegetarian options
                        </button>
                    </div>

                    <div className="mt-6 sm:hidden">
                        <label
                            htmlFor={`${id}-category`}
                            className="mb-2 block text-xs font-semibold tracking-wider text-navy/70 uppercase"
                        >
                            Browse the menu
                        </label>
                        <Select
                            value={query ? 'all' : selectedCategory}
                            onValueChange={chooseCategory}
                        >
                            <SelectTrigger
                                id={`${id}-category`}
                                aria-controls={`${id}-results`}
                                /* The trigger ships at 36px; this is the one
                                   control a thumb has to hit on a phone, so it
                                   keeps the 44px the native select had. */
                                className="min-h-11 w-full min-w-0 rounded-lg border-navy/20 bg-white px-3 text-base text-navy focus-visible:ring-2 focus-visible:ring-lake focus-visible:outline-none data-[size=default]:h-11"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All dishes</SelectItem>
                                {venue.menu.map((section) => (
                                    <SelectItem
                                        key={section.key}
                                        value={section.key}
                                    >
                                        {section.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div
                        className="mt-6 hidden flex-wrap gap-2 sm:flex"
                        role="group"
                        aria-label={`Menu categories for ${venue.name}`}
                    >
                        {[
                            { key: 'all', label: 'All dishes' },
                            ...venue.menu,
                        ].map((section) => (
                            <button
                                key={section.key}
                                type="button"
                                onClick={() => chooseCategory(section.key)}
                                aria-pressed={
                                    query
                                        ? section.key === 'all'
                                        : section.key === selectedCategory
                                }
                                aria-controls={`${id}-results`}
                                className={cn(
                                    'min-h-11 cursor-pointer rounded-lg border px-3.5 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-lake focus-visible:ring-offset-2 focus-visible:outline-none',
                                    (
                                        query
                                            ? section.key === 'all'
                                            : section.key === selectedCategory
                                    )
                                        ? 'border-navy bg-navy text-white'
                                        : 'border-transparent text-navy/75 hover:border-navy/15 hover:bg-white hover:text-navy',
                                )}
                            >
                                {section.label}
                            </button>
                        ))}
                    </div>
                </div>

                <div
                    id={`${id}-results`}
                    role="region"
                    aria-labelledby={`${id}-title`}
                    className="p-5 sm:p-8 lg:p-10"
                >
                    <p
                        role="status"
                        className="mb-6 text-xs font-medium tracking-wide text-navy/65"
                    >
                        {query ? `Results for “${search.trim()}” · ` : ''}
                        {resultCount} {resultCount === 1 ? 'dish' : 'dishes'}
                        {vegetarianOnly ? ' · Vegetarian' : ''}
                    </p>
                    <div className="space-y-10">
                        {visibleSections.map((section) => (
                            <section
                                key={section.key}
                                aria-labelledby={`${id}-${section.key}`}
                            >
                                <div className="border-b border-gold/50 pb-4">
                                    <h4
                                        id={`${id}-${section.key}`}
                                        className="font-display text-2xl font-medium text-navy sm:text-3xl"
                                    >
                                        {section.label}
                                    </h4>
                                    {section.note && (
                                        <p className="mt-2 text-sm leading-relaxed text-navy/70">
                                            {section.note}
                                        </p>
                                    )}
                                </div>
                                <ul className="grid items-start gap-x-12 lg:grid-cols-2">
                                    {section.items.map((dish) => (
                                        <MenuDish key={dish.id} dish={dish} />
                                    ))}
                                </ul>
                            </section>
                        ))}
                    </div>
                    {resultCount === 0 && (
                        <div className="py-10 text-center">
                            <p className="font-display text-2xl text-navy">
                                Nothing on this page just yet.
                            </p>
                            <p className="mt-3 text-sm text-navy/70">
                                Try another category or a different search.
                            </p>
                            <button
                                type="button"
                                onClick={reset}
                                className="mt-5 min-h-11 cursor-pointer rounded-lg border border-navy/20 px-5 text-sm font-medium text-navy hover:bg-sand focus-visible:ring-2 focus-visible:ring-lake focus-visible:outline-none"
                            >
                                Reset menu filters
                            </button>
                        </div>
                    )}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 border-t border-navy/10 bg-sand/35 px-5 py-5 text-xs leading-relaxed text-navy/70 sm:px-8 lg:px-10">
                    <span className="inline-flex items-center gap-2">
                        <Leaf className="size-3.5 text-lake" aria-hidden />
                        Vegetarian dishes are marked on the menu.
                    </span>
                    <span>
                        Please tell our team about any food allergies before
                        ordering.
                    </span>
                </div>
            </div>
        </div>
    );
}
