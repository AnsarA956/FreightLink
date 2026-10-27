<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingSetting;
use Illuminate\Http\Request;

class PricingSettingController extends Controller
{
    public function edit()
    {
        $setting = PricingSetting::current();

        return view('admin.pricing.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'base_charge' => ['required', 'numeric', 'min:0'],
            'rate_per_kg' => ['required', 'numeric', 'min:0'],
            'rate_per_km' => ['required', 'numeric', 'min:0'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        PricingSetting::current()->update($data);

        return back()->with('success', 'Pricing settings saved.');
    }
}