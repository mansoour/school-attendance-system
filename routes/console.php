<?php

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Operations\SqliteBackup;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
Artisan::command('school:admin {email} {--name=System administrator}', function () {
    $email = strtolower($this->argument('email'));
    $user = User::firstOrNew(['email' => $email]);
    if (! $user->exists) {
        $user->fill(['name' => $this->option('name'), 'password' => Str::password(32), 'locale' => config('app.locale')])->save();
        $user->markEmailAsVerified();
    }
    $user->assignRole('owner');
    app(AuditLogger::class)->record('owner.assigned', $user);
    Password::sendResetLink(['email' => $email]);
    $this->info('System administrator ready. A password setup link was sent to '.$email.' (see storage/logs/laravel.log with the log mail driver).');
})->purpose('Create or promote the system administrator and send a password setup link');
Artisan::command('school:demo', function () {
    if (School::where('code', 'DEMO')->exists()) {
        $this->error('The demo school already exists.');

        return 1;
    }
    DB::transaction(function () {
        $school = School::create(['name' => 'مدرسة تجريبية', 'code' => 'DEMO', 'city' => 'المدينة']);
        $first = ['سارة', 'ريم', 'نورة', 'لجين', 'فاطمة', 'هيا', 'جود', 'مها', 'ليان', 'دانة', 'رهف', 'شهد'];
        $last = ['العتيبي', 'القحطاني', 'الشهري', 'الغامدي', 'الزهراني', 'الحربي', 'المطيري', 'الدوسري'];
        $id = 1100000000;
        foreach ([['رابع ابتدائي', 'primary'], ['خامس ابتدائي', 'primary'], ['سادس ابتدائي', 'primary'], ['أول متوسط', 'intermediate'], ['ثاني متوسط', 'intermediate'], ['ثالث متوسط', 'intermediate']] as $order => [$gradeName, $stage]) {
            $grade = Grade::create(['school_id' => $school->id, 'name' => $gradeName, 'stage' => $stage, 'sort_order' => $order + 1]);
            foreach (['أ', 'ب'] as $section) {
                $classroom = Classroom::create(['school_id' => $school->id, 'grade_id' => $grade->id, 'name' => $section]);
                foreach (range(1, 8) as $n) {
                    $student = Student::create(['school_id' => $school->id, 'classroom_id' => $classroom->id, 'name' => $first[array_rand($first)].' '.$last[array_rand($last)], 'national_id' => (string) ++$id, 'guardian_phone' => '05'.random_int(10000000, 99999999)]);
                    foreach (range(1, 10) as $daysAgo) {
                        $date = today()->subDays($daysAgo);
                        if ($date->isFriday() || $date->isSaturday()) {
                            continue;
                        }
                        $roll = random_int(1, 100);
                        AttendanceRecord::create(['school_id' => $school->id, 'student_id' => $student->id, 'classroom_id' => $classroom->id, 'date' => $date, 'status' => $roll <= 6 ? 'absent' : ($roll <= 9 ? 'late' : ($roll <= 11 ? 'excused' : 'present'))]);
                    }
                }
            }
        }
    });
    $this->info('Demo school created with fictional students and attendance. No accounts or passwords were created.');
})->purpose('Create a fictional demo school with sample attendance');
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
