<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\School\ExcuseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExcuseController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), Excuse::STATUSES, true) ? $request->query('status') : null;
        $excuses = $this->context()->scope(Excuse::query())->with('student.classroom.grade', 'submitter', 'reviewer')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('date'), fn ($q, $d) => $q->where('date', $this->date($d)->toDateString()))
            ->latest()->paginate(25)->withQueryString();

        // Absences without an excuse, so staff can record one received on paper or by phone.
        $date = $this->schoolDate($request->query('date'));
        $unexcused = $this->context()->scope(AttendanceRecord::query())->with('student.classroom.grade')
            ->where('date', $date->toDateString())->where('status', 'absent')->whereDoesntHave('excuses')->get();

        return view('school.excuses', ['excuses' => $excuses, 'status' => $status, 'date' => $date, 'unexcused' => $unexcused, 'counts' => $this->context()->scope(Excuse::query())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')]);
    }

    public function store(Request $request, ExcuseService $service)
    {
        $data = $request->validate([
            'attendance_record_id' => 'required|integer|exists:attendance_records,id',
            'type' => ['required', Rule::in(Excuse::TYPES)],
            'reason' => 'required|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'approve' => 'boolean',
        ]);
        $record = AttendanceRecord::findOrFail($data['attendance_record_id']);
        $this->context()->authorize($record);
        abort_unless(in_array($record->status, ['absent', 'late', 'excused'], true), 422);
        $service->submit($record, $request->user(), $data['type'], $data['reason'], $request->file('attachment'), $request->boolean('approve'));

        return back()->with('status', __('Excuse recorded.'));
    }

    public function review(Request $request, Excuse $excuse, ExcuseService $service)
    {
        $this->context()->authorize($excuse);
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'review_note' => 'nullable|string|max:1000']);
        $service->review($excuse, $request->user(), $data['status'], $data['review_note'] ?? null);

        return back()->with('status', __('Excuse reviewed.'));
    }

    public function attachment(Request $request, Excuse $excuse)
    {
        $user = $request->user();
        $isGuardian = $user->approvedChildren()->whereKey($excuse->student_id)->exists();
        if (! $isGuardian) {
            abort_unless($user->can('excuses.view'), 403);
            $this->context()->authorize($excuse);
        }
        abort_unless($excuse->attachment_path && Storage::disk('local')->exists($excuse->attachment_path), 404);

        return Storage::disk('local')->download($excuse->attachment_path);
    }
}
