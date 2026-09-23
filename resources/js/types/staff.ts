import type { RoleName } from '@/types/auth';

export type Permission =
    | 'dashboard.view'
    | 'bookings.view'
    | 'bookings.manage'
    | 'guests.view'
    | 'guests.manage'
    | 'inquiries.view'
    | 'inquiries.manage'
    | 'rooms.view'
    | 'rooms.manage'
    | 'availability.manage'
    | 'pricing.view'
    | 'pricing.manage'
    | 'content.manage'
    | 'promotions.manage'
    | 'reviews.moderate'
    | 'media.manage'
    | 'users.view'
    | 'users.manage'
    | 'reports.view'
    | 'system.manage';

export type StaffMember = {
    id: number;
    name: string;
    email: string;
    role: RoleName;
    role_label: string;
    phone: string | null;
    job_title: string | null;
    is_active: boolean;
    is_self: boolean;
    last_login_at: string | null;
    created_at: string;
    can_manage: boolean;
};

export type RoleOption = {
    value: RoleName;
    label: string;
    description: string;
    level: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};
