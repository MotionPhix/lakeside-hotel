import { Bell, ExternalLink, Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverDescription,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';
import { toUrl } from '@/lib/utils';
import { home } from '@/routes';

/**
 * The controls on the right of the page header.
 *
 * Everything here does something today: the link opens the public website in a
 * new tab, the appearance button flips the dashboard between light and dark, and
 * the bell opens a panel that says plainly that there is nothing to show yet.
 * It deliberately carries no unread count - a badge that never changes is a lie
 * dressed as a feature. Booking and enquiry alerts will fill that panel when the
 * reservation engine lands.
 */
export function HeaderActions() {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';

    return (
        <div className="flex shrink-0 items-center gap-1">
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="ghost" size="icon" asChild>
                        <a
                            href={toUrl(home())}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <ExternalLink />
                            <span className="sr-only">View website</span>
                        </a>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>View website</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                            updateAppearance(isDark ? 'light' : 'dark')
                        }
                    >
                        {isDark ? <Sun /> : <Moon />}
                        <span className="sr-only">
                            {isDark
                                ? 'Switch to light mode'
                                : 'Switch to dark mode'}
                        </span>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>
                    {isDark ? 'Light mode' : 'Dark mode'}
                </TooltipContent>
            </Tooltip>

            <Popover>
                <PopoverTrigger asChild>
                    <Button variant="ghost" size="icon">
                        <Bell />
                        <span className="sr-only">Notifications</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="end" className="w-80">
                    <PopoverHeader>
                        <PopoverTitle>Notifications</PopoverTitle>
                        <PopoverDescription>
                            You are all caught up. Booking and enquiry alerts
                            will appear here.
                        </PopoverDescription>
                    </PopoverHeader>
                </PopoverContent>
            </Popover>
        </div>
    );
}
