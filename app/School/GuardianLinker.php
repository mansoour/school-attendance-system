<?php

namespace App\School;

use App\Models\Student;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Settings\SettingsService;

/**
 * Matches a registering guardian to a student and activates the link once the
 * guardian's email is verified (and, optionally, once staff approve it).
 */
class GuardianLinker
{
    public function __construct(private SettingsService $settings, private Notifier $notifier) {}

    /** Finds the student matching the details a guardian typed, per the configured verification mode. */
    public function match(string $nationalId, ?string $name, ?string $phone): ?Student
    {
        $student = Student::where('national_id', trim($nationalId))->where('is_active', true)->first();
        if (! $student) {
            return null;
        }
        $mode = $this->settings->get('guardians.match_mode');
        $nameOk = Student::normalizeName($name) === Student::normalizeName($student->name);
        $phoneOk = $student->phone && Student::normalizePhone($phone) === Student::normalizePhone($student->phone);

        return match ($mode) {
            'national_id_phone' => $phoneOk ? $student : null,
            'national_id_name_phone' => $nameOk && $phoneOk ? $student : null,
            default => $nameOk ? $student : null,
        };
    }

    public function request(User $guardian, Student $student, string $relation): void
    {
        $guardian->children()->syncWithoutDetaching([$student->id => ['relation' => $relation, 'status' => 'pending']]);
        app(AuditLogger::class)->record('guardian.link_requested', $student, ['guardian_id' => $guardian->id, 'relation' => $relation], actor: $guardian);
    }

    /** Called once the guardian's email is verified. */
    public function activate(User $guardian): void
    {
        foreach ($guardian->children()->wherePivot('status', 'pending')->get() as $student) {
            if ($this->settings->get('guardians.require_approval')) {
                $this->notifier->toStaff($student->school_id, 'guardian_registered', ['guardian' => $guardian->name, 'student' => $student->name], route('school.guardians.index'));

                continue;
            }
            $this->approve($guardian, $student, null);
        }
    }

    public function approve(User $guardian, Student $student, ?User $actor): void
    {
        $relation = $guardian->children()->whereKey($student->id)->first()?->pivot->relation;
        $guardian->children()->updateExistingPivot($student->id, ['status' => 'approved', 'approved_at' => now()]);
        if ($this->settings->get('guardians.fill_student_contact')) {
            $student->fill(array_filter([
                'guardian_name' => $student->guardian_name ?: $guardian->name,
                'guardian_phone' => $student->guardian_phone ?: $guardian->phone,
                'guardian_email' => $student->guardian_email ?: $guardian->email,
                'guardian_relation' => $student->guardian_relation ?: $relation,
            ]))->save();
        }
        app(AuditLogger::class)->record('guardian.link_approved', $student, ['guardian_id' => $guardian->id], actor: $actor ?? $guardian);
        $this->notifier->send([$guardian], 'guardian_link_approved', ['student' => $student->name], route('school.guardian.index'));
        if (! $actor) {
            $this->notifier->toStaff($student->school_id, 'guardian_registered', ['guardian' => $guardian->name, 'student' => $student->name], route('school.guardians.index'));
        }
    }
}
