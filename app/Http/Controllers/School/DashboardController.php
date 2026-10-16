<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Excuse;
use App\Models\Student;
use App\Models\User;
use App\School\Roles;
use App\School\Stats;
use Illuminate\Http\Request;

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
        $monthStart = $date->copy()->startOfMonth();

        $students = $context->scope(Student::query())->where('is_active', true)->count();
        $today = $stats->totals($date, $date);
        $classes = $stats->byClass($date, $date);
        $month = $stats->byClass($monthStart, $date);
        $staffCounts = $context->scope(User::query())->role(Roles::STAFF)->with('roles')->get()
            ->flatMap(fn ($u) => $u->roles->pluck('name'))->countBy();
        $guardianCount = $context->isAllSchools() ? User::role('guardian')->count()
            : User::role('guardian')->whereHas('children', fn ($q) => $q->where('school_id', $context->id()))->count();

        return view('school.dashboard', [
            'date' => $date,
            'students' => $students,
            'today' => $today,
            'notTaken' => $classes->where('records', 0)->where('students', '>', 0)->count(),
            'classes' => $month,
            'todayByClass' => $classes->keyBy(fn ($row) => $row['classroom']->id),
            'pendingExcuses' => $context->scope(Excuse::query())->where('status', 'pending')->count(),
            'recentAbsences' => $context->scope(AttendanceRecord::query())->with('student', 'classroom.grade')->whereIn('status', ['absent', 'late'])->latest('updated_at')->limit(6)->get(),
            'alerts' => $user->notifications()->latest()->limit(5)->get(),
            'roleCounts' => $staffCounts->put('guardian', $guardianCount),
        ]);
    }
}
