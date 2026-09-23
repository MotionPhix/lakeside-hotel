import { Link } from '@inertiajs/react';
import { ExternalLink, LayoutGrid, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavGroup, type NavGroupDefinition } from '@/components/nav-group';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/lib/permissions';
import { dashboard, home } from '@/routes';
import users from '@/routes/admin/users';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { can } = usePermissions();

    const groups: NavGroupDefinition[] = [
        {
            label: 'Overview',
            items: [
                { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            ],
        },
        {
            label: 'Hotel',
            items: [
                can('users.view') && {
                    title: 'Staff',
                    href: users.index(),
                    icon: Users,
                },
            ].filter(Boolean) as NavGroupDefinition['items'],
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'View website',
            href: home(),
            icon: ExternalLink,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {groups.map((group) => (
                    <NavGroup
                        key={group.label}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
