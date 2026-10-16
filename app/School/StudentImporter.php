<?php

namespace App\School;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Student;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Imports students from an Excel or CSV sheet. Rows are matched by national ID:
 * existing students in the school are updated, new ones are created.
 */
class StudentImporter
{
    /** Column keys with the Arabic and English headers accepted for each. */
    public const COLUMNS = [
        'name' => ['اسم الطالبة', 'اسم الطالب', 'الاسم', 'name', 'student name'],
        'national_id' => ['رقم الهوية', 'السجل المدني', 'الهوية', 'national id', 'national_id', 'id number'],
        'grade' => ['الصف', 'grade'],
        'classroom' => ['الفصل', 'الشعبة', 'class', 'section'],
        'phone' => ['جوال الطالبة', 'جوال الطالب', 'الجوال', 'phone', 'student phone'],
        'email' => ['بريد الطالبة', 'بريد الطالب', 'البريد الإلكتروني', 'email', 'student email'],
        'guardian_name' => ['اسم ولي الأمر', 'guardian name'],
        'guardian_phone' => ['جوال ولي الأمر', 'guardian phone'],
        'guardian_email' => ['بريد ولي الأمر', 'guardian email'],
    ];

    /** @return array{created: int, updated: int, errors: list<string>} */
    public function import(string $path, int $schoolId, bool $createClasses): array
    {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), array_shift($rows) ?? []);
        $map = [];
        foreach (self::COLUMNS as $key => $aliases) {
            foreach ($header as $index => $title) {
                if (in_array($title, array_map('mb_strtolower', $aliases), true)) {
                    $map[$key] = $index;
                }
            }
        }
        $result = ['created' => 0, 'updated' => 0, 'errors' => []];
        if (! isset($map['name'], $map['national_id'])) {
            $result['errors'][] = __('The sheet must include the student name and national ID columns.');

            return $result;
        }

        DB::transaction(function () use ($rows, $map, $schoolId, $createClasses, &$result) {
            foreach ($rows as $i => $row) {
                $line = $i + 2;
                $data = [];
                foreach ($map as $key => $index) {
                    $value = trim((string) ($row[$index] ?? ''));
                    $data[$key] = $value === '' ? null : $value;
                }
                if (! array_filter($data)) {
                    continue;
                }
                $validator = Validator::make($data, [
                    'name' => 'required|string|max:200', 'national_id' => 'required|string|max:20',
                    'email' => 'nullable|email|max:200', 'guardian_email' => 'nullable|email|max:200',
                    'phone' => 'nullable|string|max:40', 'guardian_phone' => 'nullable|string|max:40', 'guardian_name' => 'nullable|string|max:200',
                ]);
                if ($validator->fails()) {
                    $result['errors'][] = __('Row :row: :error', ['row' => $line, 'error' => $validator->errors()->first()]);

                    continue;
                }
                $student = Student::where('national_id', $data['national_id'])->first();
                if ($student && $student->school_id !== $schoolId) {
                    $result['errors'][] = __('Row :row: this national ID belongs to a student in another school.', ['row' => $line]);

                    continue;
                }
                $classroom = $this->classroom($schoolId, $data['grade'] ?? null, $data['classroom'] ?? null, $createClasses);
                if (($data['grade'] ?? null) && ! $classroom) {
                    $result['errors'][] = __('Row :row: grade or class not found.', ['row' => $line]);
                }
                $attributes = collect($data)->only(['name', 'email', 'phone', 'guardian_name', 'guardian_phone', 'guardian_email'])->filter()->all();
                if ($classroom) {
                    $attributes['classroom_id'] = $classroom->id;
                }
                if ($student) {
                    $student->update($attributes);
                    $result['updated']++;
                } else {
                    Student::create($attributes + ['school_id' => $schoolId, 'national_id' => $data['national_id']]);
                    $result['created']++;
                }
            }
        });
        app(AuditLogger::class)->record('students.imported', null, ['school_id' => $schoolId, 'created' => $result['created'], 'updated' => $result['updated'], 'errors' => count($result['errors'])]);

        return $result;
    }

    private function classroom(int $schoolId, ?string $gradeName, ?string $className, bool $create): ?Classroom
    {
        if (! $gradeName) {
            return null;
        }
        $grade = Grade::where('school_id', $schoolId)->where('name', $gradeName)->first();
        if (! $grade && $create) {
            $grade = Grade::create(['school_id' => $schoolId, 'name' => $gradeName, 'stage' => 'primary', 'sort_order' => Grade::where('school_id', $schoolId)->count() + 1]);
        }
        if (! $grade) {
            return null;
        }
        $className = $className ?: '1';
        $classroom = $grade->classrooms()->where('name', $className)->first();
        if (! $classroom && $create) {
            $classroom = Classroom::create(['school_id' => $schoolId, 'grade_id' => $grade->id, 'name' => $className]);
        }

        return $classroom;
    }

    public function template(): Spreadsheet
    {
        $sheet = new Spreadsheet;
        $active = $sheet->getActiveSheet();
        $active->setRightToLeft(true);
        $active->fromArray([array_map(fn ($aliases) => $aliases[0], self::COLUMNS), ['سارة محمد العتيبي', '1000000001', 'رابع ابتدائي', 'أ', '0500000001', '', 'محمد العتيبي', '0500000002', '']]);
        foreach (range('A', 'I') as $column) {
            $active->getColumnDimension($column)->setAutoSize(true);
        }

        return $sheet;
    }
}
