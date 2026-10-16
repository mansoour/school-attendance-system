<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\Models\Student;
use App\Models\StudentNote;
use App\School\ExcuseService;
use App\School\GuardianLinker;
use App\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The guardian's view: linked children, their absences, excuses and shared notes. */
class GuardianPortalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $children = $user->children()->with(['classroom.grade', 'school'])->get();
        $approved = $children->where('pivot.status', 'approved');
        $ids = $approved->pluck('id');

        return view('school.guardian.index', [
            'children' => $children,
            'records' => AttendanceRecord::with('student', 'excuses')->whereIn('student_id', $ids)->where('status', '!=', 'present')->latest('date')->limit(50)->get(),
            'counts' => AttendanceRecord::whereIn('student_id', $ids)->where('date', '>=', today()->subDays(30))->selectRaw('student_id, status, count(*) as total')->groupBy('student_id', 'status')->get()->groupBy('student_id'),
            'notes' => StudentNote::with('student', 'author')->whereIn('student_id', $ids)->where('visible_to_guardian', true)->latest()->limit(20)->get(),
            'matchMode' => app(SettingsService::class)->get('guardians.match_mode'),
        ]);
    }

    /** Links an additional child to an existing guardian account. */
    public function link(Request $request, GuardianLinker $linker)
    {
        $data = $request->validate([
            'student_national_id' => 'required|string|max:20',
            'student_name' => 'nullable|string|max:200',
            'student_phone' => 'nullable|string|max:40',
            'relation' => ['required', Rule::in(Student::RELATIONS)],
        ]);
        $student = $linker->match($data['student_national_id'], $data['student_name'] ?? null, $data['student_phone'] ?? null);
        if (! $student) {
            throw ValidationException::withMessages(['student_national_id' => __('No student matches these details. Check them or contact the school.')]);
        }
        $linker->request($request->user(), $student, $data['relation']);
        $linker->activate($request->user());

        return back()->with('status', __('The student was added to your account.'));
    }

    public function excuseForm(Request $request, AttendanceRecord $record)
    {
        $this->authorizeRecord($request, $record);

        return view('school.guardian.excuse', ['record' => $record->load('student', 'excuses')]);
    }

    public function submitExcuse(Request $request, AttendanceRecord $record, ExcuseService $service)
    {
        $this->authorizeRecord($request, $record);
        abort_unless(in_array($record->status, ['absent', 'late'], true), 422);
        abort_if($record->excuses()->whereIn('status', ['pending', 'approved'])->exists(), 409, __('An excuse has already been submitted for this day.'));
        $data = $request->validate([
            'type' => ['required', Rule::in(Excuse::TYPES)],
            'reason' => 'required|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);
        $service->submit($record, $request->user(), $data['type'], $data['reason'], $request->file('attachment'));

        return redirect()->route('school.guardian.index')->with('status', __('Your excuse was sent to the school for review.'));
    }

    private function authorizeRecord(Request $request, AttendanceRecord $record): void
    {
        abort_unless($request->user()->approvedChildren()->whereKey($record->student_id)->exists(), 404);
    }
}
