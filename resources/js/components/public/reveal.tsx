import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fades and lifts its children into view the first time they are scrolled to.
 *
 * Content starts visible, and is only hidden once the browser has confirmed it
 * is below the fold and has something to animate. The obvious version of this
 * component does the opposite - starts hidden and waits for an observer to reveal
 * it - which quietly makes the whole page invisible whenever that observer never
 * runs: in a background tab, in a screenshot or preview service, and to anything
 * reading the page without rendering it. Since every image below the fold is also
 * `loading="lazy"`, and lazy images do not load in a page nobody is looking at,
 * the two together turn the page into a blank rectangle.
 *
 * Honours `prefers-reduced-motion`, and does nothing at all if
 * IntersectionObserver is unavailable.
 */
export function Reveal({
    children,
    className,
    delay = 0,
}: {
    children: ReactNode;
    className?: string;
    delay?: number;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const [hidden, setHidden] = useState(false);

    useEffect(() => {
        const node = ref.current;

        if (!node || typeof IntersectionObserver === 'undefined') {
            return;
        }

        // Only content that is off-screen can animate in later. Anything already
        // in view is left alone, so nothing on the first screen can be hidden and
        // then fail to come back.
        if (node.getBoundingClientRect().top < window.innerHeight) {
            return;
        }

        setHidden(true);

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setHidden(false);
                    observer.disconnect();
                }
            },
            { rootMargin: '0px 0px -8% 0px', threshold: 0.05 },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, []);

    return (
        <div
            ref={ref}
            style={delay > 0 ? { transitionDelay: `${delay}ms` } : undefined}
            className={cn(
                /* `translate`, not `transform`: Tailwind 4 compiles translate-y-*
                   to the CSS `translate` property, so a transition naming only
                   `transform` left the lift snapping into place while the fade
                   eased. The lift is half of what this component is for. */
                'transition-[opacity,translate,transform] duration-700 ease-out motion-reduce:transition-none',
                hidden
                    ? 'translate-y-6 opacity-0 motion-reduce:translate-y-0 motion-reduce:opacity-100'
                    : 'translate-y-0 opacity-100',
                className,
            )}
        >
            {children}
        </div>
    );
}
