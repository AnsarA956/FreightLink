<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    public const STATUSES = ['Available', 'On Delivery', 'Off Duty'];

    protected $fillable = ['user_id', 'license_no', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The reviews customers gave for this driver's deliveries (REQ-46)
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Average rating and number of reviews, in one query
    public function ratingSummary(): array
    {
        $row = $this->reviews()
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        return [
            'average' => $row->total > 0 ? round((float) $row->average, 2) : null,
            'count'   => (int) $row->total,
        ];
    }
}