<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['sent', 'failed', 'skipped'])],
            'event'  => ['nullable', Rule::in(['booked', 'dispatched', 'delivered'])],
        ]);

        $logs = NotificationLog::with('shipment')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.notifications.index', compact('logs'));
    }
}