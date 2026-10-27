@extends('layouts.app')

@section('title', 'Shipments - FreightLink')

@section('content')
<div class="max-w-6xl bg-white rounded-xl shadow p-6">
    <h1 class="text-2xl font-bold mb-4">All shipments</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Search and filters --}}
    <form method="GET" action="{{ route('admin.shipments.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Tracking ID, customer name or email"
                   class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">From date</label>
            <input type="date" name="from" value="{{ request('from') }}" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">To date</label>
            <input type="date" name="to" value="{{ request('to') }}" class="w-full border rounded px-3 py-2">
        </div>
        <div class="md:col-span-5 flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Filter</button>
            <a href="{{ route('admin.shipments.index') }}" class="px-4 py-2 rounded border">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500">
                    <th class="py-2">Tracking ID</th>
                    <th class="py-2">Customer</th>
                    <th class="py-2">From → To</th>
                    <th class="py-2">Weight</th>
                    <th class="py-2">Status</th>
                    <th class="py-2">Driver / Vehicle</th>
                    <th class="py-2 text-right">Total</th>
                    <th class="py-2 pl-4">Booked on</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $shipment)
                    <tr class="border-t">
                        <td class="py-2 pr-2">
                            <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-blue-600 underline">{{ $shipment->tracking_id }}</a>
                        </td>
                        <td class="py-2 pr-2">{{ $shipment->user->name }}</td>
                        <td class="py-2 pr-2">{{ \Illuminate\Support\Str::limit($shipment->pickup_address, 22) }} → {{ \Illuminate\Support\Str::limit($shipment->delivery_address, 22) }}</td>
                        <td class="py-2 pr-2">{{ number_format($shipment->weight_kg, 2) }} kg</td>
                        <td class="py-2 pr-2">{{ $shipment->status }}</td>
                        <td class="py-2 pr-2">
                            @if ($shipment->driver)
                                {{ $shipment->driver->user->name }}<br>
                                <span class="text-xs text-slate-500">{{ $shipment->vehicle?->plate_no }}</span>
                            @else
                                <span class="text-slate-400">Not assigned</span>
                            @endif
                        </td>
                        <td class="py-2 text-right whitespace-nowrap">Rs {{ number_format($shipment->total_price, 2) }}</td>
                        <td class="py-2 pl-4 whitespace-nowrap">{{ $shipment->created_at->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 text-slate-500">No shipments match your search.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $shipments->links() }}
    </div>
</div>
@endsection