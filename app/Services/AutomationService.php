<?php

namespace App\Services;

use App\Models\AutomationLog;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\StatusLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

class AutomationService
{
    public const TASKS = ['auto-assign', 'check-stuck', 'close-delivered', 'cleanup', 'daily-report'];

    private const ACTIVE = ['Assigned', 'Picked Up', 'In Transit'];

    // Runs one task and returns a short result, for example ['assigned' => 2]
    public function run(string $task): array
    {
        if ($task !== 'heartbeat' && ! in_array($task, self::TASKS, true)) {
            throw new InvalidArgumentException('Unknown task "' . $task . '". Use: ' . implode(', ', self::TASKS));
        }

        try {
            return match ($task) {
                'auto-assign'     => $this->autoAssign(),
                'check-stuck'     => $this->checkStuck(),
                'close-delivered' => $this->closeDelivered(),
                'cleanup'         => $this->cleanup(),
                'daily-report'    => $this->dailyReport(),
                'heartbeat'       => $this->heartbeat(),
            };
        } catch (Throwable $e) {
            Log::error('Automation task ' . $task . ' failed: ' . $e->getMessage());
            $this->log($task, 'warning', null, 'The task failed: ' . mb_substr($e->getMessage(), 0, 300));

            return ['error' => mb_substr($e->getMessage(), 0, 120)];
        }
    }

    // ------------------------------------------------------------------
    // Proof that the scheduler is running (shown on the Automation page)
    // ------------------------------------------------------------------

    private function heartbeat(): array
    {
        Cache::put('automation.last_run', now()->toDateTimeString(), now()->addDay());

        return ['ok' => 1];
    }

    // ------------------------------------------------------------------
    // 1. Smart dispatch
    // ------------------------------------------------------------------

    private function autoAssign(): array
    {
        $cfg = config('automation.auto_assign');

        if (! $cfg['enabled']) {
            return ['skipped' => 'switched off'];
        }

        $shipments = Shipment::where('status', 'Booked')
            ->whereNull('driver_id')
            ->where('created_at', '<=', now()->subMinutes((int) $cfg['wait_minutes']))
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        if ($shipments->isEmpty()) {
            return ['assigned' => 0, 'waiting' => 0];
        }

        // Active drivers who are not off duty
        $drivers = Driver::with('user')->get()
            ->filter(fn ($d) => $d->user && $d->user->is_active && ! $this->isOffDuty((string) $d->status))
            ->values();

        // How many active shipments each driver has now
        $load = Shipment::whereIn('status', self::ACTIVE)
            ->whereNotNull('driver_id')
            ->selectRaw('driver_id, COUNT(*) as total')
            ->groupBy('driver_id')
            ->pluck('total', 'driver_id')
            ->all();

        $assigned = 0;
        $waiting  = 0;

        foreach ($shipments as $shipment) {
            $driver  = $this->pickDriver($drivers, $load, (int) $cfg['max_active_per_driver']);
            $vehicle = $driver ? $this->pickVehicle($driver, (float) $shipment->weight_kg) : null;

            if (! $driver || ! $vehicle) {
                $waiting++;
                $this->logOnce('auto-assign', 'warning', $shipment,
                    'Could not be assigned automatically: ' . (! $driver ? 'no free driver.' : 'no vehicle can carry ' . number_format($shipment->weight_kg, 2) . ' kg.'));

                continue;
            }

            $done = DB::transaction(function () use ($shipment, $driver, $vehicle) {
                $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->first();

                // An admin may have assigned it a moment ago
                if (! $locked || $locked->status !== 'Booked' || $locked->driver_id !== null) {
                    return false;
                }

                $locked->update([
                    'driver_id'  => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'status'     => 'Assigned',
                ]);

                StatusLog::create([
                    'shipment_id' => $locked->id,
                    'status'      => 'Assigned',
                    'note'        => 'Assigned automatically to ' . $driver->user->name . ' (' . $vehicle->plate_no . ')',
                    'changed_at'  => now(),
                ]);

                return true;
            });

            if ($done) {
                $load[$driver->id] = ($load[$driver->id] ?? 0) + 1;
                $assigned++;

                $this->log('auto-assign', 'info', $shipment,
                    'Assigned to ' . $driver->user->name . ' with vehicle ' . $vehicle->plate_no . '.');
            }
        }

        return ['assigned' => $assigned, 'waiting' => $waiting];
    }

