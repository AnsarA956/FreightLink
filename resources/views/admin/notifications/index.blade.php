@extends('layouts.app')

@section('title', 'Notifications - FreightLink')

@section('content')
@php
    $field = 'border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';

    $eventBadge = [
        'booked'     => 'bg-blue-100 text-blue-800',
        'dispatched' => 'bg-amber-100 text-amber-800',
        'delivered'  => 'bg-green-100 text-green-800',
    ];
@endphp

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Notifications</h1>
        <p class="text-sm text-slate-500 mt-1">
            Every email and SMS the system tried to send to a customer.
            "Skipped" means the customer has no real email address or phone number (for example a walk-in customer).
        </p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Event</label>
                <select name="event" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach (['booked' => 'Booked', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach (['sent' => 'Sent', 'failed' => 'Failed', 'skipped' => 'Skipped'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Filter</button>
            <a href="{{ route('admin.notifications.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-sm transition">Clear</a>
        </form>
    </div>

    {{-- The list --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Time</th>
                        <th class="py-2 pr-3">Shipment</th>
                        <th class="py-2 pr-3">Event</th>
                        <th class="py-2 pr-3">Sent to</th>
                        <th class="py-2 pr-3 hidden lg:table-cell">Subject</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 hidden md:table-cell">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $hasChannel = array_key_exists('channel', $log->getAttributes());
                            $channel    = $hasChannel ? $log->getAttribute('channel') : null;
                        @endphp
                        <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3 whitespace-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</td>
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <a href="{{ route('admin.shipments.show', $log->shipment_id) }}" class="text-orange-600 font-semibold hover:underline">{{ $log->shipment->tracking_id }}</a>
                            </td>
                            <td class="py-3 pr-3">
                                <span class="{{ $eventBadge[$log->event] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium">{{ ucfirst($log->event) }}</span>
                            </td>
                            <td class="py-3 pr-3">
                                @if ($channel)
                                    <span class="text-xs font-semibold {{ $channel === 'sms' ? 'text-violet-700' : 'text-sky-700' }}">
                                        {{ $channel === 'sms' ? '💬 SMS' : '✉️ ' . ucfirst($channel) }}
                                    </span><br>
                                @endif
                                {{ $log->recipient }}
                            </td>
                            <td class="py-3 pr-3 hidden lg:table-cell">{{ $log->subject }}</td>
                            <td class="py-3 pr-3">
                                @if ($log->status === 'sent')
                                    <span class="bg-green-100 text-green-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Sent</span>
                                @elseif ($log->status === 'failed')
                                    <span class="bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Failed</span>
                                @else
                                    <span class="bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Skipped</span>
                                @endif
                            </td>
                            <td class="py-3 text-xs text-slate-600 hidden md:table-cell">{{ $log->error }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No notifications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection