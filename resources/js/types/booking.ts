import type { MediaImage, Pricing } from './hotel';

/** The dates and party a guest is searching with. */
export type BookingSearch = {
    check_in: string;
    check_out: string;
    adults: number;
    children: number;
    room_type: string;
};

/** A room category offered in the search form's filter. */
/**
 * One extra as it is offered while booking.
 *
 * `max_quantity` and `quantity_label` come from the activity itself rather than
 * from the form: how many of a thing may be chosen is a property of the thing, and
 * sending it here means the control the guest sees cannot disagree with what the
 * server will accept. A null `quantity_label` means there is nothing to count - the
 * price is for the group - so the form leaves the control out.
 */
export type BookingExtraOption = {
    id: number;
    name: string;
    description: string | null;
    duration: string | null;
    /** The unit price, as configured. */
    price: string;
    price_basis: string;
    basis_label: string;
    min_quantity: number;
    max_quantity: number;
    quantity_label: string | null;
};

/** An extra the guest has chosen, in the shape the form submits it. */
export type BookingExtraChoice = {
    id: number;
    quantity: number;
};

/** One extra as it sits on a stay: named and priced as it was when chosen. */
export type BookingExtraLine = {
    id: number;
    name: string;
    /** Reads as the guest should see it, e.g. "Sunset cruise × 2". */
    label: string;
    price_basis: string;
    basis_label: string;
    unit_price: string;
    quantity: number;
    subtotal: string;
};

export type RoomTypeOption = {
    slug: string;
    name: string;
    /** The room's cheapest rate as a guest should see it: tax included. */
    from: Pricing | null;
};

/**
 * A category the hotel can sell for the searched dates, priced as the booking
 * would be. `nightly` is keyed by date so a guest can see which nights carry the
 * weekend rate.
 */
export type BookingOffer = {
    slug: string;
    name: string;
    tagline: string | null;
    description: string | null;
    bed_configuration: string | null;
    size_sqm: number | null;
    capacity_adults: number;
    capacity_children: number;
    image: MediaImage | null;
    available: number;
    nights: number;
    nightly: Record<string, string>;
    average_nightly: string;
    extra_guests: number;
    extra_person_price: string;
    subtotal: string;
    tax_total: string;
    total: string;
    /**
     * How the total is presented: the same figure either way, with VAT and the
     * levy broken out only when the hotel has asked for it. `subtotal` and
     * `tax_total` remain the accounting figures whatever the toggle says.
     */
    pricing: Pricing;
    amenities: string[];
};

export type PaymentOptionChoice = {
    value: string;
    label: string;
    deposit_percentage: number;
};

/** The terms, times and payment choices every booking page shows. */
export type BookingContext = {
    check_in_time: string;
    check_out_time: string;
    cancellation_policy: string;
    child_policy: string;
    transfer_note: string;
    payment_options: PaymentOptionChoice[];
    online_payment_available: boolean;
};

/** A reservation as the guest sees it. */
export type Reservation = {
    reference: string;
    status: string;
    status_label: string;
    payment_status: string;
    payment_status_label: string;
    check_in: string;
    check_out: string;
    nights: number;
    adults: number;
    children: number;
    currency: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    /** The whole stay: accommodation, its tax, and the extras. */
    total: string;
    /**
     * The accommodation alone - see {@link BookingOffer.pricing}. Extras are
     * itemised separately in {@link Reservation.extras} and added on top.
     */
    pricing: Pricing;
    /** The extras chosen with this stay, in the order they were added. */
    extras: BookingExtraLine[];
    /** What those extras come to. */
    extras_total: string;
    amount_paid: string;
    balance: string;
    payment_method: string | null;
    payment_method_label: string | null;
    airport_transfer: boolean;
    special_requests: string | null;
    guest: {
        name: string;
        email: string;
        phone: string | null;
    };
    rooms: {
        name: string | null;
        slug: string | null;
        subtotal: string;
        nightly_rates: Record<string, string> | null;
    }[];
};
