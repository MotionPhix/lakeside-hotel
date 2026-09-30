import { router, usePage } from '@inertiajs/react';
import {
    BookingSearchFields,
    roomTypeParam,
} from '@/components/public/booking-search-fields';
import bookingRoutes from '@/routes/site/booking';
import type { SharedData } from '@/types';

type BookableRoom = {
    slug: string;
    name: string;
};

/**
 * The quick booking bar on the homepage: dates, party size and room preference.
 *
 * It carries what has been chosen into the booking page, which is where the
 * hotel's live availability and rates are, rather than answering the question here
 * and risking a stale one.
 *
 * The fields themselves live in {@see BookingSearchFields}, shared with the
 * booking page's own search, so the two cannot drift apart. This is the card, and
 * the one decision that belongs to it: where the search goes.
 */
export function BookingWidget({ roomTypes }: { roomTypes: BookableRoom[] }) {
    const { site } = usePage<SharedData>().props;

    return (
        <div className="rounded-xl border border-navy/10 bg-white/95 p-4 shadow-md backdrop-blur sm:p-5">
            <BookingSearchFields
                onSearch={(values) =>
                    router.get(bookingRoutes.index.url(), {
                        check_in: values.from,
                        check_out: values.to,
                        adults: values.adults,
                        children: values.children,
                        room_type: roomTypeParam(values.roomType),
                    })
                }
                roomTypes={roomTypes}
                checkInTime={site.contact.check_in_time}
            />
        </div>
    );
}
