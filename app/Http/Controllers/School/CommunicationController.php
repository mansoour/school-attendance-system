<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\GuardianContact;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    /** Absent and late students for a day, with WhatsApp, call and email shortcuts to their guardians. */
    public function index(Request $request)
    {
        $date = $this->date($request->query('date'));
        $status = in_array($request->query('status'), ['absent', 'late', 'excused'], true) ? [$request->query('status')] : ['absent', 'late'];
        $records = $this->context()->scope(AttendanceRecord::query())->with('student.classroom.grade', 'student.guardians')
            ->where('date', $date->toDateString())->whereIn('status', $status)
            ->when($request->integer('classroom'), fn ($q, $id) => $q->where('classroom_id', $id))
            ->when($request->integer('grade') && ! $request->integer('classroom'), fn ($q) => $q->whereIn('classroom_id', Classroom::where('grade_id', $request->integer('grade'))->select('id')))
            ->get()->sortBy(fn ($r) => [$r->classroom?->grade?->sort_order, $r->classroom?->name, $r->student?->name])->values();
        $contacts = GuardianContact::whereIn('student_id', $records->pluck('student_id'))->where('absence_date', $date->toDateString())->get()->groupBy('student_id');

        return view('school.communication', ['date' => $date, 'records' => $records, 'contacts' => $contacts, 'grades' => $this->gradeOptions(), 'classes' => $this->classOptions(), 'school' => $this->context()->school()]);
    }

    /** Records that a staff member contacted a guardian (called before the link opens). */
    public function log(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'channel' => ['required', Rule::in(GuardianContact::CHANNELS)],
            'absence_date' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);
        $student = Student::findOrFail($data['student_id']);
        $this->context()->authorize($student);
        GuardianContact::create($data + ['school_id' => $student->school_id, 'user_id' => $request->user()->id]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', __('Contact recorded.'));
    }
}
