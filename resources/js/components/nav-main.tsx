import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';

export type NavHref = NonNullable<InertiaLinkProps['href']>;

export type NavSubItem = {
    title: string;
    /**
     * Absent while the module is still to be built. The row renders as text
     * with no destination rather than as a link that goes nowhere.
     */
    href?: NavHref;
    /** Shown on hover. Carries the module's description while it is unbuilt. */
    hint?: string;
};

export type NavSection = {
    title: string;
    /** Where the section heading goes. Omitted when nothing under it exists yet. */
    href?: NavHref;
    items: NavSubItem[];
};

/**
 * The dashboard navigation, laid out like the shadcn sidebar block: one flat list
 * of sections, each with its pages nested underneath on the submenu rail.
 *
 * The sections and their pages come from the module registry, filtered by what
 * the signed-in role may actually open, so the navigation cannot drift from the
 * permissions the server enforces. A module that has no route yet is shown but
 * inert - the sidebar doubles as an honest map of what the dashboard can do
 * today, which is the same thing the overview page already says.
 */
export function NavMain({ sections }: { sections: NavSection[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup>
            <SidebarMenu>
                {sections.map((section) => (
                    <SidebarMenuItem key={section.title}>
                        {section.href ? (
                            <SidebarMenuButton asChild>
                                <Link
                                    href={section.href}
                                    prefetch
                                    className="font-medium"
                                >
                                    {section.title}
                                </Link>
                            </SidebarMenuButton>
                        ) : (
                            <SidebarMenuButton className="font-medium">
                                {section.title}
                            </SidebarMenuButton>
                        )}

                        {section.items.length > 0 && (
                            <SidebarMenuSub>
                                {section.items.map((item) =>
                                    item.href ? (
                                        <SidebarMenuSubItem key={item.title}>
                                            <SidebarMenuSubButton
                                                asChild
                                                isActive={isCurrentOrParentUrl(
                                                    item.href,
                                                )}
                                            >
                                                <Link href={item.href} prefetch>
                                                    {item.title}
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    ) : (
                                        <SidebarMenuSubItem key={item.title}>
                                            <SidebarMenuSubButton
                                                aria-disabled
                                                title={item.hint}
                                                className="opacity-55"
                                            >
                                                {item.title}
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    ),
                                )}
                            </SidebarMenuSub>
                        )}
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
