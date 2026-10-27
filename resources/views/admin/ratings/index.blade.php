@extends('layouts.app')

@section('title', 'Driver ratings - FreightLink')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Driver ratings</h1>
        <p class="text-sm text-slate-500 mt-1">Customers rate delivered shipments from 1 to 5 stars.</p>
    </div>

    {{-- Overall --}}
    <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow flex flex-wrap items-center gap-6">
        <div>
            <div class="text-sm text-slate-300">Overall average</div>
            <div class="text-4xl font-bold">{{ $total > 0 ? number_format($overall, 2) : '-' }}<span class="text-lg text-slate-400"> / 5</span></div>
        </div>
        @if ($total > 0)
            <div class="text-2xl text-amber-400">
                {{ str_repeat('★', (int) round($overall)) }}<span class="text-slate-500">{{ str_repeat('★', 5 - (int) round($overall)) }}</span>
            </div>
        @endif
        <div class="text-sm text-slate-300">from {{ number_format($total) }} review(s)</div>
    </div>

    {{-- Average per driver --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-x-auto">
        <h2 class="font-bold text-slate-900 mb-4">Average rating per driver</h2>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Driver</th>
                    <th class="py-2 pr-3 text-right">Reviews</th>
                    <th class="py-2 pr-3 text-right">Average</th>
                    <th class="py-2">Stars</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($drivers as $d)
                    @php $avg = $d->reviews_avg_rating !== null ? (float) $d->reviews_avg_rating : null; @endphp
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="py-3 pr-3 font-medium text-slate-900">{{ $d->user->name ?? 'Driver #' . $d->id }}</td>
                        <td class="py-3 pr-3 text-right">{{ number_format($d->reviews_count) }}</td>
                        <td class="py-3 pr-3 text-right font-semibold">{{ $avg !== null ? number_format($avg, 2) : '-' }}</td>
                        <td class="py-3 min-w-[10rem]">
                            @if ($avg !== null)
                                <div class="flex items-center gap-3">
                                    <span class="text-amber-500 whitespace-nowrap">
                                        {{ str_repeat('★', (int) round($avg)) }}<span class="text-slate-300">{{ str_repeat('★', 5 - (int) round($avg)) }}</span>
                                    </span>
                                    <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden hidden sm:block">
                                        <div class="h-2 bg-amber-400 rounded-full" style="width: {{ round($avg / 5 * 100) }}%"></div>
                                    </div>
                                </div>
                            @else
                                <span class="text-slate-400 text-xs">No reviews yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">No drivers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Latest reviews --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-x-auto">
        <h2 class="font-bold text-slate-900 mb-4">Latest reviews</h2>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Date</th>
                    <th class="py-2 pr-3">Shipment</th>
                    <th class="py-2 pr-3 hidden md:table-cell">Customer</th>
                    <th class="py-2 pr-3">Driver</th>
                    <th class="py-2 pr-3">Rating</th>
                    <th class="py-2 hidden lg:table-cell">Comment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($latest as $r)
                    <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                        <td class="py-3 pr-3 whitespace-nowrap">{{ $r->created_at->format('d M Y') }}</td>
                        <td class="py-3 pr-3 whitespace-nowrap font-medium">{{ $r->shipment->tracking_id ?? '-' }}</td>
                        <td class="py-3 pr-3 hidden md:table-cell">{{ $r->user->name ?? '-' }}</td>
                        <td class="py-3 pr-3">{{ $r->driver?->user?->name ?? '-' }}</td>
                        <td class="py-3 pr-3 text-amber-500 whitespace-nowrap">{{ str_repeat('★', $r->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $r->rating) }}</span></td>
                        <td class="py-3 text-slate-600 hidden lg:table-cell">{{ $r->comment ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection