export type RoleName =
    | 'guest'
    | 'system_admin'
    | 'admin'
    | 'hotel_manager'
    | 'reception'
    | 'marketing';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    job_title: string | null;
    role: RoleName;
    role_label: string;
    role_level: number;
    permissions: string[];
    is_staff: boolean;
    two_factor_enabled?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
