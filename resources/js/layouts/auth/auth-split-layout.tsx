import { Link } from '@inertiajs/react';
import { LakesideLogo } from '@/components/lakeside-logo';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * The two-column sign-in panel. The wordmark sits on a white plate in the dark
 * column for the same reason it does in the sidebar: the mark is deep blue, and
 * a dark ground swallows it.
 */
export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-zinc-900" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <span className="flex items-center rounded-lg bg-white px-2 py-1.5">
                        <LakesideLogo className="h-6" />
                    </span>
                </Link>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-start lg:hidden"
                    >
                        <LakesideLogo className="h-9" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
