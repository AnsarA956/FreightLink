@extends('layouts.app')

@section('title', 'Discount Codes - FreightLink')

@section('content')
@php
    $field = 'border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Discount codes</h1>
        <p class="text-sm text-slate-500 mt-1">
            The percentage is taken off the price after the service multiplier and cargo surcharge, before tax.
            The expiry date is the last day the code works. Leave it empty for a code that never expires.
        </p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Add --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">➕ Add a code</h2>

        <form method="POST" action="{{ route('admin.discount-codes.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                <input type="text" name="code" value="{{ old('code') }}" placeholder="WELCOME10" class="{{ $field }} uppercase w-44" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Discount (%)</label>
                <input type="number" step="0.01" min="0.01" max="100" name="percent" value="{{ old('percent') }}" class="{{ $field }} w-32" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Expires on (optional)</label>
                <input type="date" name="expires_on" value="{{ old('expires_on') }}" class="{{ $field }}">
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2 rounded-lg transition text-sm">Add code</button>
        </form>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-1">All codes ({{ $codes->count() }})</h2>
        <p class="text-xs text-slate-500 mb-4">To change a code, edit the row and press Save.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Code</th>
                        <th class="py-2 pr-3">Discount (%)</th>
                        <th class="py-2 pr-3">Expires on</th>
                        <th class="py-2 pr-3">Switched on</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Used</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($codes as $code)
                        @php $expired = $code->expires_on && $code->expires_on->lt(today()); @endphp
                        <tr class="border-t border-slate-100">
                            <td class="py-3 pr-3">
                                <input form="update-{{ $code->id }}" type="text" name="code" value="{{ $code->code }}"
                                       class="{{ $field }} w-40 uppercase" required>
                            </td>
                            <td class="py-3 pr-3">
                                <input form="update-{{ $code->id }}" type="number" step="0.01" min="0.01" max="100" name="percent"
                                       value="{{ $code->percent }}" class="{{ $field }} w-28" required>
                            </td>
                            <td class="py-3 pr-3">
                                <input form="update-{{ $code->id }}" type="date" name="expires_on"
                                       value="{{ $code->expires_on?->format('Y-m-d') }}" class="{{ $field }}">
                            </td>
                            <td class="py-3 pr-3">
                                <input form="update-{{ $code->id }}" type="hidden" name="is_active" value="0">
                                <input form="update-{{ $code->id }}" type="checkbox" name="is_active" value="1" class="w-4 h-4" @checked($code->is_active)>
                            </td>
                            <td class="py-3 pr-3">
                                @if (! $code->is_active)
                                    <span class="bg-slate-200 text-slate-600 px-2.5 py-0.5 rounded-full text-xs font-medium">Switched off</span>
                                @elseif ($expired)
                                    <span class="bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Expired</span>
                                @else
                                    <span class="bg-green-100 text-green-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Working</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3">{{ $code->shipments_count }}</td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <form id="update-{{ $code->id }}" method="POST" action="{{ route('admin.discount-codes.update', $code) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs">Save</button>
                                </form>

                                <form method="POST" action="{{ route('admin.discount-codes.destroy', $code) }}" class="inline"
                                      onsubmit="return confirm('Delete this discount code?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="border border-red-300 text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No discount codes yet. Add the first one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection