<?php

namespace App\Http\Controllers\School;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\User;
use App\School\Roles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $classes = $this->context()->scope(Classroom::query())->with('grade', 'supervisor')->withCount('students')
            ->when($request->integer('grade'), fn ($q, $grade) => $q->where('grade_id', $grade))->get()
            ->sortBy(fn ($c) => sprintf('%05d-%s', $c->grade?->sort_order ?? 0, $c->name))->values();

        return view('school.classes', [
            'classes' => $classes,
            'grades' => $this->gradeOptions(),
            'staff' => $this->context()->scope(User::query())->role(Roles::STAFF)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $grade = Grade::findOrFail($data['grade_id']);
        $this->context()->authorize($grade);
        foreach (preg_split('/[,،]\s*/u', $data['name']) as $name) {
            if (trim($name) !== '' && ! $grade->classrooms()->where('name', trim($name))->exists()) {
                Classroom::create(['school_id' => $grade->school_id, 'grade_id' => $grade->id, 'name' => trim($name)] + collect($data)->only('capacity', 'supervisor_id')->all());
            }
        }

        return back()->with('status', __('Class saved.'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->context()->authorize($classroom);
        $data = $this->validated($request, $classroom);
        $grade = Grade::findOrFail($data['grade_id']);
        abort_unless($grade->school_id === $classroom->school_id, 422);
        $classroom->update($data);

        return back()->with('status', __('Saved.'));
    }

    public function destroy(Classroom $classroom)
    {
        $this->context()->authorize($classroom);
        abort_if($classroom->students()->exists(), 409, __('Move the students to another class before deleting this class.'));
        $classroom->delete();

        return back()->with('status', __('Deleted.'));
    }

    private function validated(Request $request, ?Classroom $classroom = null): array
    {
        if ($request->filled('supervisor_id')) {
            $supervisor = User::find($request->integer('supervisor_id'));
            abort_unless($supervisor && $supervisor->school_id === ($classroom?->school_id ?? Grade::find($request->integer('grade_id'))?->school_id), 422);
        }

        return $request->validate([
            'grade_id' => 'required|integer|exists:grades,id',
            'name' => ['required', 'string', 'max:120', Rule::unique('classrooms')->where('grade_id', $request->input('grade_id'))->ignore($classroom?->id)],
            'capacity' => 'nullable|integer|min:1|max:200',
            'supervisor_id' => 'nullable|integer|exists:users,id',
        ]);
    }
}
