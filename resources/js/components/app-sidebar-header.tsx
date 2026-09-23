import { Breadcrumbs } from '@/components/breadcrumbs';
import { HeaderActions } from '@/components/header-actions';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * The sticky page header from the shadcn `sidebar-02` block: the sidebar trigger,
 * a short rule, then the breadcrumb.
 *
 * The row runs the full width of the content area and is laid out from the
 * outside in - breadcrumb on the left, controls on the right - rather than being
 * centred over the page. The content below keeps its own centred measure; the
 * header is chrome, and chrome reads better pinned to the edges.
 *
 * The breadcrumb sits in a shrinking column so a long page title is clipped
 * instead of shoving the controls off the end on a narrow window.
 */
export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:px-6">
            <SidebarTrigger className="-ml-1" />

            <Separator
                orientation="vertical"
                className="mr-1 data-[orientation=vertical]:h-4"
            />

            <div className="min-w-0 flex-1 overflow-hidden [&_ol]:flex-nowrap">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <HeaderActions />
        </header>
    );
}
