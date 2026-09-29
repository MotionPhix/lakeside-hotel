{{--
    Structured data that belongs to one page rather than to the whole site.

    A room's own page is the only one that has any today: the site-wide schema
    says what the hotel is, and this says what the room is - its bed, its size,
    its occupancy and its nightly rate. Both are emitted, and search engines
    treat them as two descriptions of the same place rather than a conflict.

    Assembled in App\Support\PageSeo and passed in, for the same reason the hotel
    schema is assembled in SitePresenter: Blade reads a literal `@context` key as
    a directive and would compile PHP in its place.
--}}
<script type="application/ld+json">
    {!! json_encode(
        $schema,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
    ) !!}
</script>
