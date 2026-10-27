@extends('layouts.app')

@section('title', 'Track a Shipment - FreightLink')

@section('content')
<div class="max-w-xl mx-auto mt-6">

    <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white rounded-3xl p-8 shadow-lg text-center">
        <div class="mx-auto bg-orange-500 rounded-2xl w-14 h-14 flex items-center justify-center text-2xl mb-4">📍</div>
        <h1 class="text-2xl sm:text-3xl font-bold">Track your shipment</h1>
        <p class="text-slate-300 text-sm mt-2 mb-6">Enter the tracking ID you received when you booked, for example FL-123456.</p>

        @if (isset($notFound))
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 text-sm text-left">
                No shipment was found with the tracking ID "{{ $notFound }}". Please check the ID and try again.
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 text-sm text-left">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('track.index') }}" class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="tracking_id" value="{{ $notFound ?? request('tracking_id') }}"
                   placeholder="FL-123456" autofocus required
                   class="flex-1 rounded-lg px-4 py-3 text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-orange-400">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 font-semibold px-8 py-3 rounded-lg transition">Track</button>
        </form>
    </div>

    <p class="text-center text-xs text-slate-500 mt-4">
        No login is needed. The tracking page shows only the status and the dates, not your address or price.
    </p>
</div>
@endsection