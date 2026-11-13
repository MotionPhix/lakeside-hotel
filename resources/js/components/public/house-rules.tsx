import { usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { SharedData } from '@/types';

export function HouseRules() {
    const { site } = usePage<SharedData>().props;
    return (
        <aside
            aria-label="Hotel house rules"
            className="flex items-start gap-3 rounded-xl border border-navy/15 bg-sand/60 p-5 text-sm leading-relaxed text-navy"
        >
            <Info className="mt-0.5 size-5 shrink-0 text-lake" aria-hidden />
            <div>
                <p className="mb-1 font-semibold">Before you visit</p>
                <p>{site.booking.house_rules}</p>
            </div>
        </aside>
    );
}
