<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\Shipment;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShipmentCreator
{
    // Saves a shipment for the given customer, using the price that was calculated and shown
    public function create(User $customer, array $in, array $b, string $note): Shipment
    {
        return DB::transaction(function () use ($customer, $in, $b, $note) {
            // The code must still be valid at the moment of confirmation
            if (! empty($b['discount_code_id'])) {
                $code = DiscountCode::lockForUpdate()->find($b['discount_code_id']);

                if (! $code || ! $code->isUsable()) {
                    throw new RuntimeException('The discount code is no longer valid. Please calculate the price again.');
                }
            }

            $shipment = Shipment::create([
                'tracking_id'        => $this->newTrackingId(),
                'user_id'            => $customer->id,
                'service_type_id'    => $in['service_type_id'],
                'cargo_type_id'      => $in['cargo_type_id'],
                'pickup_address'     => $in['pickup_address'],
                'delivery_address'   => $in['delivery_address'],
                                'pickup_lat'         => $b['pickup_lat'] ?? null,
                'pickup_lng'         => $b['pickup_lng'] ?? null,
                'delivery_lat'       => $b['delivery_lat'] ?? null,
                'delivery_lng'       => $b['delivery_lng'] ?? null,
                'weight_kg'          => $in['weight_kg'],
                'length_cm'          => $in['length_cm'],
                'width_cm'           => $in['width_cm'],
                'height_cm'          => $in['height_cm'],
                'volumetric_kg'      => $b['volumetric_kg'],
                'chargeable_kg'      => $b['chargeable_kg'],
                'distance_km'        => $b['distance_km'],
                'subtotal'           => $b['subtotal'],
                'service_multiplier' => $b['service_multiplier'],
                'cargo_surcharge'    => $b['cargo_surcharge'],
                'discount_code_id'   => $b['discount_code_id'] ?? null,
                'discount_code'      => $b['discount_code'] ?? null,
                'discount_percent'   => $b['discount_percent'] ?? null,
                'discount_amount'    => $b['discount_amount'],
                'tax_percent'        => $b['tax_percent'],
                'tax_amount'         => $b['tax_amount'],
                'total_price'        => $b['total_price'],
                'status'             => 'Booked',
            ]);

            StatusLog::create([
                'shipment_id' => $shipment->id,
                'status'      => 'Booked',
                'note'        => $note,
                'changed_at'  => now(),
            ]);

            return $shipment;
        });
    }

    private function newTrackingId(): string
    {
        do {
            $id = 'FL-' . random_int(100000, 999999);
        } while (Shipment::where('tracking_id', $id)->exists());

        return $id;
    }
}