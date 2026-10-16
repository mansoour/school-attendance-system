<?php

namespace App\Operations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthReport
{
    public function checks(): array
    {
        $heartbeat = Cache::get('starter.scheduler_heartbeat');
        $directory = config('database.default') === 'sqlite' ? 'backups' : config('backup.backup.name');
        $backups = Storage::disk('local')->files($directory);
        $latest = collect($backups)->filter(fn ($f) => str_ends_with($f, '.sqlite') || str_ends_with($f, '.zip'))->map(fn ($f) => Storage::disk('local')->lastModified($f))->max();

        return [
            'Database' => DB::select('select 1') !== [],
            'Storage' => is_writable(storage_path('app')),
            'Scheduler' => $heartbeat && $heartbeat > now()->subMinutes(5)->timestamp,
            'Queue' => DB::table('jobs')->where('created_at', '<', now()->subMinutes(15)->timestamp)->count() === 0,
            'Failed jobs' => DB::table('failed_jobs')->count() === 0,
            'Mail transport' => ! app()->environment('production') || ! in_array(config('mail.default'), ['log', 'array']),
            'Backup freshness' => $latest && $latest > now()->subDays(2)->timestamp,
            'Production debug' => ! app()->environment('production') || ! config('app.debug'),
        ];
    }
}
