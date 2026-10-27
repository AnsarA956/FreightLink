@extends('layouts.app')

@section('title', $shipment->tracking_id . ' - FreightLink')

@section('content')
@php
    $failed   = in_array($shipment->status, ['Cancelled', 'Delivery Failed'], true);
    $finished = in_array($shipment->status, ['Delivered', 'Closed'], true);
    $banner   = $failed ? 'from-red-700 to-red-600' : ($finished ? 'from-green-700 to-green-600' : 'from-slate-900 to-slate-700');
    $field    = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Banner --}}
    <div class="bg-gradient-to-r {{ $banner }} text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-wider text-white/70">Tracking ID</div>
                <h1 class="text-3xl font-bold font-mono">{{ $shipment->tracking_id }}</h1>
                <div class="text-sm text-white/80 mt-1">
                    Booked on {{ $shipment->created_at->format('d M Y, H:i') }}
                    · {{ $shipment->paidPayment ? 'Paid online' : 'Not paid online' }}
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="bg-white/20 px-4 py-1.5 rounded-full text-sm font-semibold">{{ $shipment->status }}</span>

                @if ($shipment->status !== 'Cancelled')
                    <a href="{{ route('invoice.download', $shipment) }}"
                       class="bg-white/10 hover:bg-white/20 px-4 py-1.5 rounded-full text-sm transition">⬇ Invoice PDF</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">📦 Shipment details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Customer</div>
                <div class="font-medium">{{ $shipment->user->name }}</div>
                <div class="text-slate-600">{{ $shipment->user->phone }} · {{ $shipment->user->email }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Driver and vehicle</div>
                @if ($shipment->driver)
                    <div class="font-medium">{{ $shipment->driver->user->name }}</div>
                    <div class="text-slate-600">{{ $shipment->vehicle?->plate_no ?? '-' }}</div>
                @else
                    <div><span class="bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Not assigned</span></div>
                @endif
            </div>

            <div class="md:col-span-2">
                <div class="text-xs uppercase tracking-wide text-slate-500">From</div>
                <div class="font-medium">{{ $shipment->pickup_address }}</div>
            </div>
            <div class="md:col-span-2">
                <div class="text-xs uppercase tracking-wide text-slate-500">To</div>
                <div class="font-medium">{{ $shipment->delivery_address }}</div>
            </div>

            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Distance</div>
                <div class="font-medium">{{ number_format($shipment->distance_km, 2) }} km</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Weight</div>
                <div class="font-medium">{{ number_format($shipment->weight_kg, 2) }} kg <span class="text-slate-500 font-normal">(chargeable {{ number_format($shipment->chargeable_kg, 2) }} kg)</span></div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Size</div>
                <div class="font-medium">{{ $shipment->length_cm + 0 }} × {{ $shipment->width_cm + 0 }} × {{ $shipment->height_cm + 0 }} cm</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Service / Cargo</div>
                <div class="font-medium">{{ $shipment->serviceType->name }} / {{ $shipment->cargoType->name }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Total price</div>
                <div class="text-xl font-bold text-orange-600">Rs {{ number_format($shipment->total_price, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Cancel booking (only before a driver is assigned) --}}
    @if ($shipment->status === 'Booked' && ! $shipment->driver_id)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-slate-900">Cancel booking</h2>
                <p class="text-sm text-slate-500">A booking can be cancelled until a driver is assigned.</p>
            </div>
            <form method="POST" action="{{ route('shipments.cancel', $shipment) }}"
                  onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                @csrf
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Cancel booking</button>
            </form>
        </div>
    @endif

    {{-- Assignment (admin only) --}}
    @if (auth()->user()->role === 'admin')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-1">🧑 Driver and vehicle</h2>

            @if ($shipment->driver)
                <p class="text-sm text-slate-700 mb-4">
                    Currently assigned to <strong>{{ $shipment->driver->user->name }}</strong>
                    with vehicle <strong>{{ $shipment->vehicle?->plate_no }}</strong>.
                </p>
            @else
                <p class="text-sm text-slate-500 mb-4">No driver or vehicle has been assigned yet.</p>
            @endif

            @if ($assignable)
                <form method="POST" action="{{ route('admin.shipments.assign', $shipment) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Driver</label>
                        <select name="driver_id" class="{{ $field }}" required>
                            <option value="">Choose a driver</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    @selected((string) old('driver_id', $shipment->driver_id) === (string) $driver->id)>
                                    {{ $driver->user->name }} ({{ $driver->status }})
                                </option>
                            @endforeach
                        </select>
                        @if ($drivers->isEmpty())
                            <p class="text-xs text-red-600 mt-1">No active drivers are available. Add one in the Drivers page.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Vehicle (this shipment: {{ number_format($shipment->weight_kg, 2) }} kg)</label>
                        <select name="vehicle_id" class="{{ $field }}" required>
                            <option value="">Choose a vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                @php $tooSmall = $vehicle->capacity_kg < $shipment->weight_kg; @endphp
                                <option value="{{ $vehicle->id }}"
                                    @disabled($tooSmall)
                                    @selected((string) old('vehicle_id', $shipment->vehicle_id) === (string) $vehicle->id)>
                                    {{ $vehicle->plate_no }} ({{ $vehicle->type }}, {{ number_format($vehicle->capacity_kg) }} kg){{ $tooSmall ? ' - too small' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Vehicles that cannot carry this weight are greyed out.</p>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                            {{ $shipment->driver ? 'Reassign' : 'Assign' }}
                        </button>
                    </div>
                </form>
            @else
                <p class="text-sm text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                    This shipment is "{{ $shipment->status }}", so the driver and vehicle can no longer be changed.
                </p>
            @endif
        </div>
    @endif

    {{-- Route map with the live driver position --}}
    @include('partials.route-map', [
        'mapId'    => 'admin-shipment-map',
        'pickup'   => [$shipment->pickup_lat, $shipment->pickup_lng],
        'delivery' => [$shipment->delivery_lat, $shipment->delivery_lng],
        'liveUrl'  => route('shipments.location', $shipment),
    ])

    {{-- Status history --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">🕒 Status history</h2>

        <ol class="border-l-2 border-slate-200 ml-2 space-y-5">
            @foreach ($shipment->statusLogs as $log)
                <li class="pl-5 relative">
                    <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $loop->last ? 'bg-orange-500 ring-4 ring-orange-100' : 'bg-slate-400' }}"></span>
                    <div class="font-semibold text-sm text-slate-900">{{ $log->status }}</div>
                    <div class="text-xs text-slate-500">{{ $log->changed_at->format('d M Y, H:i') }}@if ($log->note) · {{ $log->note }}@endif</div>
                </li>
            @endforeach
        </ol>
    </div>

    <a href="{{ route('admin.shipments.index') }}" class="inline-block text-slate-600 hover:text-slate-900 text-sm">← Back to all shipments</a>
</div>
@endsection