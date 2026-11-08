<?php

namespace App\Mail;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShipmentUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Shipment Update: '.$this->shipment->tracking_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shipment-update',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
