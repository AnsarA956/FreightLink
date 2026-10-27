<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\StatusLog;

class DashboardController extends Controller
{
    // Shipments that are not finished yet
    private const ACTIVE = ['Pending Payment', 'Booked', 'Assigned', 'Picked Up', 'In Transit'];

    // After login, send the user to the panel that matches their role
    public function index()
    {
        $role = auth()->user()->role;

        return redirect()->route($role . '.dashboard');
    }

    public function customer()
    {
        $user = auth()->user();

        // A fresh query for this customer's shipments, every time it is called
        $mine = fn () => Shipment::where('user_id', $user->id);

        $stats = [
            'total'     => $mine()->where('status', '!=', 'Cancelled')->count(),
            'active'    => $mine()->whereIn('status', self::ACTIVE)->count(),
            'delivered' => $mine()->whereIn('status', ['Delivered', 'Closed'])->count(),
            'unpaid'    => $mine()->where('status', '!=', 'Cancelled')
                ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
                ->count(),
            'value'     => (float) $mine()->where('status', '!=', 'Cancelled')->sum('total_price'),
        ];

        // Delivered shipments that the customer has not rated yet
        $toRate = $mine()->whereIn('status', ['Delivered', 'Closed'])
            ->whereNotNull('driver_id')
            ->whereDoesntHave('review')
            ->latest()
            ->limit(5)
            ->get();

        $recent = $mine()->with(['serviceType', 'paidPayment'])->latest()->limit(6)->get();

        return view('dashboards.customer', compact('stats', 'toRate', 'recent'));
    }

    public function staff()
    {
        $user = auth()->user();

        $stats = [
            'today'           => Shipment::whereDate('created_at', today())->where('status', '!=', 'Cancelled')->count(),
            'waiting'         => Shipment::where('status', 'Booked')->whereNull('driver_id')->count(),
            'on_road'         => Shipment::whereIn('status', ['Picked Up', 'In Transit'])->count(),
            'delivered_today' => StatusLog::where('status', 'Delivered')->whereDate('changed_at', today())->count(),
            'walkins'         => StatusLog::where('note', 'like', 'Walk-in booking created by staff member ' . $user->name . '%')
                ->whereDate('changed_at', today())
                ->count(),
        ];

        // Shipments that need a person to look at them
        $attention = Shipment::with('user')
            ->where(function ($q) {
                $q->where('status', 'Delivery Failed')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'Booked')
                         ->whereNull('driver_id')
                         ->where('created_at', '<', now()->subDay());
                  });
            })
            ->latest()
            ->limit(6)
            ->get();

        $recent = Shipment::with(['user', 'driver.user'])->latest()->limit(8)->get();

        return view('dashboards.staff', compact('stats', 'attention', 'recent'));
    }

    public function admin()
    {
        return view('dashboards.panel', [
            'title'   => 'Admin Panel',
            'message' => 'Here you will manage users, rates, vehicles, drivers and reports.',
        ]);
    }

    public function driver()
    {
        return view('dashboards.panel', [
            'title'   => 'Driver Panel',
            'message' => 'Here you will see your assigned shipments and update their status.',
        ]);
    }
}