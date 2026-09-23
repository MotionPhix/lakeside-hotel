import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

/**
 * The brand block at the top of the sidebar: a tile, then the hotel name over a
 * second line. The two-line shape follows the shadcn sidebar block, where the
 * same slot carries a title and a version.
 */
export default function AppLogo({ subtitle }: { subtitle?: string }) {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-4 fill-current text-white dark:text-black" />
            </div>
            <div className="flex flex-col gap-0.5 leading-none">
                <span className="truncate font-medium">{name}</span>
                {subtitle && (
                    <span className="truncate text-xs text-sidebar-foreground/60">
                        {subtitle}
                    </span>
                )}
            </div>
        </>
    );
}
