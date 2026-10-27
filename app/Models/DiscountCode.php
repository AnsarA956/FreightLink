<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    protected $fillable = ['code', 'percent', 'expires_on', 'is_active'];

    protected $casts = [
        'percent'    => 'decimal:2',
        'expires_on' => 'date',
        'is_active'  => 'boolean',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // The code is valid when it is switched on and the expiry date (the last valid day) has not passed
    public function isUsable(): bool
    {
        return $this->is_active
            && ($this->expires_on === null || $this->expires_on->gte(today()));
    }
}