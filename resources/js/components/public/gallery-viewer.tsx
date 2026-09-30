import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import type { KeyboardEvent, TouchEvent } from 'react';
import type { RefObject } from 'react';
import { useRef } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatLabel } from '@/lib/format';
import type { GalleryItemData } from '@/types';

/**
 * How far a finger has to travel sideways before it counts as a swipe rather than
 * a tap that wandered, or the beginnings of a scroll.
 */
const SWIPE_THRESHOLD = 48;

/**
 * A photograph, filling the screen, with the rest of its category either side.
 *
 * The set it walks through is whatever the gallery is currently showing - the same
 * list the grid was built from - rather than every photograph the hotel has. If a
 * guest has narrowed the grid to Dining, the arrows should move through Dining,
 * because that is the set they asked for.
 *
 * Built on the dialog primitive so the modal behaviour is the browser's and the
 * library's rather than mine: focus is trapped inside while it is open and
 * returned to the photograph that opened it when it closes, the page behind is
 * hidden from assistive technology and cannot be scrolled, and Escape closes it.
 * The parts that are this component's own are the arrows, the swipe, and saying
 * out loud which photograph of how many is on screen.
 */
export function GalleryViewer({
    items,
    index,
    onIndexChange,
    restoreFocusTo,
}: {
    items: GalleryItemData[];
    /** Which of `items` is open, or null when the viewer is closed. */
    index: number | null;
    onIndexChange: (index: number | null) => void;
    /**
     * The photograph that opened the viewer, so closing it puts the keyboard user
     * back where they were rather than at the top of the page.
     */
    restoreFocusTo?: RefObject<HTMLElement | null>;
}) {
    const current = index === null ? null : (items[index] ?? null);

    /* Where the finger went down, kept out of state: a swipe should not make the
       component render on the way. */
    const touchStart = useRef<{ x: number; y: number } | null>(null);

    const step = (delta: number) => {
        if (index === null) {
            return;
        }

        const next = index + delta;

        /* Stops at the ends rather than wrapping, so the arrows mean what they
           look like and a category with one photograph in it cannot send a guest
           round in a circle of one. */
        if (next < 0 || next >= items.length) {
            return;
        }

        onIndexChange(next);
    };

    /* Read inside the key handler without narrowing `index` again there. */
    const atStart = index === 0;
    const atEnd = index !== null && index >= items.length - 1;

    const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        if (items.length < 2) {
            return;
        }

        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                step(-1);
                break;
            case 'ArrowRight':
                event.preventDefault();
                step(1);
                break;
            case 'Home':
                event.preventDefault();
                onIndexChange(0);
                break;
            case 'End':
                event.preventDefault();
                onIndexChange(items.length - 1);
                break;
            /* Escape is the dialog's own business and is left alone. */
        }
    };

    const onTouchEnd = (event: TouchEvent<HTMLDivElement>) => {
        const start = touchStart.current;
        touchStart.current = null;

        const touch = event.changedTouches[0];

        if (start === null || touch === undefined || items.length < 2) {
            return;
        }

        const across = touch.clientX - start.x;
        const down = touch.clientY - start.y;

        /* Sideways, and further sideways than down. A thumb moving vertically is
           scrolling or reaching for the close button, and stealing that would be
           worse than having no swipe at all. */
        if (
            Math.abs(across) < SWIPE_THRESHOLD ||
            Math.abs(across) <= Math.abs(down)
        ) {
            return;
        }

        step(across < 0 ? 1 : -1);
    };

    return (
        <Dialog
            open={current !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onIndexChange(null);
                }
            }}
        >
            {current !== null && (
                <DialogContent
                    /*
                     * Full-bleed. Every class here is undoing a dialog default:
                     * centred and capped at 32rem, on a light surface, with padding
                     * and a border. Photographs want the whole screen and a dark
                     * frame.
                     *
                     * `top-0 left-0` is not decoration, and it was missing at
                     * first. The defaults place the panel at `top-[50%] left-[50%]`
                     * and pull it back with a negative translate; cancelling the
                     * translate without moving the origin leaves it anchored at the
                     * centre of the screen and growing down and to the right, with
                     * three quarters of it - the close button and both arrows among
                     * them - off-screen.
                     */
                    className="fixed top-0 left-0 flex h-full max-h-none w-full max-w-none translate-x-0 translate-y-0 flex-col gap-0 rounded-none border-0 bg-navy/95 p-0 sm:max-w-none"
                    showCloseButton={false}
                    aria-modal="true"
                    onKeyDown={onKeyDown}
                    onCloseAutoFocus={(event) => {
                        /*
                         * Radix returns focus to whatever opened the dialog, which
                         * it finds through its own trigger. There is no trigger
                         * here - the photograph is an ordinary button in the grid -
                         * so it falls back to `<body>`, dropping a keyboard user at
                         * the top of the document with no idea where they were.
                         */
                        const trigger = restoreFocusTo?.current;

                        if (trigger) {
                            event.preventDefault();
                            trigger.focus();
                        }
                    }}
                >
                    <div className="flex items-start justify-between gap-4 p-4 sm:p-6">
                        <div className="min-w-0">
                            <DialogTitle className="font-display text-lg font-semibold text-white sm:text-xl">
                                {current.title || 'Photograph'}
                            </DialogTitle>
                            <DialogDescription className="mt-0.5 text-sm text-white/70">
                                {current.caption ||
                                    formatLabel(current.category)}
                            </DialogDescription>
                        </div>

                        <div className="flex shrink-0 items-center gap-3">
                            {/*
                                Announces itself as the guest moves, which is the
                                only thing that tells somebody who cannot see the
                                picture that the picture changed.
                            */}
                            <p
                                aria-live="polite"
                                className="text-sm text-white/70 tabular-nums"
                            >
                                {(index ?? 0) + 1} of {items.length}
                            </p>

                            <button
                                type="button"
                                onClick={() => onIndexChange(null)}
                                aria-label="Close"
                                className="flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                            >
                                <X className="size-5" aria-hidden />
                            </button>
                        </div>
                    </div>

                    <div
                        className="relative flex min-h-0 flex-1 touch-pan-y items-center justify-center overflow-hidden px-3 pb-4 sm:px-16 sm:pb-6"
                        onTouchStart={(event) => {
                            const touch = event.touches[0];

                            if (touch !== undefined) {
                                touchStart.current = {
                                    x: touch.clientX,
                                    y: touch.clientY,
                                };
                            }
                        }}
                        onTouchEnd={onTouchEnd}
                    >
                        {current.image ? (
                            <img
                                src={current.image.hero}
                                /*
                                 * The card crop is 4:3 and this one is 16:9, so a
                                 * phone fetches a 1000px file and a desktop the
                                 * full 1920 - and the wider frame shows more of
                                 * the photograph than the tile it opened from.
                                 */
                                srcSet={`${current.image.card} 1000w, ${current.image.hero} 1920w`}
                                sizes="100vw"
                                alt={
                                    current.image.alt ||
                                    current.title ||
                                    'Lakeside Hotel'
                                }
                                width={1920}
                                height={1080}
                                className="max-h-full max-w-full rounded-md object-contain"
                            />
                        ) : (
                            <p className="text-sm text-white/70">
                                This photograph is no longer available.
                            </p>
                        )}

                        {items.length > 1 && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => step(-1)}
                                    disabled={atStart}
                                    aria-label="Previous photograph"
                                    className="absolute top-1/2 left-1 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:pointer-events-none disabled:opacity-25 sm:left-3 sm:size-12"
                                >
                                    <ChevronLeft
                                        className="size-6"
                                        aria-hidden
                                    />
                                </button>

                                <button
                                    type="button"
                                    onClick={() => step(1)}
                                    disabled={atEnd}
                                    aria-label="Next photograph"
                                    className="absolute top-1/2 right-1 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:pointer-events-none disabled:opacity-25 sm:right-3 sm:size-12"
                                >
                                    <ChevronRight
                                        className="size-6"
                                        aria-hidden
                                    />
                                </button>
                            </>
                        )}
                    </div>
                </DialogContent>
            )}
        </Dialog>
    );
}
