/** A guest expected today, or leaving today. */
export type StayRow = {
    reference: string;
    guest: string;
    phone: string | null;
    room: string | null;
    nights: number;
    guests: number;
    status: string;
    status_label: string;
    balance: string;
    unpaid: boolean;
    airport_transfer: boolean;
    /** Only on departures: the stay was due to end before today. */
    overdue?: boolean;
};

export type Occupancy = {
    occupied: number;
    sellable: number;
    percentage: number;
};

export type RevenueSummary = {
    taken_today: string;
    taken_this_month: string;
    month_label: string;
    booked_today: string;
};

export type InquiryRow = {
    id: number;
    name: string;
    email: string;
    subject: string;
    type: string;
    status: string;
    guests: number | null;
    preferred_date: string | null;
    received: string | null;
};

export type ArrivalDay = {
    date: string;
    label: string;
    bookings: number;
    guests: number;
};

/** Everything the operational overview shows. */
export type DashboardOverview = {
    date: string;
    date_label: string;
    arrivals: StayRow[];
    departures: StayRow[];
    in_house: number;
    occupancy: Occupancy;
    revenue: RevenueSummary;
    pending: number;
    outstanding: string;
    inquiries: InquiryRow[];
    arriving_soon: ArrivalDay[];
};

/** A reservation as it appears in the admin list. */
export type AdminBookingRow = {
    id: number;
    reference: string;
    guest: string;
    email: string;
    phone: string | null;
    room: string | null;
    rooms: number;
    check_in: string;
    check_out: string;
    nights: number;
    adults: number;
    children: number;
    guests: number;
    status: string;
    status_label: string;
    status_variant: string;
    payment_status: string;
    payment_status_label: string;
    total: string;
    balance: string;
    unpaid: boolean;
    source: string;
    airport_transfer: boolean;
    created_at: string | null;
};

/** The moves the desk may make, decided by the server. */
export type BookingActions = {
    confirm: boolean;
    cancel: boolean;
    check_in: boolean;
    check_out: boolean;
    no_show: boolean;
};

export type AdminBookingItem = {
    id: number;
    room_type: string | null;
    room: string | null;
    adults: number;
    children: number;
    rate_plan: string | null;
    price_per_night: string;
    subtotal: string;
    nightly_rates: Record<string, string> | null;
};

export type AdminBookingPayment = {
    id: number;
    provider: string;
    reference: string | null;
    method: string | null;
    amount: string;
    status: string;
    paid_at: string | null;
    recorded_by: string | null;
};

/** One reservation in full. */
export type AdminBookingDetail = AdminBookingRow & {
    currency: string;
    subtotal: string;
    discount_total: string;
    tax_total: string;
    amount_paid: string;
    payment_method: string | null;
    coupon: string | null;
    special_requests: string | null;
    internal_notes: string | null;
    transfer_details: Record<string, string> | null;
    cancellation_reason: string | null;
    created_by: string | null;
    timeline: Record<string, string | null>;
    guest_detail: {
        name: string;
        email: string;
        phone: string | null;
        country: string | null;
        city: string | null;
        stays: number;
        marketing_opt_in: boolean;
    };
    items: AdminBookingItem[];
    payments: AdminBookingPayment[];
    can: BookingActions;
};

export type SelectOption = {
    value: string;
    label: string;
};
