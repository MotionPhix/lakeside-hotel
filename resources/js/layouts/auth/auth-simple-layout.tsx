import { Link } from '@inertiajs/react';
import { LakesideLogo } from '@/components/lakeside-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * The sign-in panel: wordmark, then what the page is for, then the form.
 *
 * The whole block is left aligned. Centred, the mark and the heading each sat on
 * their own axis and the eye had to reset between them; aligned to one edge, the
 * mark, heading, description and form all start from the same line.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-start gap-5">
                        <Link href={home()} className="flex flex-col gap-2">
                            <LakesideLogo className="h-9" />
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
