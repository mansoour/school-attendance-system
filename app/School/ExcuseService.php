<?php

namespace App\School;

use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExcuseService
{
    public function __construct(private Notifier $notifier) {}

    public function submit(AttendanceRecord $record, User $actor, string $type, string $reason, ?UploadedFile $attachment = null, bool $approve = false): Excuse
    {
        $excuse = DB::transaction(function () use ($record, $actor, $type, $reason, $attachment, $approve) {
            $excuse = Excuse::create([
                'school_id' => $record->school_id,
                'student_id' => $record->student_id,
                'attendance_record_id' => $record->id,
                'date' => $record->date,
                'type' => $type,
                'reason' => $reason,
                'attachment_path' => $attachment?->store('excuses', 'local'),
                'status' => 'pending',
                'submitted_by' => $actor->id,
            ]);
            if ($approve) {
                $this->review($excuse, $actor, 'approved', null, false);
            }

            return $excuse;
        });
        if (! $approve) {
            $student = $record->student;
            $this->notifier->toStaff($record->school_id, 'excuse_submitted', ['student' => $student->name, 'date' => $record->date->toDateString()], route('school.excuses.index', ['status' => 'pending']));
        }

        return $excuse;
    }

    public function review(Excuse $excuse, User $actor, string $status, ?string $note, bool $notify = true): void
    {
        DB::transaction(function () use ($excuse, $actor, $status, $note) {
            $excuse->update(['status' => $status, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note]);
            $record = $excuse->record;
            if ($record && $status === 'approved' && $record->status === 'absent') {
                $record->update(['status' => 'excused']);
            }
            if ($record && $status === 'rejected' && $record->status === 'excused') {
                $record->update(['status' => 'absent']);
            }
        });
        if ($notify) {
            $this->notifier->toGuardians($excuse->student, 'excuse_reviewed', ['date' => $excuse->date->toDateString(), 'status' => __('school.excuse_status.'.$status)], route('school.guardian.index'));
        }
    }
}
