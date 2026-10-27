<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        $totals = Payment::selectRaw("
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as collected,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed")
            ->first();

        $payments = Payment::with(['shipment', 'user'])
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', [
            'payments'  => $payments,
            'collected' => (float) $totals->collected,
            'paid'      => (int) $totals->paid,
            'failed'    => (int) $totals->failed,
        ]);
    }
}