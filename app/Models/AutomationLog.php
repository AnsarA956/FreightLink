<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $fillable = ['task', 'level', 'shipment_id', 'message'];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}