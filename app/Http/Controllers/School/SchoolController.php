<?php

namespace App\Http\Controllers\School;

use App\Models\School;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    public function switch(Request $request)
    {
        $data = $request->validate(['school_id' => 'nullable|integer|exists:schools,id']);
        session(['school_id' => $data['school_id'] ?? null]);

        return back();
    }

    public function index()
    {
        return view('school.schools', ['schools' => School::withCount(['students', 'classrooms', 'users'])->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $school = School::create($this->validated($request));
        session(['school_id' => $school->id]);

        return back()->with('status', __('School created.'));
    }

    public function update(Request $request, School $school)
    {
        $school->update($this->validated($request, $school));

        return back()->with('status', __('Saved.'));
    }

    public function destroy(School $school)
    {
        abort_if($school->students()->exists(), 409, __('Remove or move the students before deleting the school.'));
        $school->delete();
        if ((int) session('school_id') === $school->id) {
            session()->forget('school_id');
        }

        return back()->with('status', __('Deleted.'));
    }

    public function settings()
    {
        $school = $this->context()->school();
        if (! $school) {
            // The system administrator is viewing all schools: let them pick one first.
            return view('school.choose-school', ['schools' => School::withCount('students')->orderBy('name')->get()]);
        }

        return view('school.settings', ['school' => $school]);
    }

    public function saveSettings(Request $request)
    {
        $school = $this->context()->school();
        abort_unless($school, 422, __('Choose a school first.'));
        $school->update($this->validated($request, $school, true));
        app(AuditLogger::class)->record('school.settings_saved', $school);

        return back()->with('status', __('Saved.'));
    }

    private function validated(Request $request, ?School $school = null, bool $settingsOnly = false): array
    {
        $rules = [
            'name' => 'required|string|max:200',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:200',
            'day_starts_at' => 'required|date_format:H:i',
            'late_after_minutes' => 'required|integer|min:0|max:240',
        ];
        if (! $settingsOnly) {
            $rules += ['code' => ['nullable', 'string', 'max:40', Rule::unique('schools', 'code')->ignore($school?->id)], 'is_active' => 'boolean'];
        }
        $data = $request->validate($rules);
        if (! $settingsOnly) {
            $data['is_active'] = $request->boolean('is_active');
        }

        return $data;
    }
}
