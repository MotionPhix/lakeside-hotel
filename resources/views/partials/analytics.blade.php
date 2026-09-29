{{--
    Site measurement, and only when somebody has asked for it.

    With no provider set - which is how the site ships - this renders nothing at
    all and no request leaves a guest's browser. That matters more than it sounds:
    a hotel's visitors did not agree to be measured, and a tag that is convenient
    to switch on is one that gets left on.

    The first three providers here set no cookies and store no personal data.
    Google does both, which is why it is not the default and why the config
    comment lists it last. Its IP address is anonymised, which is the most the
    snippet can do on its own.

    Everything loads with `defer` or `async` so none of it delays the page, and
    none of it is on the staff screens, whose traffic is not what anybody wants
    to measure.

    Reads the settings the page was already sent rather than querying again.
--}}
@php
    $analytics = $page['props']['site']['analytics'] ?? [];
    $provider = (string) ($analytics['provider'] ?? '');
    $measurementId = (string) ($analytics['measurement_id'] ?? '');
    $measured = $measurementId !== ''
        && in_array($provider, ['plausible', 'fathom', 'umami', 'google'], true);
@endphp

@if ($measured)
    @if ($provider === 'plausible')
        <script defer data-domain="{{ $measurementId }}" src="https://plausible.io/js/script.js"></script>
    @elseif ($provider === 'fathom')
        <script defer src="https://cdn.usefathom.com/script.js" data-site="{{ $measurementId }}"></script>
    @elseif ($provider === 'umami')
        <script defer data-website-id="{{ $measurementId }}" src="https://cloud.umami.is/script.js"></script>
    @elseif ($provider === 'google')
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', @json($measurementId), { anonymize_ip: true });
        </script>
    @endif
@endif
