@extends('layouts.app')

@section('title', 'Tracking ' . $shipment->tracking_id . ' - FreightLink')

@section('content')
@php
    $failed   = in_array($shipment->status, ['Cancelled', 'Delivery Failed'], true);
    $finished = in_array($shipment->status, ['Delivered', 'Closed'], true);

    $banner = $failed
        ? 'from-red-700 to-red-600'
        : ($finished ? 'from-green-700 to-green-600' : 'from-slate-900 to-slate-700');
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    {{-- Status banner --}}
    <div class="bg-gradient-to-r {{ $banner }} text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider text-white/70">Tracking ID</div>
                <h1 class="text-3xl font-bold font-mono">{{ $shipment->tracking_id }}</h1>
            </div>
            <span class="bg-white/20 px-4 py-1.5 rounded-full text-sm font-semibold">{{ $shipment->status }}</span>
        </div>

        @if ($shipment->status === 'Cancelled')
            <p class="mt-4 text-sm text-white/90">This shipment was cancelled.</p>
        @elseif ($shipment->status === 'Delivery Failed')
            <p class="mt-4 text-sm text-white/90">The delivery attempt failed. A new delivery will be arranged.</p>
        @elseif ($finished)
            <p class="mt-4 text-sm text-white/90">This shipment has been delivered.</p>
        @endif
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Service</div>
                <div class="font-semibold text-slate-900">{{ $shipment->serviceType->name }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Distance</div>
                <div class="font-semibold text-slate-900">{{ number_format($shipment->distance_km, 2) }} km</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Booked on</div>
                <div class="font-semibold text-slate-900">{{ $shipment->created_at->format('d M Y, H:i') }}</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Last update</div>
                <div class="font-semibold text-slate-900">{{ $shipment->updated_at->format('d M Y, H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- Progress --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-6">Progress</h2>

        <ol class="flex items-start">
            @foreach ($steps as $step)
                @php
                    $done     = in_array($step, $reached);
                    $current  = $shipment->status === $step;
                    $nextStep = $steps[$loop->index + 1] ?? null;
                    $lineDone = $nextStep !== null && in_array($nextStep, $reached);
                @endphp

                <li class="flex-1 relative text-center">
                    @if (! $loop->last)
                        <div class="absolute top-4 left-1/2 w-full h-1 {{ $lineDone ? 'bg-green-500' : 'bg-slate-200' }}"></div>
                    @endif

                    <div class="relative mx-auto w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold
                                {{ $done ? 'bg-green-600 text-white' : 'bg-slate-200 text-slate-500' }}
                                {{ $current ? 'ring-4 ring-green-200' : '' }}">
                        {{ $done ? '✓' : $loop->iteration }}
                    </div>

                    <div class="text-xs mt-2 px-1 {{ $current ? 'font-bold text-slate-900' : 'text-slate-600' }}">{{ $step }}</div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Timeline (status and time only; the internal notes are not shown publicly) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">Status history</h2>

        <ol class="border-l-2 border-slate-200 ml-2 space-y-5">
            @foreach ($timeline as $log)
                <li class="pl-5 relative">
                    <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $loop->first ? 'bg-orange-500 ring-4 ring-orange-100' : 'bg-slate-400' }}"></span>
                    <div class="font-semibold text-sm text-slate-900">{{ $log->status }}</div>
                    <div class="text-xs text-slate-500">{{ $log->changed_at->format('d M Y, H:i') }}</div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Full details only for the owner and the admin --}}
    @auth
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('admin.shipments.show', $shipment) }}" class="inline-block text-orange-600 font-semibold hover:underline text-sm">Open the admin view of this shipment →</a>
        @elseif (auth()->id() === $shipment->user_id)
            <a href="{{ route('customer.shipments.show', $shipment) }}" class="inline-block text-orange-600 font-semibold hover:underline text-sm">View the full details and price →</a>
        @endif
    @endauth

    <div>
        <a href="{{ route('track.index') }}" class="inline-block text-slate-600 hover:text-slate-900 text-sm">← Track another shipment</a>
    </div>
</div>
@endsection