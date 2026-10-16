<?php

namespace App\School;

use App\Models\Student;
use App\Models\User;
use App\Notifications\SchoolNotification;
use App\Settings\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Sends school events to guardians and staff according to the administrator's
 * notification settings (enabled, in-app, email and recipient roles per event).
 */
class Notifier
{
    /** Events delivered to a student's guardians. */
    public const GUARDIAN_EVENTS = ['student_absent', 'student_late', 'excuse_reviewed', 'note_shared', 'guardian_link_approved'];

    /** Events delivered to staff roles of the student's school. */
    public const STAFF_EVENTS = ['excuse_submitted', 'guardian_registered', 'repeated_absence', 'student_absent_staff'];

    public const DEFAULT_ROLES = [
        'excuse_submitted' => ['health_guide', 'student_affairs', 'vice_principal'],
        'guardian_registered' => ['student_affairs', 'principal'],
        'repeated_absence' => ['counselor', 'principal', 'vice_principal'],
        'student_absent_staff' => ['vice_principal'],
    ];

    public function __construct(private SettingsService $settings) {}

    public static function events(): array
    {
        return array_merge(self::GUARDIAN_EVENTS, self::STAFF_EVENTS);
    }

    /** @return list<string> */
    public function channels(string $event): array
    {
        if (! $this->settings->get("school_notify.$event.enabled")) {
            return [];
        }

        return array_values(array_filter([
            $this->settings->get("school_notify.$event.database") ? 'database' : null,
            $this->settings->get("school_notify.$event.mail") ? 'mail' : null,
        ]));
    }

    public function toGuardians(Student $student, string $event, array $parameters, string $url): void
    {
        $this->send($student->approvedGuardians(), $event, $parameters + ['student' => $student->name], $url);
    }

    public function toStaff(int $schoolId, string $event, array $parameters, string $url): void
    {
        $roles = $this->settings->get("school_notify.$event.roles") ?: [];
        if (! $roles) {
            return;
        }
        $staff = User::where('school_id', $schoolId)->where('is_active', true)->role($roles)->get();
        $this->send($staff, $event, $parameters, $url);
    }

    /** @param  Collection<int, User>|iterable<User>  $users */
    public function send(iterable $users, string $event, array $parameters, string $url): void
    {
        $channels = $this->channels($event);
        $users = collect($users)->unique('id');
        if (! $channels || $users->isEmpty()) {
            return;
        }
        Notification::send($users, new SchoolNotification($event, array_map('strval', $parameters), $url, $channels));
    }
}
