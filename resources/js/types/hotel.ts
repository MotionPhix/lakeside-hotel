/**
 * Shapes the public website receives from the server.
 *
 * Media is always delivered as a set of ready URLs rather than a raw path, so
 * components never have to know about the media library or the bucket disk.
 */
export type MediaImage = {
    id: number;
    alt: string;
    url: string;
    thumb: string;
    card: string;
    hero: string;
};

export type SiteContact = {
    address: string;
    phone: string;
    whatsapp: string;
    email: string;
    events_email: string;
    latitude: number;
    longitude: number;
    map_zoom: number;
    check_in_time: string;
    check_out_time: string;
};

export type SiteBookingInfo = {
    currency: string;
    vat_rate: number;
    tourism_levy_rate: number;
    deposit_percentage: number;
    online_payment_enabled: boolean;
    pay_at_hotel_enabled: boolean;
    cancellation_policy: string;
    child_policy: string;
    transfer_note: string;
};

export type SiteSocial = {
    facebook: string;
    instagram: string;
    tripadvisor: string;
};

export type SiteSettings = {
    name: string;
    tagline: string;
    contact: SiteContact;
    booking: SiteBookingInfo;
    social: SiteSocial;
    logo: string;
    whatsapp_link: string;
};

export type AmenitySummary = {
    id: number;
    name: string;
    slug: string;
    icon: string | null;
    description: string | null;
};

export type RoomTypeSummary = {
    id: number;
    name: string;
    slug: string;
    tagline: string | null;
    description: string;
    capacity_adults: number;
    capacity_children: number;
    max_occupancy: number;
    size_sqm: number | null;
    bed_configuration: string | null;
    from_price: string;
    base_price: string;
    weekend_price: string | null;
    min_nights: number;
    is_featured: boolean;
    cover: MediaImage | null;
    images: MediaImage[];
    amenities: AmenitySummary[];
};

export type RoomTypeDetail = RoomTypeSummary & {
    total_rooms: number;
    url: string;
};

export type HeroSlideData = {
    id: number;
    headline: string;
    subheadline: string | null;
    cta_label: string | null;
    cta_url: string | null;
    secondary_cta_label: string | null;
    secondary_cta_url: string | null;
    image: MediaImage | null;
};

export type ContentBlockData = {
    key: string;
    title: string;
    subtitle: string | null;
    body: string | null;
    image: MediaImage | null;
};

export type MenuItemData = {
    id: number;
    name: string;
    description: string | null;
    price: string;
    category: string;
    is_signature: boolean;
    is_vegetarian: boolean;
};

export type DiningVenueData = {
    id: number;
    name: string;
    slug: string;
    type: string;
    tagline: string | null;
    description: string | null;
    opening_hours: Record<string, string> | null;
    dress_code: string | null;
    cover: MediaImage | null;
    gallery: MediaImage[];
    signature_dishes: MenuItemData[];
    menu: MenuCategory[];
};

export type ActivityData = {
    id: number;
    name: string;
    slug: string;
    description: string;
    duration: string | null;
    duration_minutes: number | null;
    price: string | null;
    price_basis: string;
    is_complimentary: boolean;
    min_participants: number | null;
    max_participants: number | null;
    cover: MediaImage | null;
};

export type ConferencePackageData = {
    id: number;
    name: string;
    slug: string;
    type: string;
    tagline: string | null;
    description: string;
    capacity_min: number | null;
    capacity_max: number | null;
    capacity_label: string | null;
    price: string;
    price_basis: string;
    includes: string[];
    cover: MediaImage | null;
};

export type GalleryItemData = {
    id: number;
    title: string | null;
    caption: string | null;
    category: string;
    type: string;
    video_url: string | null;
    is_video: boolean;
    image: MediaImage | null;
};

export type TestimonialData = {
    id: number;
    guest_name: string;
    guest_country: string | null;
    rating: number;
    stars: string;
    title: string | null;
    quote: string;
    stayed_on: string | null;
    source: string;
    response: string | null;
    avatar: MediaImage | null;
};

export type OfferData = {
    id: number;
    title: string;
    slug: string;
    subtitle: string | null;
    description: string;
    highlight: string | null;
    discount_label: string | null;
    type: string;
    starts_on: string | null;
    ends_on: string | null;
    coupon_code: string | null;
    terms: string | null;
    is_featured: boolean;
    image: MediaImage | null;
};

export type NearbyAttractionData = {
    id: number;
    name: string;
    category: string;
    description: string | null;
    distance_km: string | null;
    travel_time_minutes: number | null;
    image: MediaImage | null;
};

export type MenuCategory = {
    key: string;
    label: string;
    items: MenuItemData[];
};
