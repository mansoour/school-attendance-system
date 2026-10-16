<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Student;
use App\Models\StudentNote;
use App\School\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $category = in_array($request->query('category'), StudentNote::CATEGORIES, true) ? $request->query('category') : null;
        $notes = $this->context()->scope(StudentNote::query())->with('student.classroom.grade', 'author')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($request->query('q'), fn ($q, $term) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%$term%")->orWhere('national_id', $term)))
            ->latest()->paginate(25)->withQueryString();

        return view('school.notes', [
            'notes' => $notes,
            'category' => $category,
            'students' => $this->context()->scope(Student::query())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'national_id']),
        ]);
    }

    public function store(Request $request, Notifier $notifier)
    {
        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'attendance_record_id' => 'nullable|integer|exists:attendance_records,id',
            'category' => ['required', Rule::in(StudentNote::CATEGORIES)],
            'body' => 'required|string|max:3000',
            'visible_to_guardian' => 'boolean',
        ]);
        $student = Student::findOrFail($data['student_id']);
        $this->context()->authorize($student);
        if (! empty($data['attendance_record_id'])) {
            abort_unless(AttendanceRecord::whereKey($data['attendance_record_id'])->where('student_id', $student->id)->exists(), 422);
        }
        $note = StudentNote::create($data + ['school_id' => $student->school_id, 'author_id' => $request->user()->id, 'visible_to_guardian' => $request->boolean('visible_to_guardian')]);
        if ($note->visible_to_guardian) {
            $notifier->toGuardians($student, 'note_shared', [], route('school.guardian.index'));
        }

        return back()->with('status', __('Note added.'));
    }

    public function destroy(Request $request, StudentNote $note)
    {
        $this->context()->authorize($note);
        abort_unless($note->author_id === $request->user()->id || $request->user()->can('employees.manage'), 403);
        $note->delete();

        return back()->with('status', __('Deleted.'));
    }
}
