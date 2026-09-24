/** The kinds of control the generic form knows how to render. */
export type CmsFieldType =
    | 'text'
    | 'textarea'
    | 'slug'
    | 'number'
    | 'money'
    | 'boolean'
    | 'select'
    | 'date'
    | 'icon'
    | 'list'
    | 'map'
    | 'relation'
    | 'media';

export type CmsField = {
    name: string;
    label: string;
    type: CmsFieldType;
    /** value => label, for select. */
    options: Record<string, string>;
    required: boolean;
    help: string | null;
    /** Collection name, for media fields. */
    collection: string | null;
};

/** One content type, as the backend describes it. */
export type CmsResource = {
    key: string;
    label: string;
    singular: string;
    group: string;
    description: string;
    permission: string;
    /** Column order for the list; each name is also a field. */
    columns: string[];
    fields: CmsField[];
    searchable: string[];
    sortable: boolean;
    publishable: boolean;
    publish_column: string | null;
    media: Record<string, { label: string; multiple: boolean }>;
};

export type CmsRow = {
    id: number;
    title: string;
    /** Null where the content type is not published by a boolean. */
    published: boolean | null;
    values: Record<string, string>;
};

export type CmsMediaItem = {
    id: number;
    url: string;
    thumb: string;
    name: string;
};

/** Media keyed by record id, then by collection. */
export type CmsMedia = Record<string, Record<string, CmsMediaItem[]>>;

export type CmsValue =
    | string
    | number
    | boolean
    | string[]
    | Record<string, string | number>
    | null;

export type CmsValues = Record<string, CmsValue>;

export type CmsRelationOption = {
    value: string;
    label: string;
};

/** Relation choices keyed by field name. */
export type CmsRelations = Record<string, CmsRelationOption[]>;

export type CmsHubGroup = {
    group: string;
    resources: {
        key: string;
        label: string;
        singular: string;
        description: string;
        count: number;
    }[];
};
