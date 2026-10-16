<?php

namespace App\Http\Controllers\School;

use App\Models\Classroom;
use App\Models\Excuse;
use App\Models\GuardianContact;
use App\Models\Student;
use App\Models\StudentNote;
use App\School\StudentImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = $this->context()->scope(Student::query())->with('classroom.grade')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', "%$term%")->orWhere('national_id', 'like', "%$term%")->orWhere('phone', 'like', "%$term%")))
            ->when($request->integer('classroom'), fn ($q, $id) => $q->where('classroom_id', $id))
            ->when($request->integer('grade'), fn ($q, $id) => $q->whereIn('classroom_id', Classroom::where('grade_id', $id)->select('id')))
            ->when($request->query('unassigned') === '1', fn ($q) => $q->whereNull('classroom_id'))
            ->withCount(['attendance as absences_count' => fn ($q) => $q->whereIn('status', ['absent', 'excused'])])
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('school.students.index', ['students' => $students, 'grades' => $this->gradeOptions(), 'classes' => $this->classOptions()]);
    }

    public function create()
    {
        return view('school.students.form', ['student' => new Student, 'classes' => $this->classOptions()]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->context()->requireId();
        $student = Student::create($this->validated($request, $schoolId) + ['school_id' => $schoolId]);

        return redirect()->route('school.students.show', $student)->with('status', __('Student added.'));
    }

    public function show(Student $student)
    {
        $this->context()->authorize($student);
        $student->load('classroom.grade', 'guardians');

        return view('school.students.show', [
            'student' => $student,
            'records' => $student->attendance()->with('recorder', 'excuses')->where('status', '!=', 'present')->latest('date')->limit(60)->get(),
            'counts' => $student->attendance()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'excuses' => Excuse::where('student_id', $student->id)->with('submitter', 'reviewer')->latest()->get(),
            'notes' => StudentNote::where('student_id', $student->id)->with('author')->latest()->get(),
            'contacts' => $student->hasMany(GuardianContact::class)->with('user')->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Student $student)
    {
        $this->context()->authorize($student);

        return view('school.students.form', ['student' => $student, 'classes' => $this->classOptions()]);
    }

    public function update(Request $request, Student $student)
    {
        $this->context()->authorize($student);
        $student->update($this->validated($request, $student->school_id, $student));

        return redirect()->route('school.students.show', $student)->with('status', __('Saved.'));
    }

    public function destroy(Student $student)
    {
        $this->context()->authorize($student);
        $student->delete();

        return redirect()->route('school.students.index')->with('status', __('Deleted.'));
    }

    public function move(Request $request)
    {
        $data = $request->validate(['students' => 'required|array', 'students.*' => 'integer', 'classroom_id' => 'nullable|integer|exists:classrooms,id']);
        $classroom = isset($data['classroom_id']) ? Classroom::findOrFail($data['classroom_id']) : null;
        if ($classroom) {
            $this->context()->authorize($classroom);
        }
        $students = $this->context()->scope(Student::query())->whereIn('id', $data['students'])->get();
        foreach ($students as $student) {
            abort_if($classroom && $student->school_id !== $classroom->school_id, 422);
            $student->update(['classroom_id' => $classroom?->id]);
        }

        return back()->with('status', __(':count students moved.', ['count' => $students->count()]));
    }

    public function importForm()
    {
        return view('school.students.import', ['columns' => StudentImporter::COLUMNS]);
    }

    public function template(StudentImporter $importer)
    {
        $writer = new Xlsx($importer->template());

        return response()->streamDownload(fn () => $writer->save('php://output'), 'students-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(Request $request, StudentImporter $importer)
    {
        $schoolId = $this->context()->requireId();
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120']);
        $result = $importer->import($request->file('file')->getRealPath(), $schoolId, $request->boolean('create_classes'));

        return back()->with('status', __('Import finished: :created added, :updated updated.', ['created' => $result['created'], 'updated' => $result['updated']]))->with('import_errors', $result['errors']);
    }

    private function validated(Request $request, int $schoolId, ?Student $student = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'national_id' => ['required', 'string', 'max:20', Rule::unique('students')->ignore($student?->id)],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')->where('school_id', $schoolId)],
            'email' => 'nullable|email|max:200',
            'phone' => 'nullable|string|max:40',
            'guardian_name' => 'nullable|string|max:200',
            'guardian_phone' => 'nullable|string|max:40',
            'guardian_email' => 'nullable|email|max:200',
            'guardian_relation' => ['nullable', Rule::in(Student::RELATIONS)],
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
