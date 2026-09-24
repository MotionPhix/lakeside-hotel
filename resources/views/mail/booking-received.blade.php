@extends('mail.layout')

@use('App\Support\Money')

@php
    /*
     * The desk's copy.
     *
     * Not a brochure: everything the front desk needs to act on the booking, in
     * the order they would ask for it, and nothing they would have to scroll past.
     * It wears the same chrome as the guest's confirmation because it is the same
     * hotel writing, and a desk that recognises the letter at a glance answers it
     * faster.
     */
    $adminUrl = route('admin.bookings.show', $booking->reference);
    $row = 'padding:10px 0;font-size:14px;border-bottom:1px solid #f0e9de;vertical-align:top;';
    $label = $row.'color:#5b6b7c;width:36%;';
    $value = $row.'color:#17324d;font-weight:600;';
    $eyebrow = 'margin:0 0 6px;font-size:12px;letter-spacing:.09em;text-transform:uppercase;color:#0f5e9c;font-weight:700;';
@endphp

@section('preheader')
    {{ $booking->reference }} — {{ $booking->guest->fullName() }} — {{ $booking->check_in->format('j M') }} to {{ $booking->check_out->format('j M Y') }}, {{ $booking->total }} {{ $booking->currency }}.
@endsection

@section('body')
    <tr>
        <td style="padding:30px 32px 0;">
            <p style="{{ $eyebrow }}">New booking</p>
            <p style="margin:0;font-size:24px;font-weight:600;letter-spacing:.01em;color:#17324d;">{{ $booking->reference }}</p>
            <p style="margin:8px 0 0;font-size:14px;color:#5b6b7c;">
                {{ $booking->status->label() }} · {{ $booking->source->label() }} · {{ $booking->payment_status->label() }}
            </p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 0;border-top:1px solid #e7dfd3;">
                <tr>
                    <td style="{{ $label }}">Guest</td>
                    <td style="{{ $value }}">{{ $booking->guest->fullName() }}</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Email</td>
                    <td style="{{ $row }}"><a href="mailto:{{ $booking->guest->email }}" style="color:#0f5e9c;text-decoration:none;">{{ $booking->guest->email }}</a></td>
                </tr>
                @if ($booking->guest->phone)
                    <tr>
                        <td style="{{ $label }}">Phone</td>
                        <td style="{{ $row }}"><a href="tel:{{ $booking->guest->phone }}" style="color:#0f5e9c;text-decoration:none;">{{ $booking->guest->phone }}</a></td>
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
                    <td style="{{ $value }}">{{ $booking->check_in->format('D j F Y') }}@if ($hotel['check_in']) from {{ $hotel['check_in'] }}@endif</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Departs</td>
                    <td style="{{ $value }}">{{ $booking->check_out->format('D j F Y') }}@if ($hotel['check_out']) by {{ $hotel['check_out'] }}@endif ({{ $booking->nights }} {{ Str::plural('night', $booking->nights) }})</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Party</td>
                    <td style="{{ $value }}">{{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}@if ($booking->children), {{ $booking->children }} {{ Str::plural('child', $booking->children) }}@endif</td>
                </tr>
                @foreach ($booking->items as $item)
                    <tr>
                        <td style="{{ $label }}">{{ $loop->first ? 'Room' : 'Also' }}</td>
                        <td style="{{ $value }}">{{ $item->roomType?->name ?? 'Room' }} — {{ Money::format($booking->currency, $item->subtotal) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td style="{{ $label }}">Total</td>
                    <td style="{{ $value }}">{{ Money::format($booking->currency, $booking->total) }}</td>
                </tr>
                <tr>
                    <td style="{{ $label }}">Paid</td>
                    <td style="{{ $row }}">
                        {{ Money::format($booking->currency, $booking->amount_paid) }}@if ((float) $booking->balance() > 0), balance <strong style="color:#17324d;">{{ Money::format($booking->currency, $booking->balance()) }}</strong>@endif
                    </td>
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
                <p style="margin:22px 0 0;{{ $eyebrow }}">Special requests</p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 0;">
                    <tr>
                        <td style="background:#faf7f2;border-left:3px solid #d9a441;border-radius:6px;padding:14px 18px;font-size:14px;color:#17324d;">{{ $booking->special_requests }}</td>
                    </tr>
                </table>
            @endif

            @if ($booking->airport_transfer && $booking->transfer_details)
                <p style="margin:22px 0 0;{{ $eyebrow }}">Transfer details</p>
                <p style="margin:8px 0 0;font-size:14px;color:#17324d;">
                    {{ collect($booking->transfer_details)->filter()->map(fn ($value, $key) => Str::headline($key).': '.$value)->implode(' · ') }}
                </p>
            @endif

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 0;">
                <tr>
                    <td style="background:#0f5e9c;border-radius:8px;">
                        <a href="{{ $adminUrl }}" style="display:inline-block;padding:13px 24px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">Open in the dashboard</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="height:30px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
@endsection

@section('footer-note')
    Confirm it, take payment or allocate a room from the reservation page.
@endsection
