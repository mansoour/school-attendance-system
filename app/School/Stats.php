<?php

namespace App\School;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance aggregates shared by the dashboard and reports.
 */
class Stats
{
    public function __construct(private SchoolContext $context) {}

    /** Status counts for a date range, optionally narrowed to a grade or class. */
    public function totals(CarbonInterface $from, CarbonInterface $to, ?int $gradeId = null, ?int $classroomId = null): array
    {
        $rows = $this->records($from, $to, $gradeId, $classroomId)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $totals = collect(AttendanceRecord::STATUSES)->mapWithKeys(fn ($s) => [$s => (int) ($rows[$s] ?? 0)])->all();
        $totals['records'] = array_sum($totals);
        $totals['absence_rate'] = $totals['records'] ? round(($totals['absent'] + $totals['excused']) * 100 / $totals['records'], 1) : 0;
        $totals['late_rate'] = $totals['records'] ? round($totals['late'] * 100 / $totals['records'], 1) : 0;

        return $totals;
    }

    /** One row per class with student and status counts. */
    public function byClass(CarbonInterface $from, CarbonInterface $to, ?int $gradeId = null, ?int $classroomId = null): Collection
    {
        $classes = $this->context->scope(Classroom::query())->with('grade')->withCount(['students' => fn ($q) => $q->where('is_active', true)])
            ->when($gradeId, fn ($q) => $q->where('grade_id', $gradeId))->when($classroomId, fn ($q) => $q->whereKey($classroomId))->get()
            ->sortBy(fn ($c) => sprintf('%05d-%s', $c->grade?->sort_order ?? 0, $c->name));
        $counts = $this->records($from, $to, $gradeId, $classroomId)->select('attendance_records.classroom_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('attendance_records.classroom_id', 'status')->get()->groupBy('classroom_id');

        return $classes->values()->map(function (Classroom $classroom) use ($counts) {
            $row = ['classroom' => $classroom, 'students' => $classroom->students_count];
            foreach (AttendanceRecord::STATUSES as $status) {
                $row[$status] = (int) ($counts->get($classroom->id)?->firstWhere('status', $status)?->total ?? 0);
            }
            $row['records'] = $row['present'] + $row['absent'] + $row['late'] + $row['excused'];
            $row['absence_rate'] = $row['records'] ? round(($row['absent'] + $row['excused']) * 100 / $row['records'], 1) : 0;

            return $row;
        });
    }

    /** Students with the most absences in the range. */
    public function topAbsent(CarbonInterface $from, CarbonInterface $to, int $limit = 10, ?int $gradeId = null, ?int $classroomId = null): Collection
    {
        $ids = $this->records($from, $to, $gradeId, $classroomId)->whereIn('status', ['absent', 'excused'])
            ->select('student_id', DB::raw('count(*) as total'), DB::raw("sum(case when status = 'absent' then 1 else 0 end) as unexcused"))
            ->groupBy('student_id')->orderByDesc('total')->limit($limit)->get();
        $students = Student::with('classroom.grade')->whereIn('id', $ids->pluck('student_id'))->get()->keyBy('id');

        return $ids->map(fn ($row) => ['student' => $students->get($row->student_id), 'total' => (int) $row->total, 'unexcused' => (int) $row->unexcused])->filter(fn ($r) => $r['student']);
    }

    /** Daily absence counts for a trend chart. */
    public function daily(CarbonInterface $from, CarbonInterface $to, ?int $gradeId = null, ?int $classroomId = null): Collection
    {
        return $this->records($from, $to, $gradeId, $classroomId)
            ->select('date', DB::raw("sum(case when status in ('absent','excused') then 1 else 0 end) as absent"), DB::raw("sum(case when status = 'late' then 1 else 0 end) as late"), DB::raw('count(*) as total'))
            ->groupBy('date')->orderBy('date')->get();
    }

    private function records(CarbonInterface $from, CarbonInterface $to, ?int $gradeId, ?int $classroomId)
    {
        return $this->context->scope(AttendanceRecord::query())
            ->whereBetween('attendance_records.date', [$from->toDateString(), $to->toDateString()])
            ->when($classroomId, fn ($q) => $q->where('attendance_records.classroom_id', $classroomId))
            ->when($gradeId && ! $classroomId, fn ($q) => $q->whereIn('attendance_records.classroom_id', Classroom::where('grade_id', $gradeId)->select('id')));
    }
}
