<?php

namespace App\Models;

use App\Services\ShipmentNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StatusLog extends Model
{
    protected $fillable = ['shipment_id', 'status', 'note', 'changed_at'];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // REQ-38: every new status entry may trigger an email to the customer
        static::created(function (StatusLog $log) {
            $send       = fn () => app(ShipmentNotifier::class)->handle($log);
            $connection = DB::connection();

            // If we are inside a database transaction, wait until it is saved before sending
            if ($connection->transactionLevel() > 0 && method_exists($connection, 'afterCommit')) {
                $connection->afterCommit($send);
            } else {
                $send();
            }
        });
    }
}