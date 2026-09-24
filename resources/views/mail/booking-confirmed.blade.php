@extends('mail.layout')

@use('App\Support\Money')

@php
    /*
     * The guest's copy of a reservation.
     *
     * Read on a phone an hour after booking, usually, so the order is what a
     * person actually wants in that moment: what this is, what the reference is,
     * when they arrive, what it costs, and what is still to pay. Everything the
     * hotel needs them to know is below that.
     */
    $room = $booking->items->first()?->roomType?->name;
    $settled = (float) $booking->amount_paid >= (float) $booking->total;
    $balance = (float) $booking->balance();
    $bookingUrl = route('site.booking.show', $booking->reference);

    $eyebrow = 'margin:0 0 6px;font-size:12px;letter-spacing:.09em;text-transform:uppercase;color:#0f5e9c;font-weight:700;';
    $lead = 'margin:0 0 4px;font-size:15px;color:#17324d;';
    $detailLabel = 'padding:12px 0;font-size:14px;color:#5b6b7c;';
    $detailValue = 'padding:12px 0;font-size:14px;text-align:right;font-weight:600;color:#17324d;';
@endphp

@section('preheader')
    Your reference is {{ $booking->reference }}: {{ $booking->nights }} {{ Str::plural('night', $booking->nights) }} from {{ $booking->check_in->format('j F Y') }}.
@endsection

