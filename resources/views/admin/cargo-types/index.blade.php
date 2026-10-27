@extends('layouts.app')

@section('title', 'Cargo types - FreightLink')

@section('content')
@php
    $field = 'border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Cargo types</h1>
        <p class="text-sm text-slate-500 mt-1">A fixed extra amount (in Rs) added for special cargo such as fragile goods. General cargo is usually 0.</p>
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
        <h2 class="font-bold text-slate-900 mb-4">➕ Add a cargo type</h2>

        <form method="POST" action="{{ route('admin.cargo-types.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="{{ $field }} w-56" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Surcharge (Rs)</label>
                <input type="number" step="0.01" min="0" name="{{ $surcharge }}" value="{{ old($surcharge, '0') }}" class="{{ $field }} w-36" required>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2 rounded-lg transition text-sm">Add</button>
        </form>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-1">All cargo types ({{ $cargoTypes->count() }})</h2>
        <p class="text-xs text-slate-500 mb-4">To change one, edit the row and press Save. A type already used by shipments may refuse to be deleted.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Name</th>
                        <th class="py-2 pr-3">Surcharge (Rs)</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cargoTypes as $type)
                        <tr class="border-t border-slate-100">
                            <td class="py-3 pr-3">
                                <input form="update-{{ $type->id }}" type="text" name="name" value="{{ $type->name }}" class="{{ $field }} w-56" required>
                            </td>
                            <td class="py-3 pr-3">
                                <input form="update-{{ $type->id }}" type="number" step="0.01" min="0" name="{{ $surcharge }}" value="{{ $type->{$surcharge} }}" class="{{ $field }} w-36" required>
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <form id="update-{{ $type->id }}" method="POST" action="{{ route('admin.cargo-types.update', $type) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs">Save</button>
                                </form>

                                <form method="POST" action="{{ route('admin.cargo-types.destroy', $type) }}" class="inline"
                                      onsubmit="return confirm('Delete this cargo type?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="border border-red-300 text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-500">No cargo types yet. Add the first one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection