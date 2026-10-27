<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">
@php
    $titles = [
        'booked'     => 'Your booking is confirmed',
        'dispatched' => 'Your shipment is on its way',
        'delivered'  => 'Your shipment has been delivered',
    ];
    $texts = [
        'booked'     => 'Thank you for booking with FreightLink. We have received your shipment. It is now waiting for a driver to be assigned.',
        'dispatched' => 'Good news: your shipment has been picked up and is now on its way to the delivery address.',
        'delivered'  => 'Your shipment has been delivered. Thank you for choosing FreightLink.',
    ];
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; max-width:560px;">

                <tr>
                    <td style="background:#0f172a; padding:18px 24px; color:#ffffff; font-size:20px; font-weight:bold; border-radius:8px 8px 0 0;">
                        FreightLink
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <h2 style="margin:0 0 12px 0; font-size:20px; color:#0f172a;">{{ $titles[$event] }}</h2>
                        <p style="margin:0 0 8px 0; font-size:14px;">Hello {{ $shipment->user->name }},</p>
                        <p style="margin:0 0 16px 0; font-size:14px; line-height:1.5;">{{ $texts[$event] }}</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; border-top:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding:8px 0; color:#64748b; width:120px;">Tracking ID</td>
                                <td style="padding:8px 0; font-weight:bold;">{{ $shipment->tracking_id }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">From</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->pickup_address }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">To</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->delivery_address }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">Service</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->serviceType->name }}</td>
                            </tr>
                            @if ($event === 'booked')
                                <tr>
                                    <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">Total price</td>
                                    <td style="padding:8px 0; border-top:1px solid #e2e8f0; font-weight:bold;">Rs {{ number_format($shipment->total_price, 2) }}</td>
                                </tr>
                            @endif
                        </table>

                        <p style="margin:24px 0;">
                            <a href="{{ $trackUrl }}"
                               style="background:#0399b8; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px; font-size:14px; display:inline-block;">
                                Track your shipment
                            </a>
                        </p>

                        @if ($event === 'delivered')
                            <p style="margin:0; font-size:13px; color:#475569;">
                                You can download your invoice by logging in and opening your shipment under "My Shipments".
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px; background:#f8fafc; font-size:12px; color:#64748b; border-radius:0 0 8px 8px;">
                        This is an automatic message from FreightLink. Please do not reply to this email.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>