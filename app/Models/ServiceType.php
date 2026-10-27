<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    protected $fillable = ['name', 'multiplier']; // COLUMN

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}