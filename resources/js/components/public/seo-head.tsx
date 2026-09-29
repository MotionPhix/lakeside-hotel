import { Head, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

/**
 * The page's title and description, taken from the server's own description of it.
 *
 * The server writes these into the HTML too, and that is the copy a link preview
 * reads - the scrapers behind WhatsApp, Facebook and LinkedIn run no JavaScript at
 * all. This is the other half: it keeps the document title and description
 * correct as a visitor moves between pages without a full page load.
 *
 * Both read the same source, so they cannot disagree. The wording used to live in
 * each page's own `<Head>`; it moved to `App\Support\PageSeo` rather than being
 * duplicated, because a title that says one thing in the HTML and another once
 * the page has hydrated is worse than either.
 */
export function SeoHead() {
    const { seo } = usePage<SharedData>().props;

    if (!seo) {
        return null;
    }

    return (
        <Head title={seo.title}>
            <meta name="description" content={seo.description} />
        </Head>
    );
}
