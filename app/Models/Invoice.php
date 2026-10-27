<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['shipment_id', 'invoice_no', 'issued_at'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];
}