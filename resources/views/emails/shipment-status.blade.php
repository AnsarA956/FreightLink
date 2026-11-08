<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">
@php
    $titles = [
        'pending_payment'  => 'Payment Required for Your Shipment',
        'booked'           => 'Your booking is confirmed',
        'assigned'         => 'Driver & Vehicle Assigned',
        'picked_up'        => 'Your Shipment has been Picked Up',
        'in_transit'       => 'Your Shipment is On The Way',
        'delivered'        => 'Your Shipment has been Delivered',
        'delivery_failed'  => 'Delivery Attempt Unsuccessful',
        'cancelled'        => 'Your Shipment has been Cancelled',
        'closed'           => 'Your Shipment is Completed & Closed',
        'payment_reminder' => 'Payment Reminder for Your Shipment',
    ];

    $texts = [
        'pending_payment'  => 'Thank you for choosing FreightLink. Your shipment booking has been created and is waiting for payment. Please complete your payment so our dispatch team can assign a driver.',
        'booked'           => 'Thank you for booking with FreightLink. We have received your shipment request. It is now registered in our system and waiting for driver assignment.',
        'assigned'         => 'Great news! A dedicated driver and vehicle have been assigned to your shipment and are preparing for pickup.',
        'picked_up'        => 'Our driver has successfully picked up your cargo. It is now securely loaded and being routed towards its destination.',
        'in_transit'       => 'Your shipment is actively on the road and moving towards the delivery address.',
        'delivered'        => 'Your shipment has been successfully delivered. Thank you for shipping with FreightLink!',
        'delivery_failed'  => 'Our driver was unable to complete the delivery on the first attempt. Our support team will get in touch with you shortly to reschedule.',
        'cancelled'        => 'Your shipment has been cancelled. If you believe this cancellation occurred by mistake, please contact customer support.',
        'closed'           => 'Your shipment has been fully fulfilled and closed. We appreciate your trust in FreightLink.',
        'payment_reminder' => 'This is a gentle reminder that your shipment booking is currently unpaid. Please complete your payment using the link below to avoid automatic cancellation.',
    ];

    $title = $titles[$event] ?? 'Update on Your Shipment';
    $text  = $texts[$event] ?? 'There is a new update regarding your FreightLink shipment.';
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; max-width:560px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">

                <tr>
                    <td style="background:#0f172a; padding:18px 24px; color:#ffffff; font-size:20px; font-weight:bold; border-radius:8px 8px 0 0;">
                        FreightLink
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <h2 style="margin:0 0 12px 0; font-size:20px; color:#0f172a;">{{ $title }}</h2>
                        <p style="margin:0 0 8px 0; font-size:14px;">Hello {{ $shipment->user?->name ?? 'Customer' }},</p>
                        <p style="margin:0 0 16px 0; font-size:14px; line-height:1.5;">{{ $text }}</p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px; border-top:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding:8px 0; color:#64748b; width:120px;">Tracking ID</td>
                                <td style="padding:8px 0; font-weight:bold;">{{ $shipment->tracking_id }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">Status</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0; font-weight:bold; color:#0284c7;">{{ $shipment->status }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">From</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->pickup_address }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">To</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->delivery_address }}</td>
                            </tr>
                            @if ($shipment->serviceType)
                                <tr>
                                    <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">Service</td>
                                    <td style="padding:8px 0; border-top:1px solid #e2e8f0;">{{ $shipment->serviceType->name }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td style="padding:8px 0; color:#64748b; border-top:1px solid #e2e8f0;">Total Price</td>
                                <td style="padding:8px 0; border-top:1px solid #e2e8f0; font-weight:bold;">Rs {{ number_format($shipment->total_price, 2) }}</td>
                            </tr>
                        </table>

                        @if ($event === 'pending_payment' || $event === 'payment_reminder')
                            <p style="margin:24px 0 12px 0;">
                                <a href="{{ $payUrl }}"
                                   style="background:#16a34a; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:6px; font-size:14px; font-weight:bold; display:inline-block;">
                                    Pay Now (Rs {{ number_format($shipment->total_price, 2) }})
                                </a>
                            </p>
                            <p style="margin:0 0 16px 0;">
                                <a href="{{ $trackUrl }}" style="color:#0399b8; font-size:13px; text-decoration:none;">
                                    View tracking details &rarr;
                                </a>
                            </p>
                        @else
                            <p style="margin:24px 0;">
                                <a href="{{ $trackUrl }}"
                                   style="background:#0399b8; color:#ffffff; text-decoration:none; padding:10px 18px; border-radius:6px; font-size:14px; font-weight:bold; display:inline-block;">
                                    Track Your Shipment
                                </a>
                            </p>
                        @endif

                        @if ($event === 'delivered')
                            <p style="margin:0; font-size:13px; color:#475569;">
                                You can download your official invoice anytime by opening your shipment under "My Shipments".
                            </p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 24px; background:#f8fafc; font-size:12px; color:#64748b; border-radius:0 0 8px 8px;">
                        This is an automatic notification from FreightLink Logistics. Please do not reply to this email.
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
