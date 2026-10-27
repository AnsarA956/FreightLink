<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountCodeController extends Controller
{
    public function index()
    {
        $codes = DiscountCode::withCount('shipments')->orderByDesc('id')->get();

        return view('admin.discount-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        // Codes are always saved in capital letters
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code'       => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', 'unique:discount_codes,code'],
            'percent'    => ['required', 'numeric', 'min:0.01', 'max:100'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'code.regex' => 'A code may contain only letters, numbers, a dash and an underscore.',
        ]);

        DiscountCode::create($data + ['is_active' => true]);

        return back()->with('success', 'Discount code ' . $data['code'] . ' created.');
    }

    public function update(Request $request, DiscountCode $discount_code)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code'       => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('discount_codes', 'code')->ignore($discount_code->id)],
            'percent'    => ['required', 'numeric', 'min:0.01', 'max:100'],
            'expires_on' => ['nullable', 'date'],
            'is_active'  => ['required', 'boolean'],
        ], [
            'code.regex' => 'A code may contain only letters, numbers, a dash and an underscore.',
        ]);

        $discount_code->update($data);

        return back()->with('success', 'Discount code updated.');
    }

    public function destroy(DiscountCode $discount_code)
    {
        if ($discount_code->shipments()->exists()) {
            return back()->withErrors([
                'delete' => 'This code was used on existing shipments and cannot be deleted. Switch it off instead.',
            ]);
        }

        $discount_code->delete();

        return back()->with('success', 'Discount code deleted.');
    }
}