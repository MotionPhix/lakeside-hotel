import { Bell, Moon, Sun } from 'lucide-react';
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

/**
 * The controls on the right of the page header.
 *
 * Everything here does something today: the appearance button flips the
 * dashboard between light and dark, and the bell opens a panel that says plainly
 * that there is nothing to show yet. It deliberately carries no unread count - a
 * badge that never changes is a lie dressed as a feature. Booking and enquiry
 * alerts will fill that panel when the reservation engine lands.
 *
 * The link out to the public website does not live here. It sits at the foot of
 * the sidebar, directly above the account menu, where it has always been.
 */
export function HeaderActions() {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';

    return (
        <div className="flex shrink-0 items-center gap-1">
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
                {/*
                 * The tooltip wraps the popover trigger rather than sitting
                 * beside it, so the bell carries a hover label like the theme
                 * control next to it. Radix resolves both through context, so
                 * the nesting is only about which element receives the props.
                 */}
                <Tooltip>
                    <TooltipTrigger asChild>
                        <PopoverTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <Bell />
                                <span className="sr-only">Notifications</span>
                            </Button>
                        </PopoverTrigger>
                    </TooltipTrigger>
                    <TooltipContent>Notifications</TooltipContent>
                </Tooltip>

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
