import type { SVGProps } from 'react';

/**
 * Lucide's `list-sort-descending`: three lines, the top one longest.
 *
 * Written out here rather than imported because the version of lucide-react this
 * project has installed (0.475.0) does not contain the icon - it postdates that
 * release. The three paths are lucide's own, so this renders exactly what
 * `import { ListSortDescending } from 'lucide-react'` would.
 *
 * Delete this file and switch the import in the site header the next time
 * lucide-react is upgraded.
 */
export function ListSortDescending(props: SVGProps<SVGSVGElement>) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            <path d="M15 12H3" />
            <path d="M3 5h18" />
            <path d="M9 19H3" />
        </svg>
    );
}
