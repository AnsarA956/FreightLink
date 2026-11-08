$shipment = Shipment::with('customer')->find($log->shipment_id);

Http::timeout(5)
    ->withHeaders(['X-FreightLink-Secret' => (string) config('n8n.secret')])
    ->post($url, [
        'event'          => 'shipment.status_changed',
        'shipment_id'    => $log->shipment_id,
        'tracking_id'    => $shipment?->tracking_id,
        'status'         => $log->status,
        'note'           => $log->note,
        'changed_at'     => (string) $log->changed_at,
        'customer_name'  => $shipment?->customer?->name,
        'customer_email' => $shipment?->customer?->email,
    ]);