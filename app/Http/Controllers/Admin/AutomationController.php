<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationLog;
use App\Services\AutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AutomationController extends Controller
{
    public function index(Request $request)
    {
        $task = $request->query('task');

        $logs = AutomationLog::with('shipment')
            ->when(in_array($task, AutomationService::TASKS, true), fn ($q) => $q->where('task', $task))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $lastRun = Cache::get('automation.last_run');
        $running = $lastRun && Carbon::parse($lastRun)->gt(now()->subMinutes(5));

        return view('admin.automation.index', [
            'logs'      => $logs,
            'tasks'     => AutomationService::TASKS,
            'settings'  => config('automation'),
            'lastRun'   => $lastRun ? Carbon::parse($lastRun) : null,
            'running'   => $running,
            'warnings'  => AutomationLog::where('level', 'warning')->where('created_at', '>=', now()->subDay())->count(),
            'actions'   => AutomationLog::where('level', 'info')->where('created_at', '>=', now()->subDay())->count(),
            'taskFilter' => $task,
        ]);
    }

    // "Run now" button
    public function run(Request $request, AutomationService $service)
    {
        $data = $request->validate([
            'task' => ['required', Rule::in(AutomationService::TASKS)],
        ]);

        $result = $service->run($data['task']);

        $text = collect($result)->map(fn ($value, $key) => $key . ': ' . $value)->implode(', ');

        return back()->with('success', 'Task "' . $data['task'] . '" finished. ' . $text);
    }
}