    // The driver with the fewest active shipments (below the limit)
    private function pickDriver(Collection $drivers, array $load, int $max): ?Driver
    {
        return $drivers
            ->filter(fn ($d) => ($load[$d->id] ?? 0) < $max)
            ->sortBy(fn ($d) => [$load[$d->id] ?? 0, $d->id])
            ->first();
    }

    // A vehicle the driver already uses, otherwise the smallest free vehicle that can carry the weight
    private function pickVehicle(Driver $driver, float $weightKg): ?Vehicle
    {
        $fitting = Vehicle::where('capacity_kg', '>=', $weightKg)->orderBy('capacity_kg')->get();

        if ($fitting->isEmpty()) {
            return null;
        }

        $ownIds = Shipment::where('driver_id', $driver->id)
            ->whereIn('status', self::ACTIVE)
            ->whereNotNull('vehicle_id')
            ->pluck('vehicle_id');

        $own = $fitting->first(fn ($v) => $ownIds->contains($v->id));

        if ($own) {
            return $own;
        }

        $busyIds = Shipment::whereIn('status', self::ACTIVE)->whereNotNull('vehicle_id')->pluck('vehicle_id');

        return $fitting->first(fn ($v) => ! $busyIds->contains($v->id)) ?? $fitting->first();
    }

