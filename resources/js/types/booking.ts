/** The dates and party a guest is searching with. */
export type BookingSearch = {
    check_in: string;
    check_out: string;
    adults: number;
    children: number;
    room_type: string;
};

/** A room category offered in the search form's filter. */
export type RoomTypeOption = {
    slug: string;
    name: string;
    from: string;
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
    image: string | null;
    available: number;
    nights: number;
    nightly: Record<string, string>;
    average_nightly: string;
    extra_guests: number;
    extra_person_price: string;
    subtotal: string;
    tax_total: string;
    total: string;
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
    total: string;
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
