<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\User;
use App\School\Roles;
use App\School\Stats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * One dashboard, tailored to the signed-in role:
 * leadership (system administrator, principal, vice principal) sees the whole school,
 * student affairs sees the attendance desk, the health advisor sees excuses and health notes,
 * and the counselor sees repeated absences and counseling notes. Guardians use their own portal.
 */
class DashboardController extends Controller
{
    public function index(Request $request, Stats $stats)
    {
        $user = $request->user();
        if ($user->can('guardian.portal') && ! $user->can('attendance.view')) {
            return redirect()->route('school.guardian.index');
        }
        $context = $this->context();
        $date = $this->date($request->query('date'));
        $role = $user->isSystemAdmin() ? 'owner' : ($user->roles->pluck('name')->first(fn ($r) => in_array($r, Roles::STAFF, true)) ?? 'principal');
        $mode = match ($role) {
            'student_affairs' => 'affairs',
            'health_guide' => 'health',
            'counselor' => 'counselor',
            default => 'leadership',
        };

        $today = $stats->totals($date, $date);
        $todayByClass = $stats->byClass($date, $date);
        $data = [
            'date' => $date,
            'role' => $role,
            'mode' => $mode,
            'students' => $context->scope(Student::query())->where('is_active', true)->count(),
            'today' => $today,
            'notTaken' => $todayByClass->where('records', 0)->where('students', '>', 0)->values(),
            'todayByClass' => $todayByClass->keyBy(fn ($row) => $row['classroom']->id),
            'pendingExcuses' => $context->scope(Excuse::query())->where('status', 'pending')->count(),
            'absentToday' => $context->scope(AttendanceRecord::query())->with('student', 'classroom.grade', 'excuses')->where('date', $date->toDateString())->whereIn('status', ['absent', 'late'])->get()->sortBy(fn ($r) => $r->student?->name)->values(),
            'alerts' => $user->notifications()->latest()->limit(5)->get(),
        ];

        if ($mode === 'leadership') {
            $data['classes'] = $stats->byClass($date->copy()->startOfMonth(), $date);
            $staff = $context->scope(User::query())->role(Roles::STAFF)->with('roles')->get()->flatMap(fn ($u) => $u->roles->pluck('name'))->countBy();
            $guardians = $context->isAllSchools() ? User::role('guardian')->count() : User::role('guardian')->whereHas('children', fn ($q) => $q->where('school_id', $context->id()))->count();
            $data['roleCounts'] = $staff->put('guardian', $guardians);
        }
        if ($mode === 'affairs') {
            $data['pendingGuardians'] = DB::table('guardian_student')->join('students', 'students.id', '=', 'guardian_student.student_id')
                ->where('guardian_student.status', 'pending')->when(! $context->isAllSchools(), fn ($q) => $q->where('students.school_id', $context->id() ?? 0))->count();
            $data['unassigned'] = $context->scope(Student::query())->where('is_active', true)->whereNull('classroom_id')->count();
        }
        if ($mode === 'health') {
            $data['excuses'] = $context->scope(Excuse::query())->with('student.classroom.grade')->where('status', 'pending')->orderByRaw("case when type = 'medical' then 0 else 1 end")->latest()->limit(8)->get();
            $data['notes'] = $context->scope(StudentNote::query())->with('student', 'author')->where('category', 'health')->latest()->limit(6)->get();
        }
        if ($mode === 'counselor') {
            $data['topAbsent'] = $stats->topAbsent($date->copy()->subDays(30), $date, 10);
            $data['notes'] = $context->scope(StudentNote::query())->with('student', 'author')->whereIn('category', ['counseling', 'behavior'])->latest()->limit(6)->get();
        }

        return view('school.dashboard', $data);
    }
}
