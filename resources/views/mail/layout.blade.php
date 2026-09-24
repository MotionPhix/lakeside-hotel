@php
    /*
     * The chrome every email the hotel sends is built on.
     *
     * Two things shape it. Mail clients strip stylesheets often enough that a
     * stylesheet is not worth relying on, so everything is inline and the layout
     * is nested tables rather than divs and flex. And an email is read in a
     * window the width of a phone as often as not, so the card is a single column
     * that cannot overflow: no fixed widths, no side-by-side columns.
     *
     * The colours are the hotel's own - navy, lake blue, sand and one line of gold
     * - so a confirmation looks like the website it came from.
     */
    $ink = '#17324d';
    $muted = '#5b6b7c';
    $accent = '#0f5e9c';
    $rule = '#e7dfd3';
    $sand = '#f4efe8';
    $gold = '#d9a441';

    $font = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $hotel['name'] }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $sand }};font-family:{{ $font }};color:{{ $ink }};line-height:1.6;-webkit-text-size-adjust:100%;">

{{--
    The line an inbox shows next to the subject. It is pushed off the screen
    rather than hidden, because the clients that show preview text are exactly the
    ones that ignore `display:none`.
--}}
<div style="display:none;font-size:1px;color:{{ $sand }};line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">@yield('preheader')</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $sand }};padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid {{ $rule }};border-radius:14px;overflow:hidden;">

                {{-- The hotel, said the way the sign says it. --}}
                <tr>
                    <td style="background:{{ $ink }};padding:26px 32px 22px;">
                        <p style="margin:0;font-size:20px;font-weight:600;letter-spacing:-.01em;color:#ffffff;">{{ $hotel['name'] }}</p>
                        @if ($hotel['address'])
                            <p style="margin:7px 0 0;font-size:13px;color:#e8dcc8;">{{ $hotel['address'] }}</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="height:3px;background:{{ $gold }};font-size:0;line-height:0;">&nbsp;</td>
                </tr>

                @yield('body')

                {{-- How to reach the hotel, and why this arrived. --}}
                <tr>
                    <td style="padding:22px 32px;background:#faf7f2;border-top:1px solid {{ $rule }};">
                        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:{{ $ink }};">{{ $hotel['name'] }}</p>
                        @if ($hotel['address'])
                            <p style="margin:0 0 8px;font-size:13px;color:{{ $muted }};">{{ $hotel['address'] }}</p>
                        @endif

                        @php
                            $reach = array_filter([
                                $hotel['phone'] ? '<a href="tel:'.e($hotel['phone']).'" style="color:'.$accent.';text-decoration:none;">'.e($hotel['phone']).'</a>' : null,
                                $hotel['email'] ? '<a href="mailto:'.e($hotel['email']).'" style="color:'.$accent.';text-decoration:none;">'.e($hotel['email']).'</a>' : null,
                                $hotel['website'] ? '<a href="'.e($hotel['website']).'" style="color:'.$accent.';text-decoration:none;">'.e($hotel['website']).'</a>' : null,
                            ]);
                        @endphp

                        @if ($reach)
                            <p style="margin:0;font-size:13px;color:{{ $muted }};">{!! implode(' &nbsp;·&nbsp; ', $reach) !!}</p>
                        @endif

                        <p style="margin:12px 0 0;font-size:12px;color:{{ $muted }};">@yield('footer-note')</p>
                    </td>
                </tr>
            </table>

            <p style="margin:14px auto 0;max-width:600px;font-size:12px;color:{{ $muted }};">
                {{ $hotel['name'] }}@if ($hotel['address']), {{ $hotel['address'] }}@endif
            </p>
        </td>
    </tr>
</table>

</body>
</html>
