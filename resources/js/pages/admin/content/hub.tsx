import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Settings2 } from 'lucide-react';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import contentRoutes from '@/routes/admin/content';
import type { CmsHubGroup } from '@/types';

/**
 * The way into the content admin: everything that can be edited, grouped, with a
 * count of each so it is obvious where the site's content lives.
 */
export default function ContentHub({ groups }: { groups: CmsHubGroup[] }) {
    return (
        <>
            <Head title="Website content" />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Website content"
                    description="Everything the website shows, editable without a developer."
                />

                <div className="grid gap-6">
                    {groups.map((group) => (
                        <Card key={group.group}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Settings2 className="size-4 text-muted-foreground" />
                                    {group.group}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {group.resources.map((resource) => (
                                    <Link
                                        key={resource.key}
                                        href={contentRoutes.index(resource.key)}
                                        className="group flex flex-col gap-1 rounded-lg border p-4 transition-colors hover:border-foreground/30"
                                    >
                                        <span className="flex items-center justify-between gap-2">
                                            <span className="font-medium">
                                                {resource.label}
                                            </span>
                                            <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                {resource.count}
                                                <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                                            </span>
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {resource.description}
                                        </span>
                                    </Link>
                                ))}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

ContentHub.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Website content', href: contentRoutes.hub() },
    ],
};