    private function isOffDuty(string $status): bool
    {
        $status = strtolower($status);

        foreach (['off', 'leave', 'inactive', 'unavailable', 'suspend', 'sick'] as $word) {
            if (str_contains($status, $word)) {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------
    // 2. Stuck-shipment alerts
    // ------------------------------------------------------------------

    private function checkStuck(): array
    {
        $cfg = config('automation.stuck');

        if (! $cfg['enabled']) {
            return ['skipped' => 'switched off'];
        }

        $new = 0;

        // No driver for too long
        $hours = (int) $cfg['unassigned_hours'];
        Shipment::where('status', 'Booked')->whereNull('driver_id')
            ->where('created_at', '<=', now()->subHours($hours))
            ->limit(100)->get()
            ->each(function ($s) use (&$new, $hours) {
                $new += $this->logOnce('check-stuck', 'warning', $s, "Still has no driver after {$hours} hours.") ? 1 : 0;
            });

        // Assigned, but the driver has not picked it up
        $hours = (int) $cfg['not_picked_up_hours'];
        Shipment::where('status', 'Assigned')
            ->where('updated_at', '<=', now()->subHours($hours))
            ->limit(100)->get()
            ->each(function ($s) use (&$new, $hours) {
                $new += $this->logOnce('check-stuck', 'warning', $s, "Assigned, but not picked up after {$hours} hours.") ? 1 : 0;
            });

        // On the road for too long
        $hours = (int) $cfg['in_transit_hours'];
        Shipment::whereIn('status', ['Picked Up', 'In Transit'])
            ->where('updated_at', '<=', now()->subHours($hours))
            ->limit(100)->get()
            ->each(function ($s) use (&$new, $hours) {
                $new += $this->logOnce('check-stuck', 'warning', $s, "On the road for more than {$hours} hours.") ? 1 : 0;
            });

        return ['new_alerts' => $new];
    }

    // ------------------------------------------------------------------
    // 3. Close delivered shipments
    // ------------------------------------------------------------------

    private function closeDelivered(): array
    {
        $cfg = config('automation.close_delivered');

        if (! $cfg['enabled']) {
            return ['skipped' => 'switched off'];
        }

        $days   = max(1, (int) $cfg['after_days']);
        $cutoff = now()->subDays($days);

        $ids = Shipment::where('status', 'Delivered')
            ->whereHas('statusLogs', fn ($q) => $q->where('status', 'Delivered')->where('changed_at', '<=', $cutoff))
            ->limit(200)
            ->pluck('id');

        $closed = 0;

        foreach ($ids as $id) {
            $done = DB::transaction(function () use ($id, $days) {
                $locked = Shipment::whereKey($id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== 'Delivered') {
                    return false;
                }

                $locked->update(['status' => 'Closed']);

                StatusLog::create([
                    'shipment_id' => $locked->id,
                    'status'      => 'Closed',
                    'note'        => "Closed automatically {$days} days after delivery",
                    'changed_at'  => now(),
                ]);

                return true;
            });

            if ($done) {
                $closed++;
            }
        }

        if ($closed > 0) {
            $this->log('close-delivered', 'info', null, "{$closed} delivered shipment(s) closed ({$days} days after delivery).");
        }

        return ['closed' => $closed];
    }

    // ------------------------------------------------------------------
    // 4. Clean-up
    // ------------------------------------------------------------------

    private function cleanup(): array
    {
        $positions = ShipmentLocation::where('updated_at', '<', now()->subDay())
            ->whereHas('shipment', fn ($q) => $q->whereNotIn('status', ['Picked Up', 'In Transit']))
            ->delete();

        $logs = AutomationLog::where('created_at', '<', now()->subDays(90))->delete();

        if ($positions > 0 || $logs > 0) {
            $this->log('cleanup', 'info', null, "Deleted {$positions} old driver position(s) and {$logs} old log line(s).");
        }

        return ['positions_deleted' => $positions, 'log_lines_deleted' => $logs];
    }

    // ------------------------------------------------------------------
    // 5. Daily report for the admin
    // ------------------------------------------------------------------

    private function dailyReport(): array
    {
        if (! config('automation.daily_report.enabled')) {
            return ['skipped' => 'switched off'];
        }

        $day     = now()->subDay()->startOfDay();
        $summary = app(ReportService::class)->build('daily', $day, $day->copy())['summary'];

        $text = "FreightLink daily summary for " . $day->format('d M Y') . "\n\n"
            . 'Shipments booked (cancelled not counted): ' . number_format($summary['shipments']) . "\n"
            . 'Delivered: ' . number_format($summary['delivered']) . "\n"
            . 'Pending deliveries: ' . number_format($summary['pending']) . "\n"
            . 'Cancelled: ' . number_format($summary['cancelled']) . "\n"
            . 'Revenue: Rs ' . number_format($summary['revenue'], 2) . "\n"
            . 'Average shipment value: Rs ' . number_format($summary['average'], 2) . "\n\n"
            . 'Open the Reports page for the full PDF and Excel files.';

        $emails = User::where('role', 'admin')->where('is_active', true)->pluck('email')
            ->reject(fn ($email) => str_ends_with($email, '@freightlink.local'));

        $sent = 0;

        foreach ($emails as $email) {
            try {
                Mail::raw($text, fn ($m) => $m->to($email)->subject('FreightLink daily summary, ' . $day->format('d M Y')));
                $sent++;
            } catch (Throwable $e) {
                Log::warning('Daily report email failed for ' . $email . ': ' . $e->getMessage());
            }
        }

        $this->log('daily-report', $sent > 0 ? 'info' : 'warning', null,
            "Daily summary for {$day->format('d M Y')} sent to {$sent} admin(s).");

        return ['emails_sent' => $sent];
    }

    // ------------------------------------------------------------------
    // The log
    // ------------------------------------------------------------------

    private function log(string $task, string $level, ?Shipment $shipment, string $message): void
    {
        AutomationLog::create([
            'task'        => $task,
            'level'       => $level,
            'shipment_id' => $shipment?->id,
            'message'     => mb_substr($message, 0, 500),
        ]);
    }

    // The same alert for the same shipment is written only once in 24 hours
    private function logOnce(string $task, string $level, Shipment $shipment, string $message): bool
    {
        $exists = AutomationLog::where('task', $task)
            ->where('shipment_id', $shipment->id)
            ->where('message', mb_substr($message, 0, 500))
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($exists) {
            return false;
        }

        $this->log($task, $level, $shipment, $message);

        return true;
    }
}