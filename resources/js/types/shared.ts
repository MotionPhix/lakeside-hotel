import type { Auth } from '@/types/auth';
import type { SiteSettings } from '@/types/hotel';

/**
 * Props Inertia shares with every response. Mirrors
 * `App\Http\Middleware\HandleInertiaRequests::share()`.
 */
export type SharedData = {
    name: string;
    site: SiteSettings;
    auth: Auth;
    sidebarOpen: boolean;
};
