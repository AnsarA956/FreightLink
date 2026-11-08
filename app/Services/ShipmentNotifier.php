<?php

namespace App\Services;

use App\Mail\ShipmentStatusMail;
use App\Models\NotificationLog;
use App\Models\Shipment;
use App\Models\StatusLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ShipmentNotifier
{
    // Which status change sends which notification (every status change triggers an email/sms)
    private const STATUS_EVENTS = [
        'Pending Payment' => 'pending_payment',
        'Booked' => 'booked',
        'Assigned' => 'assigned',
        'Picked Up' => 'picked_up',
        'In Transit' => 'in_transit',
        'Delivered' => 'delivered',
        'Delivery Failed' => 'delivery_failed',
        'Cancelled' => 'cancelled',
        'Closed' => 'closed',
    ];

    // A problem with email or SMS must never break a booking or a status update
    public function handle(StatusLog $log): void
    {
        try {
            $this->process($log);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function process(StatusLog $log): void
    {
        $event = self::STATUS_EVENTS[$log->status] ?? null;

        if ($event === null) {
            return;
        }

        $shipment = Shipment::with(['user', 'serviceType'])->find($log->shipment_id);

        if (! $shipment || ! $shipment->user) {
            return;
        }

        // Each channel runs on its own: a failed email must not stop the SMS
        foreach (['sendEmail', 'sendSms'] as $method) {
            try {
                $this->$method($shipment, $event);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    // Explicit payment reminder call (triggered by automation scheduler)
    public function sendPaymentReminder(Shipment $shipment): void
    {
        $shipment->loadMissing(['user', 'serviceType']);

        if (! $shipment->user) {
            return;
        }

        foreach (['sendEmail', 'sendSms'] as $method) {
            try {
                $this->$method($shipment, 'payment_reminder');
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    // One notification per channel, event and shipment (for payment_reminder, once per 48 hours)
    private function alreadyDone(Shipment $shipment, string $event, string $channel): bool
    {
        if ($event === 'payment_reminder') {
            return NotificationLog::where('shipment_id', $shipment->id)
                ->where('event', $event)
                ->where('channel', $channel)
                ->where('created_at', '>=', now()->subDays(2))
                ->whereIn('status', ['sent', 'skipped'])
                ->exists();
        }

        return NotificationLog::where('shipment_id', $shipment->id)
            ->where('event', $event)
            ->where('channel', $channel)
            ->whereIn('status', ['sent', 'skipped'])
            ->exists();
    }

    private function sendEmail(Shipment $shipment, string $event): void
    {
        if ($this->alreadyDone($shipment, $event, 'email')) {
            return;
        }

        $user = $shipment->user;
        $subject = $this->subject($event, $shipment);

        $record = [
            'shipment_id' => $shipment->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'event' => $event,
            'recipient' => $user->email,
            'subject' => $subject,
        ];

        // Walk-in customers without a real email cannot be emailed
        if (str_ends_with($user->email, '@freightlink.local')) {
            NotificationLog::create($record + [
                'status' => 'skipped',
                'error' => 'Walk-in customer without an email address.',
            ]);

            return;
        }

        try {
            Mail::to($user->email)->send(new ShipmentStatusMail($shipment, $event, $subject));

            NotificationLog::create($record + ['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            report($e);

            NotificationLog::create($record + [
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    private function sendSms(Shipment $shipment, string $event): void
    {
        if ($this->alreadyDone($shipment, $event, 'sms')) {
            return;
        }

        $user = $shipment->user;
        $sms = app(SmsService::class);
        $text = $this->smsText($event, $shipment);

        $number = $sms->normalize($user->phone ?? null);

        $record = [
            'shipment_id' => $shipment->id,
            'user_id' => $user->id,
            'channel' => 'sms',
            'event' => $event,
            'recipient' => $number ?? '-',
            'subject' => Str::limit($text, 250, ''),
        ];

        // Walk-in customers are still reached by SMS if the desk entered a phone number
        if ($number === null) {
            NotificationLog::create($record + [
                'status' => 'skipped',
                'error' => 'No valid phone number.',
            ]);

            return;
        }

        try {
            $sms->send($number, $text);

            NotificationLog::create($record + ['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $e) {
            report($e);

            NotificationLog::create($record + [
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }

    private function subject(string $event, Shipment $shipment): string
    {
        return match ($event) {
            'pending_payment' => 'Payment pending for shipment '.$shipment->tracking_id,
            'booked' => 'Booking confirmed - '.$shipment->tracking_id,
            'assigned' => 'Driver assigned to your shipment - '.$shipment->tracking_id,
            'picked_up' => 'Shipment picked up - '.$shipment->tracking_id,
            'in_transit' => 'Your shipment is in transit - '.$shipment->tracking_id,
            'delivered' => 'Your shipment was delivered - '.$shipment->tracking_id,
            'delivery_failed' => 'Delivery attempt update - '.$shipment->tracking_id,
            'cancelled' => 'Shipment cancelled - '.$shipment->tracking_id,
            'closed' => 'Shipment closed - '.$shipment->tracking_id,
            'payment_reminder' => 'Payment reminder: shipment '.$shipment->tracking_id.' awaiting payment',
            default => 'Shipment update - '.$shipment->tracking_id,
        };
    }

    // Keep each text under 160 characters so it fits a single SMS
    private function smsText(string $event, Shipment $shipment): string
    {
        $id = $shipment->tracking_id;

        return match ($event) {
            'pending_payment' => "FreightLink: shipment {$id} created. Total Rs ".number_format($shipment->total_price, 2).'. Complete payment to proceed.',
            'booked' => "FreightLink: shipment {$id} is booked. Total Rs ".number_format($shipment->total_price, 2).'.',
            'assigned' => "FreightLink: a driver has been assigned to shipment {$id}.",
            'picked_up' => "FreightLink: shipment {$id} has been picked up by our driver.",
            'in_transit' => "FreightLink: shipment {$id} is in transit towards delivery address.",
            'delivered' => "FreightLink: shipment {$id} was delivered. Thank you for choosing us.",
            'delivery_failed' => "FreightLink: delivery attempt for shipment {$id} was unsuccessful. Support will contact you.",
            'cancelled' => "FreightLink: shipment {$id} has been cancelled.",
            'closed' => "FreightLink: shipment {$id} is completed and closed.",
            'payment_reminder' => "FreightLink reminder: shipment {$id} is awaiting payment of Rs ".number_format($shipment->total_price, 2).'.',
            default => "FreightLink: update on shipment {$id} ({$shipment->status}).",
        };
    }
}
