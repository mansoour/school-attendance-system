<?php

namespace App\Http\Controllers\School;

use App\Models\Student;
use App\Models\User;
use App\School\GuardianLinker;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Staff review of guardian accounts and their links to students. */
class GuardianController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : null;
        $links = DB::table('guardian_student')
            ->join('students', 'students.id', '=', 'guardian_student.student_id')
            ->join('users', 'users.id', '=', 'guardian_student.user_id')
            ->when(! $this->context()->isAllSchools(), fn ($q) => $q->where('students.school_id', $this->context()->id() ?? 0))
            ->when($status, fn ($q) => $q->where('guardian_student.status', $status))
            ->select('guardian_student.*', 'students.name as student_name', 'students.national_id', 'users.name as guardian_name', 'users.email', 'users.phone', 'users.email_verified_at')
            ->orderByDesc('guardian_student.created_at')->paginate(30)->withQueryString();

        return view('school.guardians', ['links' => $links, 'status' => $status]);
    }

    public function decide(Request $request, User $user, Student $student, GuardianLinker $linker)
    {
        $this->context()->authorize($student);
        $data = $request->validate(['decision' => 'required|in:approved,rejected']);
        abort_unless($user->children()->whereKey($student->id)->exists(), 404);
        if ($data['decision'] === 'approved') {
            abort_unless($user->hasVerifiedEmail(), 422, __('The guardian has not verified their email yet.'));
            $linker->approve($user, $student, $request->user());
        } else {
            $user->children()->updateExistingPivot($student->id, ['status' => 'rejected', 'approved_at' => null]);
            app(AuditLogger::class)->record('guardian.link_rejected', $student, ['guardian_id' => $user->id]);
        }

        return back()->with('status', __('Saved.'));
    }
}
