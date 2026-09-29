import type { Auth } from '@/types/auth';
import type { SiteSettings } from '@/types/hotel';

/**
 * What the server tells a search engine and a link preview about this page.
 *
 * Built by `App\Support\PageSeo`. The same values are written into the HTML
 * server-side, because the scrapers behind a link preview never run JavaScript -
 * this copy is what keeps the title right as somebody moves around the site
 * without a full page load.
 */
export type PageSeoData = {
    title: string;
    description: string;
    canonical: string;
    image: string;
    imageAlt: string;
    robots: string;
    type: string;
    /**
     * The page's own structured data, where it has any - a room page describes
     * the room. Written into the HTML by the Blade template; the front end has no
     * use for it, and it is here so the type matches what the server sends.
     */
    schema: Record<string, unknown> | null;
};

/**
 * Props Inertia shares with every response. Mirrors
 * `App\Http\Middleware\HandleInertiaRequests::share()`.
 */
export type SharedData = {
    name: string;
    site: SiteSettings;
    auth: Auth;
    sidebarOpen: boolean;
    /** Null on the staff side, which is not published. */
    seo: PageSeoData | null;
};
