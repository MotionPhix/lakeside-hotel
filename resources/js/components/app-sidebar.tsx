import { NavFooter } from '@/components/nav-footer';
import type { NavHref, NavSection } from '@/components/nav-main';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import type { HotelModule } from '@/lib/modules';
import { hotelModules, moduleSections } from '@/lib/modules';
import { usePermissions } from '@/lib/permissions';
import { dashboard, home } from '@/routes';
import adminAvailability from '@/routes/admin/availability';
import adminBookings from '@/routes/admin/bookings';
import adminContent from '@/routes/admin/content';
import users from '@/routes/admin/users';
import type { NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { useMemo } from 'react';

/**
 * The modules that have a page behind them today. Everything else in the
 * registry is listed but inert, so the sidebar never sends anyone to a route
 * that does not exist yet.
 */
const moduleRoutes: Partial<Record<string, NavHref>> = {
    dashboard: dashboard(),
    bookings: adminBookings.index(),
    availability: adminAvailability.index(),
    // The one entry point for every content type: seventeen list screens would
    // be seventeen sidebar rows nobody could scan.
    content: adminContent.hub(),
    users: users.index(),
};

/**
 * The dashboard sidebar, laid out like the shadcn sidebar block: the application's
 * name in the header, one flat list of sections with their pages nested on the
 * submenu rail, and the rail on the edge for dragging it open and closed.
 *
 * It keeps a footer, which the block omits. The account menu lives there, and a
 * dashboard with no way to sign out is not a dashboard.
 */
export function AppSidebar() {
    const { can } = usePermissions();
    const { name } = usePage<SharedData>().props;

    const sections = useMemo<NavSection[]>(() => {
        const byKey = new Map<string, HotelModule>(
            hotelModules.map((module) => [module.key, module]),
        );

        return moduleSections
            .map((section): NavSection | null => {
                const modules = section.keys
                    .map((key) => byKey.get(key))
                    .filter(
                        (module): module is HotelModule =>
                            module !== undefined && can(module.permission),
                    );

                if (modules.length === 0) {
                    return null;
                }

                // A page that is not really a group - the overview - takes the
                // heading for itself and nests nothing underneath.
                if (section.nested === false) {
                    const [only] = modules;

                    return {
                        title: only.title,
                        href: moduleRoutes[only.key],
                        items: [],
                    };
                }

                return {
                    title: section.title,
                    href: modules
                        .map((module) => moduleRoutes[module.key])
                        .find((href) => href !== undefined),
                    items: modules.map((module) => ({
                        title: module.title,
                        href: moduleRoutes[module.key],
                        hint: moduleRoutes[module.key]
                            ? undefined
                            : module.description,
                    })),
                };
            })
            .filter((section): section is NavSection => section !== null);
    }, [can]);

    // Sits directly above the account menu, where the eye already goes to leave.
    const footerNavItems: NavItem[] = [
        {
            title: 'View website',
            href: home(),
            icon: ExternalLink,
        },
    ];

    return (
        <Sidebar collapsible="offcanvas">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {/*
                         * The application's own name, read from config('app.name')
                         * - APP_NAME in the environment - rather than the wordmark
                         * and rather than a literal. Nothing in the front end
                         * spells the name out, here or in the top bar, so renaming
                         * the application is a change to .env and nothing else.
                         *
                         * It is set a size up from the menu items below it so it
                         * reads as the brand rather than as one more place to go.
                         */}
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <span className="truncate text-lg font-semibold tracking-tight">
                                    {name}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain sections={sections} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>

            <SidebarRail />
        </Sidebar>
    );
}
