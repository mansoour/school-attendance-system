<?php

namespace App\Http\Controllers\School;

use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    public function index()
    {
        $grades = $this->context()->scope(Grade::query())->withCount(['classrooms', 'students'])->orderBy('sort_order')->orderBy('name')->get();

        return view('school.grades', ['grades' => $grades]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->context()->requireId();
        Grade::create($this->validated($request, $schoolId) + ['school_id' => $schoolId]);

        return back()->with('status', __('Grade added.'));
    }

    public function update(Request $request, Grade $grade)
    {
        $this->context()->authorize($grade);
        $grade->update($this->validated($request, $grade->school_id, $grade));

        return back()->with('status', __('Saved.'));
    }

    public function destroy(Grade $grade)
    {
        $this->context()->authorize($grade);
        abort_if($grade->students()->exists(), 409, __('Move the students to another class before deleting this grade.'));
        $grade->delete();

        return back()->with('status', __('Deleted.'));
    }

    private function validated(Request $request, int $schoolId, ?Grade $grade = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('grades')->where('school_id', $schoolId)->ignore($grade?->id)],
            'stage' => ['required', Rule::in(Grade::STAGES)],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]) + ['sort_order' => 0];
    }
}
