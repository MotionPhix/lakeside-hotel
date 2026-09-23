import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fades and lifts its children into view the first time they are scrolled to.
 *
 * Honours `prefers-reduced-motion`, and reveals immediately when
 * IntersectionObserver is unavailable so content is never trapped invisible.
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
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const node = ref.current;

        if (!node) {
            return;
        }

        if (typeof IntersectionObserver === 'undefined') {
            setVisible(true);

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setVisible(true);
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
                'transition-[opacity,transform] duration-700 ease-out motion-reduce:transition-none',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-6 opacity-0 motion-reduce:translate-y-0 motion-reduce:opacity-100',
                className,
            )}
        >
            {children}
        </div>
    );
}
