import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ImagePlus, Save, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { ResourceField } from '@/components/admin/resource-field';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import contentRoutes from '@/routes/admin/content';
import type {
    CmsMedia,
    CmsRelations,
    CmsResource,
    CmsRow,
    CmsValue,
    CmsValues,
} from '@/types';

type Props = {
    resource: CmsResource;
    record: CmsRow | null;
    values: CmsValues;
    relations: CmsRelations;
    media: CmsMedia;
};

/**
 * The create and edit form for any content type.
 *
 * The fields come from the schema in order, so a content type that gains a
 * column gains a control here without this file changing. Media is handled apart
 * from the fields because an upload is its own request, which is what lets a
 * large photograph save without holding up the rest of the form.
 */
export default function ContentForm({
    resource,
    record,
    values: initial,
    relations,
    media,
}: Props) {
    const editing = record !== null;

    const { data, setData, post, put, processing, errors } =
        useForm<CmsValues>(initial);

    const [confirming, setConfirming] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (editing) {
            put(
                contentRoutes.update.url({
                    resource: resource.key,
                    id: record.id,
                }),
                { preserveScroll: true },
            );

            return;
        }

        post(contentRoutes.store.url(resource.key), { preserveScroll: true });
    };

    const attached = editing
        ? Object.entries(media[String(record.id)] ?? {})
        : [];

    return (
        <>
            <Head
                title={
                    editing
                        ? `Edit ${record.title}`
                        : `Add ${resource.singular}`
                }
            />

            <div className="flex flex-col gap-6">
                <div className="space-y-2">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link href={contentRoutes.index(resource.key)}>
                            <ArrowLeft />
                            {resource.label}
                        </Link>
                    </Button>
                    <Heading
                        title={
                            editing
                                ? record.title
                                : `Add ${resource.singular.toLowerCase()}`
                        }
                        description={resource.description}
                    />
                </div>

                <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardContent className="grid gap-5">
                                {resource.fields
                                    .filter((field) => field.type !== 'media')
                                    .map((field) => (
                                        <ResourceField
                                            key={field.name}
                                            field={field}
                                            value={
                                                (data[field.name] ??
                                                    null) as CmsValue
                                            }
                                            error={errors[field.name]}
                                            options={relations[field.name]}
                                            onChange={(value) =>
                                                setData(field.name, value)
                                            }
                                        />
                                    ))}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Card>
                            <CardContent className="flex flex-col gap-3">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    <Save />
                                    {processing ? 'Saving…' : 'Save'}
                                </Button>

                                {editing && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="w-full"
                                        onClick={() => setConfirming(true)}
                                    >
                                        <Trash2 />
                                        Delete
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {Object.entries(resource.media).map(
                            ([collection, details]) => (
                                <MediaCard
                                    key={collection}
                                    resourceKey={resource.key}
                                    recordId={editing ? record.id : null}
                                    collection={collection}
                                    label={details.label}
                                    multiple={details.multiple}
                                    items={
                                        attached.find(
                                            ([name]) => name === collection,
                                        )?.[1] ?? []
                                    }
                                />
                            ),
                        )}
                    </div>
                </form>
            </div>

            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Delete {editing ? record.title : 'this record'}?
                        </DialogTitle>
                        <DialogDescription>
                            It is removed from the website straight away.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            type="button"
                            onClick={() => setConfirming(false)}
                        >
                            Keep it
                        </Button>
                        <Button
                            variant="destructive"
                            type="button"
                            onClick={() => {
                                if (editing) {
                                    router.delete(
                                        contentRoutes.destroy.url({
                                            resource: resource.key,
                                            id: record.id,
                                        }),
                                    );
                                }
                            }}
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

/**
 * One media collection: what is already attached, and a way to add or replace.
 *
 * A single-file collection replaces what is there, because the backend clears it
 * first - the label says so rather than letting it look like a second upload.
 */
function MediaCard({
    resourceKey,
    recordId,
    collection,
    label,
    multiple,
    items,
}: {
    resourceKey: string;
    /** Null while the record is still being created: there is nothing to attach to yet. */
    recordId: number | null;
    collection: string;
    label: string;
    multiple: boolean;
    items: { id: number; url: string; thumb: string; name: string }[];
}): ReactNode {
    const url =
        recordId === null
            ? null
            : contentRoutes.media.store.url({
                  resource: resourceKey,
                  id: recordId,
              });

    const { data, setData, post, processing, errors, reset } = useForm<{
        collection: string;
        file: File | null;
    }>({
        collection,
        file: null,
    });

    const upload = (file: File) => {
        setData('file', file);
    };

    const send = (event: FormEvent) => {
        event.preventDefault();

        if (url === null || data.file === null) {
            return;
        }

        post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset('file'),
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm">{label}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {items.length > 0 && (
                    <div className="grid grid-cols-2 gap-2">
                        {items.map((item) => (
                            <div key={item.id} className="space-y-1">
                                <img
                                    src={item.thumb}
                                    alt=""
                                    loading="lazy"
                                    className="h-20 w-full rounded object-cover"
                                />
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="w-full"
                                    type="button"
                                    onClick={() =>
                                        router.delete(
                                            contentRoutes.media.destroy.url({
                                                resource: resourceKey,
                                                id: recordId as number,
                                                mediaId: item.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    Remove
                                </Button>
                            </div>
                        ))}
                    </div>
                )}

                {url === null ? (
                    <p className="text-xs text-muted-foreground">
                        Save this record first, then add {label.toLowerCase()}.
                    </p>
                ) : (
                    <form onSubmit={send} className="space-y-2">
                        <Label
                            htmlFor={`file-${collection}`}
                            className="text-xs text-muted-foreground"
                        >
                            {multiple ? 'Add an image' : 'Replace the image'}
                        </Label>
                        <Input
                            id={`file-${collection}`}
                            type="file"
                            accept="image/*"
                            onChange={(event) =>
                                upload(event.target.files?.[0] as File)
                            }
                        />
                        {errors.file && (
                            <p className="text-xs text-destructive">
                                {errors.file}
                            </p>
                        )}
                        <Button
                            type="submit"
                            size="sm"
                            variant="outline"
                            disabled={processing || data.file === null}
                            className="w-full"
                        >
                            <ImagePlus />
                            {processing ? 'Uploading…' : 'Upload'}
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

ContentForm.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Website content', href: contentRoutes.hub() },
    ],
};
