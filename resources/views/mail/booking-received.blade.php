@php
    /*
     * A desk copy, not a brochure: everything the front desk needs to act on the
     * booking, in the order they would ask for it.
     */
    $money = fn (string|float|null $amount): string => $booking->currency.' '.number_format((float) $amount, 0, '.', ',');
    $row = 'padding:9px 0;font-size:14px;border-bottom:1px solid #eef1f5;vertical-align:top;';
    $label = $row.'color:#5b6b7c;width:34%;';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New booking {{ $booking->reference }}</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f7f8fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#17324d;line-height:1.5;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="background:#0f5e9c;padding:20px 28px;">
            <p style="margin:0;font-size:17px;font-weight:600;color:#ffffff;">New booking — {{ $booking->reference }}</p>
            <p style="margin:5px 0 0;font-size:13px;color:#e8f1f8;">
                {{ $booking->status->label() }} · {{ $booking->source->label() }} · {{ $booking->payment_status->label() }}
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:24px 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="{{ $label }}">Guest</td>
                    <td style="{{ $row }}">{{ $booking->guest->fullName() }}</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Email</td>
                    <td style="{{ $row }}">{{ $booking->guest->email }}</td>
                </tr>
                @if ($booking->guest->phone)
                    <tr>
                        <td style="{{ $label }}">Phone</td>
                        <td style="{{ $row }}">{{ $booking->guest->phone }}</td>
                    </tr>
                @endif
                @if ($booking->guest->country)
                    <tr>
                        <td style="{{ $label }}">Country</td>
                        <td style="{{ $row }}">{{ $booking->guest->country }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="{{ $label }}">Arrives</td>
                    <td style="{{ $row }}">{{ $booking->check_in->format('D j F Y') }}</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Departs</td>
                    <td style="{{ $row }}">{{ $booking->check_out->format('D j F Y') }} ({{ $booking->nights }} {{ Str::plural('night', $booking->nights) }})</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Party</td>
                    <td style="{{ $row }}">
                        {{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}@if ($booking->children), {{ $booking->children }} {{ Str::plural('child', $booking->children) }}@endif
                    </td>
                </tr>
                @foreach ($booking->items as $item)
                    <tr>
                        <td style="{{ $label }}">{{ $loop->first ? 'Room' : 'Also' }}</td>
                        <td style="{{ $row }}">{{ $item->roomType?->name }} — {{ $money($item->subtotal) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="{{ $label }}">Total</td>
                    <td style="{{ $row }}"><strong>{{ $money($booking->total) }}</strong></td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Paid</td>
                    <td style="{{ $row }}">{{ $money($booking->amount_paid) }}@if ((float) $booking->balance() > 0) — balance {{ $money($booking->balance()) }} @endif</td>
                </tr>
                @if ($booking->payment_method)
                    <tr>
                        <td style="{{ $label }}">Payment method</td>
                        <td style="{{ $row }}">{{ $booking->payment_method->label() }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="{{ $label }}">Airport transfer</td>
                    <td style="{{ $row }}">{{ $booking->airport_transfer ? 'Requested' : 'Not requested' }}</td>
                </tr>
            </table>

            @if ($booking->special_requests)
                <p style="margin:20px 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0f5e9c;font-weight:600;">Special requests</p>
                <p style="margin:0;padding:14px 16px;background:#f4efe8;border-radius:8px;font-size:14px;">{{ $booking->special_requests }}</p>
            @endif

            @if ($booking->airport_transfer && $booking->transfer_details)
                <p style="margin:20px 0 4px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#0f5e9c;font-weight:600;">Transfer details</p>
                <p style="margin:0;font-size:14px;">{{ collect($booking->transfer_details)->filter()->map(fn ($v, $k) => Str::headline($k).': '.$v)->implode(' · ') }}</p>
            @endif
        </td>
    </tr>

    <tr>
        <td style="padding:18px 28px;background:#f7f8fa;border-top:1px solid #e3e8ee;">
            <p style="margin:0;font-size:13px;color:#5b6b7c;">
                Open the booking in the dashboard to confirm it, take payment or allocate a room.
            </p>
        </td>
    </tr>
</table>

</body>
</html>
