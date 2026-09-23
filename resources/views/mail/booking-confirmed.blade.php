@php
    /*
     * Styled inline throughout: mail clients strip <style> blocks often enough
     * that a stylesheet is not worth relying on.
     */
    $money = fn (string|float|null $amount): string => $booking->currency.' '.number_format((float) $amount, 0, '.', ',');
    $room = $booking->items->first()?->roomType?->name;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $hotel['name'] }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f7f8fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#17324d;line-height:1.55;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="background:#17324d;padding:26px 30px;">
            <p style="margin:0;font-size:19px;font-weight:600;color:#ffffff;">{{ $hotel['name'] }}</p>
            @if ($hotel['address'])
                <p style="margin:6px 0 0;font-size:13px;color:#f4efe8;">{{ $hotel['address'] }}</p>
            @endif
        </td>
    </tr>

    <tr>
        <td style="padding:30px;">
            <p style="margin:0 0 6px;font-size:15px;">Dear {{ $booking->guest->fullName() }},</p>

            <p style="margin:0 0 22px;font-size:15px;">
                @if ((float) $booking->amount_paid >= (float) $booking->total)
                    Your booking is confirmed and paid in full. We look forward to welcoming you to the lake.
                @else
                    Your booking is held. Your reference is below — please quote it if you get in touch.
                @endif
            </p>

            <p style="margin:0 0 6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0f5e9c;font-weight:600;">Reference</p>
            <p style="margin:0 0 24px;font-size:22px;font-weight:600;">{{ $booking->reference }}</p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e3e8ee;border-bottom:1px solid #e3e8ee;">
                <tr>
                    <td style="padding:14px 0;font-size:14px;color:#5b6b7c;">Check in</td>
                    <td style="padding:14px 0;font-size:14px;text-align:right;font-weight:600;">
                        {{ $booking->check_in->format('D j F Y') }} @if ($hotel['check_in']) from {{ $hotel['check_in'] }} @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:14px;color:#5b6b7c;">Check out</td>
                    <td style="padding:0 0 14px;font-size:14px;text-align:right;font-weight:600;">
                        {{ $booking->check_out->format('D j F Y') }} @if ($hotel['check_out']) by {{ $hotel['check_out'] }} @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 14px;font-size:14px;color:#5b6b7c;">Nights</td>
                    <td style="padding:0 0 14px;font-size:14px;text-align:right;font-weight:600;">{{ $booking->nights }}</td>
                </tr>
                @if ($room)
                    <tr>
                        <td style="padding:0 0 14px;font-size:14px;color:#5b6b7c;">Room</td>
                        <td style="padding:0 0 14px;font-size:14px;text-align:right;font-weight:600;">{{ $room }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:0 0 14px;font-size:14px;color:#5b6b7c;">Guests</td>
                    <td style="padding:0 0 14px;font-size:14px;text-align:right;font-weight:600;">
                        {{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}@if ($booking->children), {{ $booking->children }} {{ Str::plural('child', $booking->children) }}@endif
                    </td>
                </tr>
            </table>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;">
                <tr>
                    <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">Room and extras</td>
                    <td style="padding:0 0 8px;font-size:14px;text-align:right;">{{ $money($booking->subtotal) }}</td>
                </tr>
                @if ((float) $booking->discount_total > 0)
                    <tr>
                        <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">Discount</td>
                        <td style="padding:0 0 8px;font-size:14px;text-align:right;">−{{ $money($booking->discount_total) }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">VAT and tourism levy</td>
                    <td style="padding:0 0 8px;font-size:14px;text-align:right;">{{ $money($booking->tax_total) }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0 0;border-top:1px solid #e3e8ee;font-size:15px;font-weight:600;">Total</td>
                    <td style="padding:10px 0 0;border-top:1px solid #e3e8ee;font-size:15px;font-weight:600;text-align:right;">{{ $money($booking->total) }}</td>
                </tr>
                @if ((float) $booking->amount_paid > 0)
                    <tr>
                        <td style="padding:8px 0 0;font-size:14px;color:#5b6b7c;">Paid</td>
                        <td style="padding:8px 0 0;font-size:14px;text-align:right;">{{ $money($booking->amount_paid) }}</td>
                    </tr>
                @endif
                @if ((float) $booking->balance() > 0)
                    <tr>
                        <td style="padding:8px 0 0;font-size:15px;font-weight:600;">Balance</td>
                        <td style="padding:8px 0 0;font-size:15px;font-weight:600;text-align:right;">{{ $money($booking->balance()) }}</td>
                    </tr>
                @endif
            </table>

            @if ((float) $booking->balance() > 0)
                <p style="margin:22px 0 0;padding:16px 18px;background:#f4efe8;border-radius:8px;font-size:14px;">
                    The balance of <strong>{{ $money($booking->balance()) }}</strong> is payable on arrival. We accept card, Airtel Money, TNM Mpamba and cash at the desk.
                </p>
            @endif

            @if ($booking->airport_transfer)
                <p style="margin:16px 0 0;font-size:14px;color:#5b6b7c;">
                    An airport transfer has been requested. Our team will confirm the details with you before you travel.
                </p>
            @endif

            @if ($booking->special_requests)
                <p style="margin:16px 0 0;font-size:14px;color:#5b6b7c;">
                    <strong style="color:#17324d;">Your requests:</strong> {{ $booking->special_requests }}
                </p>
            @endif
        </td>
    </tr>

    <tr>
        <td style="padding:0 30px 30px;">
            <p style="margin:0 0 6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0f5e9c;font-weight:600;">Good to know</p>
            <p style="margin:0 0 8px;font-size:13px;color:#5b6b7c;">{{ $cancellationPolicy }}</p>
            <p style="margin:0;font-size:13px;color:#5b6b7c;">{{ $childPolicy }}</p>
        </td>
    </tr>

    <tr>
        <td style="padding:22px 30px;background:#f7f8fa;border-top:1px solid #e3e8ee;">
            <p style="margin:0 0 6px;font-size:13px;color:#5b6b7c;">
                @if ($hotel['phone']) {{ $hotel['phone'] }} @endif
                @if ($hotel['phone'] && $hotel['email']) · @endif
                @if ($hotel['email']) {{ $hotel['email'] }} @endif
            </p>
            @if ($hotel['website'])
                <p style="margin:0;font-size:13px;color:#5b6b7c;">{{ $hotel['website'] }}</p>
            @endif
        </td>
    </tr>
</table>

</body>
</html>
