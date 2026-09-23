{{--
    Structured data for the public website.

    The schema is assembled in App\Support\SitePresenter, not here, because Blade
    consumes a literal `@context` key as a directive and would inject compiled PHP
    in its place. Rendered server side rather than through Inertia's Head so
    crawlers always receive it, and only on public pages.
--}}
<script type="application/ld+json">
    {!! json_encode(
        \App\Support\SitePresenter::structuredData(),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
    ) !!}
</script>
