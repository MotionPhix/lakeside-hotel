import { Head, router, useForm } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    Mail,
    Phone,
    ShieldAlert,
} from 'lucide-react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatLongDate } from '@/lib/format';
import { usePermissions } from '@/lib/permissions';
import { dashboard } from '@/routes';
import inquiries from '@/routes/admin/inquiries';
import type { AdminInquiryDetail } from '@/types';

type Props = {
    inquiry: AdminInquiryDetail;
};

/**
 * One enquiry: what was asked, who has it, and what was said back.
 *
 * The reply is recorded here rather than sent from here - what is being kept is
 * the wording of the answer and the date it went out, so the next person to open
 * this does not have to ask around for it. The statuses stay out of the way until
 * somebody wants to file the thing.
 */
export default function InquiryShow({ inquiry }: Props) {
    const { can } = usePermissions();
    const canManage = can('inquiries.manage');

    const { data, setData, post, processing, errors } = useForm({
        response: inquiry.response ?? '',
    });

    const file = (status: string) => {
        router.patch(
            inquiries.status.url(inquiry.id),
            { status },
            { preserveScroll: true },
        );
    };

    const claim = () => {
        router.post(
            inquiries.claim.url(inquiry.id),
            {},
            { preserveScroll: true },
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(inquiries.respond.url(inquiry.id), { preserveScroll: true });
    };

    /*
     * Reopening puts it back where it was: straight into the queue if nobody owns
     * it, and back to in progress if somebody does.
     */
    const reopenAs = inquiry.assigned_to === null ? 'new' : 'in_progress';

    return (
        <>
            <Head title={inquiry.subject} />

            <div className="flex flex-col gap-6">
                <Heading
                    title={inquiry.subject}
                    description={`${inquiry.type_label}${
                        inquiry.received_at
                            ? ` · received ${formatLongDate(inquiry.received_at)}`
                            : ''
                    }`}
                />

                <div className="flex flex-wrap items-center gap-3">
                    <Badge variant={inquiry.status_variant}>
                        {inquiry.status_label}
                    </Badge>
                    <span className="text-sm text-muted-foreground">
                        {inquiry.assigned_name
                            ? `Being handled by ${inquiry.assigned_name}`
                            : 'Nobody has picked this up yet'}
                    </span>
                </div>

                <div className="grid gap-6 lg:grid-cols-12">
                    <div className="grid gap-6 lg:col-span-7">
                        <Card>
                            <CardHeader>
                                <CardTitle>What they asked</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm whitespace-pre-line">
                                    {inquiry.message}
                                </p>

                                {(inquiry.preferred_date ||
                                    inquiry.guests_count !== null) && (
                                    <dl className="grid gap-2 border-t pt-4 text-sm sm:grid-cols-2">
                                        {inquiry.preferred_date && (
                                            <div>
                                                <dt className="text-muted-foreground">
                                                    Preferred date
                                                </dt>
                                                <dd>
                                                    {formatLongDate(
                                                        inquiry.preferred_date,
                                                    )}
                                                </dd>
                                            </div>
                                        )}
                                        {inquiry.guests_count !== null && (
                                            <div>
                                                <dt className="text-muted-foreground">
                                                    Guests
                                                </dt>
                                                <dd>{inquiry.guests_count}</dd>
                                            </div>
                                        )}
                                    </dl>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {inquiry.response ? 'Our reply' : 'Reply'}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {inquiry.responded_at && (
                                    <p className="text-xs text-muted-foreground">
                                        Recorded{' '}
                                        {formatLongDate(inquiry.responded_at)}
                                    </p>
                                )}

                                {canManage && inquiry.actionable ? (
                                    <form
                                        onSubmit={submit}
                                        className="grid gap-3"
                                    >
                                        <Label
                                            htmlFor="response"
                                            className="sr-only"
                                        >
                                            Reply
                                        </Label>
                                        <Textarea
                                            id="response"
                                            rows={6}
                                            value={data.response}
                                            onChange={(event) =>
                                                setData(
                                                    'response',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Paste or write the reply that went out, so it is on the record."
                                            aria-invalid={Boolean(
                                                errors.response,
                                            )}
                                        />
                                        <InputError message={errors.response} />
                                        <div>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {inquiry.response
                                                    ? 'Update the reply'
                                                    : 'Record the reply'}
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <p className="text-sm whitespace-pre-line">
                                        {inquiry.response ??
                                            'No reply recorded yet.'}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="grid gap-6 lg:col-span-5">
                        <Card>
                            <CardHeader>
                                <CardTitle>How to reach them</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 text-sm">
                                <p className="font-medium">{inquiry.name}</p>
                                <a
                                    href={`mailto:${inquiry.email}`}
                                    className="flex items-center gap-2 underline-offset-4 hover:underline"
                                >
                                    <Mail className="size-4" aria-hidden />
                                    {inquiry.email}
                                </a>
                                {inquiry.phone && (
                                    <a
                                        href={`tel:${inquiry.phone}`}
                                        className="flex items-center gap-2 underline-offset-4 hover:underline"
                                    >
                                        <Phone className="size-4" aria-hidden />
                                        {inquiry.phone}
                                    </a>
                                )}
                                {inquiry.source_page && (
                                    <p className="text-xs text-muted-foreground">
                                        Sent from {inquiry.source_page}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {canManage && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Deal with it</CardTitle>
                                </CardHeader>
                                <CardContent className="grid gap-3">
                                    {inquiry.actionable && (
                                        <Button
                                            variant="outline"
                                            onClick={claim}
                                            disabled={
                                                inquiry.assigned_to !== null
                                            }
                                        >
                                            {inquiry.assigned_to === null
                                                ? 'Pick it up'
                                                : 'Somebody has it'}
                                        </Button>
                                    )}

                                    {inquiry.actionable ? (
                                        <>
                                            <Button
                                                variant="outline"
                                                onClick={() => file('closed')}
                                            >
                                                <Archive />
                                                Close it
                                            </Button>
                                            <Button
                                                variant="outline"
                                                onClick={() => file('spam')}
                                            >
                                                <ShieldAlert />
                                                File as spam
                                            </Button>
                                        </>
                                    ) : (
                                        <Button
                                            variant="outline"
                                            onClick={() => file(reopenAs)}
                                        >
                                            <ArchiveRestore />
                                            Reopen it
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

InquiryShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Inquiries', href: inquiries.index() },
    ],
};
