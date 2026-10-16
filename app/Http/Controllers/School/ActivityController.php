<?php

namespace App\Http\Controllers\School;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** School-level view of the audit log: actions performed by the school's own accounts. */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $scope = fn ($q) => $q->when(! $this->context()->isAllSchools(), fn ($q) => $q->whereIn('actor_id', User::where('school_id', $this->context()->id() ?? 0)->select('id')));
        $logs = AuditLog::with('actor')->tap($scope)
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->when($request->query('date'), fn ($q, $d) => $q->whereDate('created_at', $this->date($d)))
            ->latest('id')->paginate(40)->withQueryString();
        $actions = AuditLog::query()->tap($scope)->distinct()->orderBy('action')->pluck('action')
            ->mapWithKeys(fn ($action) => [$action => self::label($action)])->sort();

        return view('school.activity', ['logs' => $logs, 'actions' => $actions]);
    }

    /** "student.created" → "إضافة: طالبة". Unknown actions fall back to a readable form. */
    public static function label(string $action): string
    {
        $entity = Str::beforeLast($action, '.');
        $verb = Str::afterLast($action, '.');
        $entityKey = 'school.activity.entities.'.$entity;
        $verbKey = 'school.activity.verbs.'.$verb;
        $entityLabel = __($entityKey);
        $verbLabel = __($verbKey);
        $entityLabel = is_string($entityLabel) && $entityLabel !== $entityKey ? $entityLabel : str_replace(['_', '-'], ' ', $entity);
        $verbLabel = is_string($verbLabel) && $verbLabel !== $verbKey ? $verbLabel : str_replace('_', ' ', $verb);

        return $verbLabel.': '.$entityLabel;
    }

    /** Readable record type, e.g. App\Models\Student → "طالبة". */
    public static function target(?string $type): string
    {
        if (! $type) {
            return '';
        }
        $key = 'school.activity.entities.'.Str::snake(class_basename($type));
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : class_basename($type);
    }
}
