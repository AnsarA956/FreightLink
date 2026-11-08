<h2>Hello {{ $shipment->user->name ?? 'Customer' }},</h2>

<p>
    Your shipment <strong>{{ $shipment->tracking_id }}</strong> status is now:
    <strong>{{ $shipment->status }}</strong>
</p>

<p>
    Pickup: {{ $shipment->pickup_address }}<br>
    Delivery: {{ $shipment->delivery_address }}
</p>

<p>
    Track it here:
    <a href="{{ url('/track/' . $shipment->tracking_id) }}">
        {{ url('/track/' . $shipment->tracking_id) }}
    </a>
</p>

<p>Thank you for choosing FreightLink.</p>