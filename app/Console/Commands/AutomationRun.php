<?php

namespace App\Console\Commands;

use App\Services\AutomationService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AutomationRun extends Command
{
    protected $signature = 'automation:run {task=all : auto-assign, check-stuck, close-delivered, cleanup, daily-report or all}';

    protected $description = 'Runs the automatic FreightLink tasks';

    public function handle(AutomationService $service): int
    {
        $task  = $this->argument('task');
        $tasks = $task === 'all' ? AutomationService::TASKS : [$task];

        foreach ($tasks as $name) {
            try {
                $result = $service->run($name);
            } catch (InvalidArgumentException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->line(str_pad($name, 17) . collect($result)->map(fn ($value, $key) => $key . '=' . $value)->implode('   '));
        }

        return self::SUCCESS;
    }
}