@section('body')
    <tr>
        <td style="padding:30px 32px 0;">
            <p style="{{ $lead }}">Dear {{ $booking->guest->fullName() }},</p>

            <p style="margin:14px 0 0;font-size:17px;font-weight:600;color:#17324d;">
                @if ($settled)
                    Your stay is booked and paid in full.
                @else
                    Your stay is booked.
                @endif
            </p>

            <p style="margin:8px 0 0;font-size:15px;color:#5b6b7c;">
                @if ($settled)
                    We look forward to welcoming you to the lake. Everything you need is below.
                @else
                    Your room is held for the dates below. Quote the reference if you get in touch.
                @endif
            </p>

            {{-- The one thing a guest is asked to keep hold of. --}}
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;">
                <tr>
                    <td style="background:#faf7f2;border-left:3px solid #d9a441;border-radius:6px;padding:16px 18px;">
                        <p style="{{ $eyebrow }}">Your reference</p>
                        <p style="margin:0;font-size:24px;font-weight:600;letter-spacing:.01em;color:#17324d;">{{ $booking->reference }}</p>
                    </td>
                </tr>
            </table>

            {{-- Arrival, departure and the party. --}}
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 0;border-top:1px solid #e7dfd3;border-bottom:1px solid #e7dfd3;">
                <tr>
                    <td style="{{ $detailLabel }}">Check in</td>
                    <td style="{{ $detailValue }}">{{ $booking->check_in->format('D j F Y') }}@if ($hotel['check_in']) from {{ $hotel['check_in'] }}@endif</td>
                </tr>
                <tr>
                    <td style="padding:0 0 12px;font-size:14px;color:#5b6b7c;">Check out</td>
                    <td style="padding:0 0 12px;font-size:14px;text-align:right;font-weight:600;color:#17324d;">{{ $booking->check_out->format('D j F Y') }}@if ($hotel['check_out']) by {{ $hotel['check_out'] }}@endif</td>
                </tr>
                <tr>
                    <td style="padding:0 0 12px;font-size:14px;color:#5b6b7c;">Nights</td>
                    <td style="padding:0 0 12px;font-size:14px;text-align:right;font-weight:600;color:#17324d;">{{ $booking->nights }}</td>
                </tr>
                @if ($room)
                    <tr>
                        <td style="padding:0 0 12px;font-size:14px;color:#5b6b7c;">Room</td>
                        <td style="padding:0 0 12px;font-size:14px;text-align:right;font-weight:600;color:#17324d;">{{ $room }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:0 0 12px;font-size:14px;color:#5b6b7c;">Guests</td>
                    <td style="padding:0 0 12px;font-size:14px;text-align:right;font-weight:600;color:#17324d;">{{ $booking->adults }} {{ Str::plural('adult', $booking->adults) }}@if ($booking->children), {{ $booking->children }} {{ Str::plural('child', $booking->children) }}@endif</td>
                </tr>
            </table>

            {{-- What it costs, and what is left of it. --}}
            <p style="margin:24px 0 0;{{ $eyebrow }}">What it costs</p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 0;">
                <tr>
                    <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">Room and extras</td>
                    <td style="padding:0 0 8px;font-size:14px;text-align:right;color:#17324d;">{{ Money::format($booking->currency, $booking->subtotal) }}</td>
                </tr>
                @if ((float) $booking->discount_total > 0)
                    <tr>
                        <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">Discount</td>
                        <td style="padding:0 0 8px;font-size:14px;text-align:right;color:#17324d;">−{{ Money::format($booking->currency, $booking->discount_total) }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:0 0 8px;font-size:14px;color:#5b6b7c;">VAT and tourism levy</td>
                    <td style="padding:0 0 8px;font-size:14px;text-align:right;color:#17324d;">{{ Money::format($booking->currency, $booking->tax_total) }}</td>
                </tr>
                <tr>
                    <td style="padding:12px 0 0;border-top:1px solid #e7dfd3;font-size:16px;font-weight:600;color:#17324d;">Total</td>
                    <td style="padding:12px 0 0;border-top:1px solid #e7dfd3;font-size:16px;font-weight:600;text-align:right;color:#17324d;">{{ Money::format($booking->currency, $booking->total) }}</td>
                </tr>
                @if ((float) $booking->amount_paid > 0)
                    <tr>
                        <td style="padding:8px 0 0;font-size:14px;color:#5b6b7c;">Paid</td>
                        <td style="padding:8px 0 0;font-size:14px;text-align:right;color:#17324d;">{{ Money::format($booking->currency, $booking->amount_paid) }}</td>
                    </tr>
                @endif
                @if ($balance > 0)
                    <tr>
                        <td style="padding:8px 0 0;font-size:15px;font-weight:600;color:#17324d;">Still to pay</td>
                        <td style="padding:8px 0 0;font-size:15px;font-weight:600;text-align:right;color:#17324d;">{{ Money::format($booking->currency, $balance) }}</td>
                    </tr>
                @endif
            </table>

            @if ($balance > 0)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 0;">
                    <tr>
                        <td style="background:#faf7f2;border-left:3px solid #d9a441;border-radius:6px;padding:15px 18px;font-size:14px;color:#17324d;">
                            {{ Money::format($booking->currency, $balance) }} is payable on arrival. We take card, Airtel Money, TNM Mpamba and cash at the desk.
                        </td>
                    </tr>
                </table>
            @endif

            {{-- What they asked for, so they can see we read it. --}}
            @if ($booking->airport_transfer)
                <p style="margin:18px 0 0;font-size:14px;color:#5b6b7c;">
                    <strong style="color:#17324d;">Airport transfer:</strong> requested. We will confirm the details with you before you travel.
                </p>
            @endif

            @if ($booking->special_requests)
                <p style="margin:12px 0 0;font-size:14px;color:#5b6b7c;">
                    <strong style="color:#17324d;">Your requests:</strong> {{ $booking->special_requests }}
                </p>
            @endif

            {{-- Somewhere to go next, and the address in plain text for anybody whose client strips buttons. --}}
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 0;">
                <tr>
                    <td style="background:#0f5e9c;border-radius:8px;">
                        <a href="{{ $bookingUrl }}" style="display:inline-block;padding:13px 24px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">View your booking</a>
                    </td>
                </tr>
            </table>

            <p style="margin:10px 0 0;font-size:12px;color:#5b6b7c;">
                <a href="{{ $bookingUrl }}" style="color:#0f5e9c;text-decoration:none;">{{ $bookingUrl }}</a>
            </p>

            <p style="margin:26px 0 0;{{ $eyebrow }}">Good to know</p>
            <p style="margin:10px 0 0;font-size:14px;color:#5b6b7c;">{{ $cancellationPolicy }}</p>
            <p style="margin:8px 0 0;font-size:14px;color:#5b6b7c;">{{ $childPolicy }}</p>
        </td>
    </tr>
    <tr>
        <td style="height:30px;font-size:0;line-height:0;">&nbsp;</td>
    </tr>
@endsection

@section('footer-note')
    Sent because you booked a stay with us. Quote {{ $booking->reference }} if you get in touch.
@endsection
