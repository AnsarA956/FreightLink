<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ShipmentStatusMail extends Mailable
{
    public function __construct(
        public Shipment $shipment,
        public string $event,
        public string $subjectLine
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-status',
            with: [
                'shipment' => $this->shipment,
                'event'    => $this->event,
                'trackUrl' => route('track.show', $this->shipment->tracking_id),
            ],
        );
    }
}