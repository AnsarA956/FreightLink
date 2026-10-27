<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CargoType extends Model
{
    protected $fillable = ['name', 'surcharge']; // COLUMN: same name as in the controller

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}