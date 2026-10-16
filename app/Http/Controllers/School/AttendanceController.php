<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\School\AttendanceService;
use App\School\Calendar;
use App\School\Stats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /** Daily overview: which classes have taken attendance and who is absent. */
    public function index(Request $request, Stats $stats)
    {
        $date = $this->schoolDate($request->query('date'));
        $gradeId = $request->integer('grade') ?: null;
        $classes = $stats->byClass($date, $date, $gradeId);
        $records = $this->context()->scope(AttendanceRecord::query())->with('student', 'classroom.grade', 'excuses')
            ->where('date', $date->toDateString())->whereIn('status', ['absent', 'late', 'excused'])
            ->when($gradeId, fn ($q) => $q->whereIn('classroom_id', Classroom::where('grade_id', $gradeId)->select('id')))
            ->get()->sortBy(fn ($r) => $r->student?->name);

        return view('school.attendance.index', ['date' => $date, 'classes' => $classes, 'records' => $records, 'totals' => $stats->totals($date, $date, $gradeId), 'grades' => $this->gradeOptions()]);
    }

    /** Class picker: shows each class with today's progress. */
    public function classes(Request $request, Stats $stats)
    {
        $date = $this->schoolDate($request->query('date'));

        return view('school.attendance.classes', ['date' => $date, 'classes' => $stats->byClass($date, $date)]);
    }

    /** The class register for one day. */
    public function take(Request $request, Classroom $classroom)
    {
        $this->context()->authorize($classroom);
        $date = $this->schoolDate($request->query('date'));
        $records = AttendanceRecord::where('classroom_id', $classroom->id)->where('date', $date->toDateString())->get()->keyBy('student_id');
        $previous = $this->classOptions();

        return view('school.attendance.take', [
            'classroom' => $classroom->load('grade'),
            'date' => $date,
            'students' => $classroom->students()->where('is_active', true)->get(),
            'records' => $records,
            'classes' => $previous,
        ]);
    }

    public function save(Request $request, Classroom $classroom, AttendanceService $service)
    {
        $this->context()->authorize($classroom);
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today', function ($attribute, $value, $fail) {
                if (! Calendar::isSchoolDay(Carbon::parse($value))) {
                    $fail(__('Attendance can only be recorded on school days (Sunday to Thursday).'));
                }
            }],
            'entries' => 'required|array',
            'entries.*.status' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'entries.*.late_minutes' => 'nullable|integer|min:1|max:600',
            'entries.*.note' => 'nullable|string|max:500',
        ]);
        $summary = $service->saveClass($classroom, $this->date($data['date']), $data['entries'], $request->user());

        return redirect()->route('school.attendance.take', [$classroom, 'date' => $data['date']])
            ->with('status', __('Attendance saved: :saved students, :absent absent, :late late.', $summary));
    }
}
