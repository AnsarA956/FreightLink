<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track shipment - FreightLink</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">

<header class="bg-slate-900 text-white">
    <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ url('/') }}" class="font-bold">FreightLink</a>
        <nav class="text-sm space-x-4">
            @auth
                <a href="{{ route('dashboard') }}" class="hover:underline">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="hover:underline">Login</a>
                <a href="{{ route('register') }}" class="hover:underline">Register</a>
            @endauth
        </nav>
    </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-3">Track your shipment</h1>
        <form method="GET" action="{{ route('track') }}" class="flex flex-col sm:flex-row gap-2">
            <input type="text" name="tracking_id" value="{{ $id }}" placeholder="Enter your tracking ID"
                   class="flex-1 border rounded px-3 py-2" required maxlength="50">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded">Track</button>
        </form>
    </div>

    @if ($searched && ! $shipment)
        <div class="p-4 rounded bg-red-100 text-red-800 text-sm">
            No shipment was found with the tracking ID "{{ $id }}". Please check it and try again.
        </div>
    @endif

    @if ($shipment)
        @php
            $steps   = ['Booked', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'];
            $logs    = $shipment->statusLogs->sortBy('changed_at');
            $reached = $logs->pluck('status')->all();
            $failed  = $shipment->status === 'Delivery Failed';
        @endphp

        {{-- Summary --}}
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">{{ $shipment->tracking_id }}</h2>
                <span class="px-3 py-1 rounded-full text-sm {{ $failed ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ $shipment->status }}
                </span>
            </div>

            {{-- Progress bar --}}
            <div class="flex items-center mb-2">
                @foreach ($steps as $i => $step)
                    @php $done = in_array($step, $reached); @endphp
                    <div class="flex flex-col items-center text-center" style="width: {{ 100 / count($steps) }}%">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs text-white {{ $done ? 'bg-green-600' : 'bg-slate-300' }}">
                            {{ $done ? '✓' : $i + 1 }}
                        </div>
                        <div class="text-xs mt-1 {{ $done ? 'font-medium' : 'text-slate-400' }}">{{ $step }}</div>
                    </div>
                @endforeach
            </div>

            @if ($failed)
                <div class="mt-4 p-3 rounded bg-red-50 text-red-800 text-sm">
                    The last delivery attempt failed. A new delivery will be arranged.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm mt-5">
                <div class="md:col-span-2"><span class="text-slate-500">From:</span> {{ $shipment->pickup_address }}</div>
                <div class="md:col-span-2"><span class="text-slate-500">To:</span> {{ $shipment->delivery_address }}</div>
                <div><span class="text-slate-500">Service:</span> {{ $shipment->serviceType->name }}</div>
                <div><span class="text-slate-500">Cargo:</span> {{ $shipment->cargoType->name }}</div>
                <div><span class="text-slate-500">Weight:</span> {{ number_format($shipment->weight_kg, 2) }} kg</div>
                <div><span class="text-slate-500">Booked on:</span> {{ $shipment->created_at->format('d M Y, H:i') }}</div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-bold mb-3">Status timeline</h2>
            <ol class="border-l-2 border-slate-200 ml-2 space-y-4">
                @foreach ($logs->reverse() as $log)
                    <li class="pl-4 relative">
                        <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $log->status === 'Delivery Failed' ? 'bg-red-600' : 'bg-blue-600' }}"></span>
                        <div class="font-medium text-sm">{{ $log->status }}</div>
                        <div class="text-xs text-slate-500">{{ $log->changed_at->format('d M Y, H:i') }}</div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</main>

</body>
</html>