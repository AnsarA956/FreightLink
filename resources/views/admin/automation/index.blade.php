@extends('layouts.app')

@section('title', 'Automation - FreightLink')

@section('content')
@php
    $s = $settings;

    $info = [
        'auto-assign'     => ['🚚', 'Smart dispatch', 'Gives a Booked shipment a driver and a vehicle: the driver with the fewest active shipments, and the smallest vehicle that can carry the weight.'],
        'check-stuck'     => ['⚠️', 'Stuck-shipment alerts', 'Flags shipments with no driver, not picked up, or on the road for too long.'],
        'close-delivered' => ['✅', 'Auto-close', 'Moves Delivered shipments to Closed after a few days.'],
        'cleanup'         => ['🧹', 'Clean-up', 'Deletes old driver positions and old log lines.'],
        'daily-report'    => ['📧', 'Daily report', 'Sends the admin a summary of yesterday.'],
    ];

    $schedule = [
        'auto-assign'     => 'every 5 minutes',
        'check-stuck'     => 'every hour',
        'close-delivered' => 'daily at 02:00',
        'cleanup'         => 'daily at 03:00',
        'daily-report'    => 'daily at 07:00',
    ];

    $settingText = [
        'auto-assign'     => ($s['auto_assign']['enabled'] ? 'On' : 'Off') . ' · waits ' . $s['auto_assign']['wait_minutes'] . ' min · max ' . $s['auto_assign']['max_active_per_driver'] . ' active per driver',
        'check-stuck'     => ($s['stuck']['enabled'] ? 'On' : 'Off') . ' · ' . $s['stuck']['unassigned_hours'] . ' h no driver · ' . $s['stuck']['not_picked_up_hours'] . ' h not picked up · ' . $s['stuck']['in_transit_hours'] . ' h in transit',
        'close-delivered' => ($s['close_delivered']['enabled'] ? 'On' : 'Off') . ' · after ' . $s['close_delivered']['after_days'] . ' days',
        'cleanup'         => 'Always on',
        'daily-report'    => $s['daily_report']['enabled'] ? 'On' : 'Off',
    ];
@endphp

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Automation</h1>
        <p class="text-sm text-slate-500 mt-1">Tasks that FreightLink runs by itself, so nobody has to click.</p>
    </div>

    {{-- Is the scheduler running? --}}
    @if ($running)
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 text-green-900 text-sm">
            ✅ <strong>The scheduler is running.</strong> Last tick {{ $lastRun->diffForHumans() }}.
        </div>
    @else
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            ⏸️ <strong>The scheduler is not running.</strong>
            {{ $lastRun ? 'Last tick ' . $lastRun->diffForHumans() . '.' : 'It has not run yet.' }}
            The tasks only run by themselves while it is running. Open a second cmd window in the project folder and run
            <code class="bg-amber-100 px-1.5 py-0.5 rounded">php artisan schedule:work</code>.
            Until then, use the <strong>Run now</strong> buttons.
        </div>
    @endif

    {{-- Numbers --}}
    <div class="grid grid-cols-2 gap-4 max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs text-slate-500">Actions in the last 24 h</div>
            <div class="text-2xl font-bold text-slate-900">{{ number_format($actions) }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs text-slate-500">Warnings in the last 24 h</div>
            <div class="text-2xl font-bold {{ $warnings > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ number_format($warnings) }}</div>
        </div>
    </div>

    {{-- The tasks --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach ($tasks as $task)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col">
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-orange-100 w-10 h-10 rounded-xl flex items-center justify-center text-xl">{{ $info[$task][0] }}</div>
                    <div>
                        <div class="font-bold text-slate-900">{{ $info[$task][1] }}</div>
                        <div class="text-xs text-slate-500">{{ $schedule[$task] }}</div>
                    </div>
                </div>

                <p class="text-sm text-slate-600 mb-3">{{ $info[$task][2] }}</p>
                <p class="text-xs text-slate-500 mb-4">{{ $settingText[$task] }}</p>

                <form method="POST" action="{{ route('admin.automation.run') }}" class="mt-auto">
                    @csrf
                    <input type="hidden" name="task" value="{{ $task }}">
                    <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white px-4 py-2 rounded-lg text-sm transition">Run now</button>
                </form>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-slate-500">The settings are in the file <code>config/automation.php</code> and can be changed in the <code>.env</code> file.</p>

    {{-- The log --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-x-auto">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="font-bold text-slate-900">What the system did by itself</h2>

            <form method="GET" action="{{ route('admin.automation.index') }}" class="flex items-center gap-2">
                <select name="task" class="border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All tasks</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task }}" @selected($taskFilter === $task)>{{ $info[$task][1] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Filter</button>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Time</th>
                    <th class="py-2 pr-3">Task</th>
                    <th class="py-2 pr-3">Shipment</th>
                    <th class="py-2">What happened</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                        <td class="py-3 pr-3 whitespace-nowrap">{{ $log->created_at->format('d M, H:i') }}</td>
                        <td class="py-3 pr-3 whitespace-nowrap">
                            <span class="{{ $log->level === 'warning' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }} px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ $info[$log->task][1] ?? $log->task }}
                            </span>
                        </td>
                        <td class="py-3 pr-3 whitespace-nowrap">
                            @if ($log->shipment)
                                <a href="{{ route('admin.shipments.show', $log->shipment) }}" class="text-orange-600 font-semibold hover:underline">{{ $log->shipment->tracking_id }}</a>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="py-3">{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-slate-500">Nothing yet. Press Run now on one of the tasks above.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection