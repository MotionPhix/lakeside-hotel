import { Head } from '@inertiajs/react';
import { CheckCircle2, Clock } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermissions } from '@/lib/permissions';
import { hotelModules } from '@/lib/modules';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { user, can, canAny } = usePermissions();

    if (!user) {
        return null;
    }

    const available = hotelModules.filter(
        (module) => module.phase === 1 && can(module.permission),
    );
    const upcoming = hotelModules.filter(
        (module) => module.phase > 1 && can(module.permission),
    );

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={`Welcome back, ${user.name.split(' ')[0]}`}
                        description="Your access to the Lakeside Hotel dashboard, based on your role."
                    />
                    <Badge variant="secondary">{user.role_label}</Badge>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="py-4">
                        <CardContent className="space-y-1">
                            <p className="text-sm text-muted-foreground">
                                Signed in as
                            </p>
                            <p className="truncate font-medium">{user.email}</p>
                        </CardContent>
                    </Card>
                    <Card className="py-4">
                        <CardContent className="space-y-1">
                            <p className="text-sm text-muted-foreground">
                                Job title
                            </p>
                            <p className="truncate font-medium">
                                {user.job_title ?? '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="py-4">
                        <CardContent className="space-y-1">
                            <p className="text-sm text-muted-foreground">
                                Permissions granted
                            </p>
                            <p className="font-medium">
                                {user.permissions.length}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-muted-foreground" />
                            Available to you now
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 sm:grid-cols-2">
                        {available.map((module) => (
                            <div
                                key={module.key}
                                className="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <module.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div className="space-y-1">
                                    <p className="font-medium">
                                        {module.title}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {module.description}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                {upcoming.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="size-4 text-muted-foreground" />
                                Your role also unlocks
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {upcoming.map((module) => (
                                <div
                                    key={module.key}
                                    className="flex items-start gap-3 rounded-lg border border-dashed p-4"
                                >
                                    <module.icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                    <div className="space-y-1">
                                        <p className="font-medium">
                                            {module.title}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {module.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {upcoming.length === 0 && !canAny(['bookings.view']) && (
                    <p className="text-sm text-muted-foreground">
                        Your role is limited to the modules listed above. Ask an
                        administrator if you need wider access.
                    </p>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
