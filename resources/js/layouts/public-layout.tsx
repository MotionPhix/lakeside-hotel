import type { ReactNode } from 'react';
import { SiteFooter } from '@/components/public/site-footer';
import { SiteHeader } from '@/components/public/site-header';
import { useFlashToast } from '@/hooks/use-flash-toast';

/**
 * The public website shell.
 *
 * Light-only by design: every component uses the fixed brand colours, so a
 * visitor's system dark-mode preference can never invert the layout.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    useFlashToast();

    return (
        <div className="flex min-h-screen flex-col bg-white text-navy">
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
