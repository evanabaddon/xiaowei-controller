<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearAutomationCache extends Command
{
    protected $signature = 'automation:clear';

    protected $description = 'Clear automation tasks from cache';

    public function handle()
    {
        Cache::flush(); // atau khusus task: Cache::forget("task_for_...")

        $this->info('Automation cache cleared.');
    }
}
