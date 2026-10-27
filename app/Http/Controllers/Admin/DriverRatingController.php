<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Review;

class DriverRatingController extends Controller
{
    // REQ-46: the average rating per driver, and the latest reviews
    public function index()
    {
        $drivers = Driver::with('user')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderByDesc('reviews_avg_rating')
            ->get();

        $latest = Review::with(['shipment', 'user', 'driver.user'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.ratings.index', [
            'drivers' => $drivers,
            'latest'  => $latest,
            'overall' => round((float) Review::avg('rating'), 2),
            'total'   => Review::count(),
        ]);
    }
}