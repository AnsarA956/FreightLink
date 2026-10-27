<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    public const TYPES = ['Bike', 'Pickup', 'Van', 'Truck'];

    protected $fillable = ['plate_no', 'type', 'capacity_kg'];
}