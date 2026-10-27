<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    public const STATUSES = [
        'Pending Payment', 'Booked', 'Assigned', 'Picked Up',
        'In Transit', 'Delivered', 'Delivery Failed', 'Cancelled', 'Closed',
    ];

    protected $fillable = [
        'tracking_id', 'user_id', 'driver_id', 'vehicle_id',
        'service_type_id', 'cargo_type_id',
        'pickup_address', 'delivery_address',
        'pickup_lat', 'pickup_lng', 'delivery_lat', 'delivery_lng',
        'weight_kg', 'length_cm', 'width_cm', 'height_cm',
        'volumetric_kg', 'chargeable_kg', 'distance_km',
        'subtotal', 'service_multiplier', 'cargo_surcharge', 'discount_amount',
        'tax_percent', 'tax_amount', 'total_price',
        'discount_code_id', 'discount_code', 'discount_percent',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function cargoType(): BelongsTo
    {
        return $this->belongsTo(CargoType::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class)->orderBy('changed_at')->orderBy('id');
    }

    // The customer's review of this shipment (REQ-45)
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    // Every payment attempt, paid or failed
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // The successful payment, if there is one
    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', 'paid');
    }
}