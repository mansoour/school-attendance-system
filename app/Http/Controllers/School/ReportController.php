<?php

namespace App\Http\Controllers\School;

use App\Models\AttendanceRecord;
use App\Models\Classroom;
use App\School\Stats;
use App\Services\Audit\AuditLogger;
use App\Support\Credits;
use App\Support\Dates;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request, Stats $stats)
    {
        return view('school.reports', $this->report($request, $stats) + ['grades' => $this->gradeOptions(), 'classes' => $this->classOptions()]);
    }

    public function export(Request $request, Stats $stats, string $format)
    {
        $report = $this->report($request, $stats);
        $name = 'attendance-report-'.$report['from']->toDateString().'-'.$report['to']->toDateString();
        app(AuditLogger::class)->record('reports.exported', null, ['format' => $format, 'from' => $report['from']->toDateString(), 'to' => $report['to']->toDateString()]);

        return $format === 'pdf' ? $this->pdf($report, $name) : $this->excel($report, $name);
    }

    private function report(Request $request, Stats $stats): array
    {
        $to = $this->date($request->query('to'));
        $from = $this->date($request->query('from'), $to->copy()->startOfMonth());
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $gradeId = $request->integer('grade') ?: null;
        $classroomId = $request->integer('classroom') ?: null;
        $records = $this->context()->scope(AttendanceRecord::query())->with('student', 'classroom.grade')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->where('status', '!=', 'present')
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->when($gradeId && ! $classroomId, fn ($q) => $q->whereIn('classroom_id', Classroom::where('grade_id', $gradeId)->select('id')))
            ->orderBy('date')->limit(5000)->get();

        return [
            'from' => $from, 'to' => $to, 'gradeId' => $gradeId, 'classroomId' => $classroomId,
            'school' => $this->context()->school(),
            'totals' => $stats->totals($from, $to, $gradeId, $classroomId),
            'byClass' => $stats->byClass($from, $to, $gradeId, $classroomId),
            'topAbsent' => $stats->topAbsent($from, $to, 15, $gradeId, $classroomId),
            'daily' => $stats->daily($from, $to, $gradeId, $classroomId),
            'records' => $records,
        ];
    }

    private function excel(array $report, string $name)
    {
        $book = new Spreadsheet;
        $sheets = [
            __('Summary') => [
                [__('Item'), __('Value')],
                [__('School'), $report['school']?->name ?? __('All schools')],
                [__('From'), Dates::both($report['from'])],
                [__('To'), Dates::both($report['to'])],
                [__('Records'), $report['totals']['records']],
                [__('school.status.present'), $report['totals']['present']],
                [__('school.status.absent'), $report['totals']['absent']],
                [__('school.status.late'), $report['totals']['late']],
                [__('school.status.excused'), $report['totals']['excused']],
                [__('Absence rate'), $report['totals']['absence_rate'].'%'],
            ],
            __('By class') => array_merge([[__('Grade'), __('Class'), __('Students'), __('school.status.absent'), __('school.status.excused'), __('school.status.late'), __('Absence rate')]],
                $report['byClass']->map(fn ($r) => [$r['classroom']->grade?->name, $r['classroom']->name, $r['students'], $r['absent'], $r['excused'], $r['late'], $r['absence_rate'].'%'])->all()),
            __('Most absent') => array_merge([[__('Student'), __('National ID'), __('Class'), __('Absences'), __('Without excuse')]],
                $report['topAbsent']->map(fn ($r) => [$r['student']->name, $r['student']->national_id, $r['student']->classroom?->label(), $r['total'], $r['unexcused']])->values()->all()),
            __('Details') => array_merge([[__('Hijri date'), __('Gregorian date'), __('Student'), __('National ID'), __('Class'), __('Status'), __('Late minutes'), __('Note')]],
                $report['records']->map(fn ($r) => [Dates::hijri($r->date), $r->date->toDateString(), $r->student?->name, $r->student?->national_id, $r->classroom?->label(), __('school.status.'.$r->status), $r->late_minutes, $r->note])->all()),
        ];
        $index = 0;
        foreach ($sheets as $title => $rows) {
            $sheet = $index === 0 ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle(mb_substr($title, 0, 31));
            $sheet->setRightToLeft(app()->getLocale() === 'ar');
            $sheet->fromArray(array_values($rows), null, 'A1', true);
            $last = $sheet->getHighestColumn();
            $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6F2EE');
            foreach (range('A', $last) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $index++;
        }
        $writer = new Xlsx($book);

        return response()->streamDownload(fn () => $writer->save('php://output'), $name.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function pdf(array $report, string $name)
    {
        $directory = storage_path('app/mpdf');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $rtl = app()->getLocale() === 'ar';
        $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $directory, 'default_font' => 'dejavusans', 'autoScriptToLang' => true, 'autoLangToFont' => true, 'margin_top' => 12, 'margin_bottom' => 12]);
        $mpdf->SetDirectionality($rtl ? 'rtl' : 'ltr');
        $mpdf->SetTitle(__('Attendance report'));
        $mpdf->SetCreator(Credits::line());
        $mpdf->SetAuthor(Credits::DEVELOPER);
        $mpdf->WriteHTML(view('school.reports-pdf', $report + ['rtl' => $rtl])->render());

        return response($mpdf->Output($name.'.pdf', 'S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"']);
    }
}
