<?php

namespace App\School;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(private Notifier $notifier, private SettingsService $settings) {}

    /**
     * Saves one day of attendance for a class and notifies guardians about new absences or late arrivals.
     *
     * @param  array<int, array{status: string, late_minutes?: int|null, note?: string|null}>  $entries  keyed by student id
     * @return array{saved: int, absent: int, late: int}
     */
    public function saveClass(Classroom $classroom, CarbonInterface $date, array $entries, User $actor): array
    {
        $students = $classroom->students()->where('is_active', true)->get()->keyBy('id');
        $changed = [];
        $summary = ['saved' => 0, 'absent' => 0, 'late' => 0];

        DB::transaction(function () use ($classroom, $date, $entries, $actor, $students, &$changed, &$summary) {
            foreach ($entries as $studentId => $entry) {
                $student = $students->get((int) $studentId);
                if (! $student || ! in_array($entry['status'] ?? null, AttendanceRecord::STATUSES, true)) {
                    continue;
                }
                $record = AttendanceRecord::firstOrNew(['student_id' => $student->id, 'date' => $date->toDateString()]);
                $previous = $record->exists ? $record->status : null;
                $record->fill([
                    'school_id' => $classroom->school_id,
                    'classroom_id' => $classroom->id,
                    'status' => $entry['status'],
                    'late_minutes' => $entry['status'] === 'late' ? ($entry['late_minutes'] ?? null) : null,
                    'note' => $entry['note'] ?? null,
                    'recorded_by' => $actor->id,
                ])->save();
                $summary['saved']++;
                if (in_array($record->status, ['absent', 'late'], true)) {
                    $summary[$record->status]++;
                }
                if ($previous !== $record->status && in_array($record->status, ['absent', 'late'], true)) {
                    $changed[] = [$student, $record];
                }
            }
            app(AuditLogger::class)->record('attendance.class_saved', $classroom, ['date' => $date->toDateString()] + $summary, actor: $actor);
        });

        foreach ($changed as [$student, $record]) {
            $this->announce($student, $record, $classroom);
        }

        return $summary;
    }

    private function announce(Student $student, AttendanceRecord $record, Classroom $classroom): void
    {
        $date = $record->date->toDateString();
        $url = route('school.guardian.index');
        if ($record->status === 'late') {
            $this->notifier->toGuardians($student, 'student_late', ['date' => $date], $url);

            return;
        }
        $this->notifier->toGuardians($student, 'student_absent', ['date' => $date], route('school.guardian.excuse', $record));
        $this->notifier->toStaff($student->school_id, 'student_absent_staff', ['student' => $student->name, 'classroom' => $classroom->label(), 'date' => $date], route('school.attendance.index', ['date' => $date]));

        $threshold = (int) $this->settings->get('school_notify.absence_threshold');
        $count = AttendanceRecord::where('student_id', $student->id)->where('status', 'absent')->where('date', '>=', $record->date->copy()->subDays(30))->count();
        if ($threshold > 0 && $count === $threshold) {
            $this->notifier->toStaff($student->school_id, 'repeated_absence', ['student' => $student->name, 'count' => $count], route('school.students.show', $student));
        }
    }
}
