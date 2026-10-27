<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingSetting extends Model
{
    protected $fillable = [
        'base_charge',
        'rate_per_kg',
        'rate_per_km',
        'tax_percent',
    ];

    // There is only ever ONE row of settings. This returns it, or creates it if missing.
    public static function current(): self
    {
        return static::first() ?? static::create([
            'base_charge' => 200,
            'rate_per_kg' => 50,
            'rate_per_km' => 10,
            'tax_percent' => 5,
        ]);
    }
}