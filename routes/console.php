<?php

use App\Models\User;
use App\Operations\SqliteBackup;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('starter:install', function () {
    $database = config('database.connections.sqlite.database');
    if (config('database.default') === 'sqlite' && $database !== ':memory:' && ! is_file($database)) {
        if (! is_dir(dirname($database))) {
            $this->error('The SQLite database directory does not exist. Check DB_DATABASE.');

            return 1;
        }
        touch($database);
    }
    if (! config('app.key')) {
        $this->call('key:generate', ['--force' => true]);
    }
    $this->call('migrate', ['--force' => true]);
    $this->call('db:seed', ['--force' => true]);
    $this->info('Starter kit installed. No shared credentials or default accounts were created.');
});
Artisan::command('starter:owner {email}', function () {
    $user = User::where('email', $this->argument('email'))->first();
    if (! $user) {
        $this->error('Register an account first, then run this command.');

        return 1;
    }
    $user->assignRole('owner');
    app(AuditLogger::class)->record('owner.assigned', $user);
    $this->info('Owner role assigned. Email verification and MFA still apply.');
});
Artisan::command('starter:backup', function () {
    if (config('database.default') !== 'sqlite') {
        $this->error('Use backup:run for non-SQLite connections.');

        return 1;
    }
    app(SqliteBackup::class)->create();
    $this->info('Independent database snapshot created and integrity checked.');
    foreach (Storage::disk('local')->files('backups') as $file) {
        if ((str_ends_with($file, '.sqlite') || str_ends_with($file, '.zip')) && Storage::disk('local')->lastModified($file) < now()->subDays(30)->timestamp) {
            Storage::disk('local')->delete($file);
        }
    }
});
Schedule::call(fn () => Cache::put('starter.scheduler_heartbeat', now()->timestamp, 600))->everyMinute()->name('starter.heartbeat');
Schedule::command('starter:backup')->dailyAt('02:00')->when(fn () => config('database.default') === 'sqlite');
Schedule::command('backup:run --only-files')->dailyAt('02:15')->when(fn () => config('database.default') === 'sqlite');
Schedule::command('backup:run')->dailyAt('02:00')->when(fn () => config('database.default') !== 'sqlite');
Schedule::command('backup:clean')->dailyAt('03:00');
