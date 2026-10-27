<?php

namespace App\Services;

use App\Models\CargoType;
use App\Models\DiscountCode;
use App\Models\PricingSetting;
use App\Models\ServiceType;

class PriceCalculator
{
    public function calculate(
        float $weightKg,
        float $length,
        float $width,
        float $height,
        float $distanceKm,
        ServiceType $service,
        CargoType $cargo,
        ?DiscountCode $code = null
    ): array {
        $rates = PricingSetting::current();

        // REQ-15: volumetric and chargeable weight
        $volumetric = round(($length * $width * $height) / 5000, 2);
        $chargeable = max($weightKg, $volumetric);

        // REQ-16: subtotal
        $ratePerKg      = (float) $rates->rate_per_kg;
        $ratePerKm      = (float) $rates->rate_per_km;
        $baseCharge     = (float) $rates->base_charge;
        $weightCharge   = round($chargeable * $ratePerKg, 2);
        $distanceCharge = round($distanceKm * $ratePerKm, 2);
        $subtotal       = round($baseCharge + $weightCharge + $distanceCharge, 2);

        // REQ-17: service multiplier and cargo surcharge
        $multiplier    = (float) $service->multiplier;
        $serviceAmount = round($subtotal * $multiplier, 2);
        $surcharge     = (float) $cargo->surcharge;

        // REQ-18: the discount is a percentage of the price before tax
        $beforeDiscount  = round($serviceAmount + $surcharge, 2);
        $discountPercent = $code ? (float) $code->percent : 0.0;
        $discount        = $code ? round($beforeDiscount * $discountPercent / 100, 2) : 0.0;

        // then tax
        $taxable    = round(max(0, $beforeDiscount - $discount), 2);
        $taxPercent = (float) $rates->tax_percent;
        $taxAmount  = round($taxable * $taxPercent / 100, 2);
        $total      = round($taxable + $taxAmount, 2);

        return [
            'weight_kg'          => $weightKg,
            'volumetric_kg'      => $volumetric,
            'chargeable_kg'      => $chargeable,
            'distance_km'        => $distanceKm,
            'base_charge'        => $baseCharge,
            'rate_per_kg'        => $ratePerKg,
            'rate_per_km'        => $ratePerKm,
            'weight_charge'      => $weightCharge,
            'distance_charge'    => $distanceCharge,
            'subtotal'           => $subtotal,
            'service_name'       => $service->name,
            'service_multiplier' => $multiplier,
            'service_amount'     => $serviceAmount,
            'cargo_name'         => $cargo->name,
            'cargo_surcharge'    => $surcharge,
            'discount_code_id'   => $code?->id,
            'discount_code'      => $code?->code,
            'discount_percent'   => $code ? $discountPercent : null,
            'discount_amount'    => $discount,
            'tax_percent'        => $taxPercent,
            'tax_amount'         => $taxAmount,
            'total_price'        => $total,
        ];
    }
}