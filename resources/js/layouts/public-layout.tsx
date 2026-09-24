import type { ReactNode } from 'react';
import { SiteFooter } from '@/components/public/site-footer';
import { SiteHeader } from '@/components/public/site-header';

/**
 * The public website shell.
 *
 * Light-only by design: every component uses the fixed brand colours, so a
 * visitor's system dark-mode preference can never invert the layout.
 *
 * Toasts are deliberately absent here. They are the back end's to send, and the
 * single listener that shows them is mounted alongside the Toaster in `app.tsx`,
 * which wraps every page. Registering one here as well meant two listeners, and
 * therefore two copies of every message, on exactly the pages a visitor is most
 * likely to be looking at.
 */
export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col bg-white text-navy">
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
        </div>
    );
}
