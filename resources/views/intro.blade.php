@extends('layouts.app')

@section('title', 'FreightLink - Freight and cargo management')

@section('content')
<div class="space-y-12">

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 text-white rounded-3xl px-6 sm:px-12 py-14 shadow-lg">
        <div class="max-w-2xl">
            <span class="inline-block bg-orange-500/20 text-orange-300 text-xs font-semibold uppercase tracking-wider px-3 py-1 rounded-full mb-4">
                Freight and cargo management
            </span>
            <h1 class="text-3xl sm:text-5xl font-bold leading-tight">
                Book, price and track your cargo <span class="text-orange-400">in minutes</span>
            </h1>
            <p class="mt-4 text-slate-300 text-lg">
                See the full price before you book. The distance is calculated automatically,
                and you can follow your shipment until it is delivered.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <a href="{{ route('register') }}" class="bg-orange-500 hover:bg-orange-600 font-semibold px-6 py-3 rounded-lg transition">Get started</a>
                    <a href="{{ route('login') }}" class="border border-slate-500 hover:bg-slate-700 px-6 py-3 rounded-lg transition">Login</a>
                @else
                    <a href="{{ route('dashboard') }}" class="bg-orange-500 hover:bg-orange-600 font-semibold px-6 py-3 rounded-lg transition">Go to my dashboard</a>
                @endguest
            </div>

            {{-- Quick tracking --}}
            <form id="quick-track" class="mt-8 bg-white/10 rounded-xl p-4 flex flex-col sm:flex-row gap-3">
                <input type="text" id="quick-track-id" placeholder="Enter your tracking ID, for example FL-123456"
                       class="flex-1 rounded-lg px-4 py-2.5 text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-400" required>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 font-semibold px-6 py-2.5 rounded-lg transition">Track</button>
            </form>
        </div>
    </section>

    {{-- Features --}}
    <section>
        <h2 class="text-2xl font-bold text-slate-900 text-center">Everything you need to ship</h2>
        <p class="text-center text-slate-500 mt-1 mb-8">One system for customers, staff, drivers and administrators</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ([
                ['💲', 'Automatic pricing', 'The price is calculated from weight, size, distance and service type, and shown before you confirm.'],
                ['📍', 'Live tracking', 'Follow every status change on a timeline, with the route and the driver position on a map.'],
                ['🧾', 'PDF invoices', 'Download an invoice with a QR code that opens the tracking page.'],
                ['🔔', 'Email and SMS alerts', 'Get a message when your booking is confirmed, dispatched and delivered.'],
                ['💳', 'Online payment', 'Pay online (test mode) or pay on delivery.'],
                ['⭐', 'Ratings and reviews', 'Rate every delivery, so the service keeps getting better.'],
            ] as [$icon, $title, $text])
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition">
                    <div class="bg-orange-100 text-2xl w-12 h-12 rounded-xl flex items-center justify-center mb-4">{{ $icon }}</div>
                    <h3 class="font-bold text-slate-900 mb-1">{{ $title }}</h3>
                    <p class="text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-white rounded-3xl border border-slate-200 shadow-sm px-6 sm:px-12 py-10">
        <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">How it works</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ([
                ['1', 'Enter the details', 'Pickup and delivery address, weight, size and service type.'],
                ['2', 'See the price', 'The distance and the full price breakdown appear in seconds.'],
                ['3', 'Confirm and pay', 'Book the shipment and receive your tracking ID.'],
                ['4', 'Track to delivery', 'Follow the shipment and download your invoice.'],
            ] as [$number, $title, $text])
                <div class="text-center">
                    <div class="mx-auto bg-slate-900 text-white font-bold w-11 h-11 rounded-full flex items-center justify-center mb-3">{{ $number }}</div>
                    <h3 class="font-semibold text-slate-900 mb-1">{{ $title }}</h3>
                    <p class="text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <p class="text-center text-xs text-slate-400">
        FreightLink is a final year project of the Department of Information Technology, GCUF.
    </p>
</div>

<script>
    // Sends the visitor to the tracking page of the typed ID
    document.getElementById('quick-track').addEventListener('submit', function (event) {
        event.preventDefault();

        var id = document.getElementById('quick-track-id').value.trim();

        if (id !== '') {
            window.location.href = @json(url('/track')) + '/' + encodeURIComponent(id);
        }
    });
</script>
@endsection