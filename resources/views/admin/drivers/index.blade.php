@extends('layouts.app')

@section('title', 'Drivers - FreightLink')

@section('content')
@php
    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';

    $statusBadge = function ($status) {
        $s = strtolower((string) $status);

        if (str_contains($s, 'available')) { return 'bg-green-100 text-green-800'; }
        if (str_contains($s, 'deliver') || str_contains($s, 'busy')) { return 'bg-amber-100 text-amber-800'; }

        return 'bg-slate-200 text-slate-600';
    };
@endphp

<div class="space-y-6">

    {{-- Heading --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Drivers</h1>
        <p class="text-sm text-slate-500 mt-1">Each driver gets a login account. Deactivating blocks the driver from logging in but keeps their records.</p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Add a driver --}}
    <details class="bg-white rounded-2xl border border-slate-200 shadow-sm" {{ $errors->any() ? 'open' : '' }}>
        <summary class="cursor-pointer select-none px-6 py-4 font-semibold text-slate-900">➕ Add a driver</summary>

        <form method="POST" action="{{ route('admin.drivers.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 px-6 pb-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password (min 8)</label>
                <input type="password" name="password" class="{{ $field }}" required autocomplete="new-password">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Licence no.</label>
                <input type="text" name="license_no" value="{{ old('license_no') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="{{ $field }}" required>
                    @foreach (\App\Models\Driver::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Add driver</button>
            </div>
        </form>
    </details>

    {{-- The list --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">All drivers ({{ $drivers->count() }})</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Driver</th>
                        <th class="py-2 pr-3 hidden md:table-cell">Phone</th>
                        <th class="py-2 pr-3 hidden lg:table-cell">Licence</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Rating</th>
                        <th class="py-2 pr-3">Account</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        @php $rating = $driver->ratingSummary(); @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold shrink-0">
                                        {{ strtoupper(mb_substr($driver->user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900">{{ $driver->user->name }}</div>
                                        <div class="text-xs text-slate-500 truncate">{{ $driver->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 pr-3 whitespace-nowrap hidden md:table-cell">{{ $driver->user->phone }}</td>
                            <td class="py-3 pr-3 whitespace-nowrap hidden lg:table-cell">{{ $driver->license_no }}</td>
                            <td class="py-3 pr-3">
                                <span class="{{ $statusBadge($driver->status) }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $driver->status }}</span>
                            </td>
                            <td class="py-3 pr-3 whitespace-nowrap">
                                @if ($rating['count'] > 0)
                                    <span class="text-amber-500">★</span>
                                    <span class="font-semibold">{{ number_format($rating['average'], 1) }}</span>
                                    <span class="text-xs text-slate-500">({{ $rating['count'] }})</span>
                                @else
                                    <span class="text-slate-400 text-xs">No reviews yet</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3">
                                @if ($driver->user->is_active)
                                    <span class="bg-green-100 text-green-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Active</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Deactivated</span>
                                @endif
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.drivers.edit', $driver) }}"
                                   class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs inline-block">Edit</a>

                                <form method="POST" action="{{ route('admin.drivers.toggle', $driver) }}" class="inline"
                                      onsubmit="return confirm('Change this driver account status?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="border border-slate-300 hover:bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-xs">
                                        {{ $driver->user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No drivers yet. Add the first one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection