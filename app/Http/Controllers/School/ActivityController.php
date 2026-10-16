<?php

namespace App\Http\Controllers\School;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/** School-level view of the audit log: actions performed by the school's own accounts. */
class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('actor')
            ->when(! $this->context()->isAllSchools(), fn ($q) => $q->whereIn('actor_id', User::where('school_id', $this->context()->id() ?? 0)->select('id')))
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->when($request->query('date'), fn ($q, $d) => $q->whereDate('created_at', $this->date($d)))
            ->latest('id')->paginate(40)->withQueryString();

        return view('school.activity', ['logs' => $logs]);
    }
}
