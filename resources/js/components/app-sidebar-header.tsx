import { Breadcrumbs } from '@/components/breadcrumbs';
import { HeaderActions } from '@/components/header-actions';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * The sticky page header from the shadcn `sidebar-02` block, with the
 * application's own name in place of a wordmark: the trigger, the name, the
 * breadcrumb, then the controls.
 *
 * The name is read from `config('app.name')` - `APP_NAME` in the environment -
 * rather than written here, so renaming the application is a change to `.env` and
 * nothing else. Nothing in the front end spells out the hotel.
 *
 * The logo belongs to the sidebar, where the brand lives; here the name is doing
 * the work, so the mark is deliberately absent and the two are not the same
 * weight of blue sitting an inch apart.
 *
 * The row runs the full width of the content area and is laid out from the
 * outside in - trigger and name on the left, controls on the right - rather than
 * being centred over the page. The content below keeps its own centred measure;
 * the header is chrome, and chrome reads better pinned to the edges.
 *
 * The breadcrumb sits in a shrinking column so a long page title is clipped
 * instead of shoving the controls off the end on a narrow window, and the name is
 * dropped below `sm` where the page's own context matters more than the
 * application's.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { name } = usePage<SharedData>().props;

    return (
        <header className="sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:px-6">
            <SidebarTrigger className="-ml-1" />

            <Separator
                orientation="vertical"
                className="mr-1 data-[orientation=vertical]:h-4"
            />

            <span className="hidden items-center gap-2 sm:flex">
                <span className="text-sm font-semibold tracking-tight">
                    {name}
                </span>

                <Separator
                    orientation="vertical"
                    className="data-[orientation=vertical]:h-4"
                />
            </span>

            <div className="min-w-0 flex-1 overflow-hidden [&_ol]:flex-nowrap">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <HeaderActions />
        </header>
    );
}
