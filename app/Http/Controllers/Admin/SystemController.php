<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\EmailLog;
use App\Models\TranslationRevision;
use App\Models\User;
use App\Operations\HealthReport;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemController extends Controller
{
    public function index()
    {
        return view('admin.index', ['members' => Gate::allows('users.manage') ? User::count() : null, 'support' => Gate::allows('support.access') ? ContactMessage::whereNull('resolved_at')->count() : null]);
    }

    public function support(Request $request)
    {
        $records = ContactMessage::query()->when($request->query('status', 'open') === 'open', fn ($q) => $q->whereNull('resolved_at'))->when($request->query('status') === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))->when($request->query('topic'), fn ($q, $topic) => $q->where('topic', $topic))->when($request->query('q'), fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')->orWhere('message', 'like', '%'.$term.'%')))->latest()->paginate(20)->withQueryString();

        return view('admin.support', ['records' => $records, 'open' => ContactMessage::whereNull('resolved_at')->count(), 'resolved' => ContactMessage::whereNotNull('resolved_at')->count()]);
    }

    public function resolve(Request $request, ContactMessage $message)
    {
        $request->validate(['resolved' => 'required|boolean']);
        $message->update(['resolved_at' => $request->boolean('resolved') ? now() : null]);
        app(AuditLogger::class)->record('support.updated', $message);

        return back()->with('status', __('Saved.'));
    }

    public function audit()
    {
        return view('admin.log', ['title' => 'Audit log', 'records' => AuditLog::latest('id')->paginate(30)]);
    }

    public function emails()
    {
        return view('admin.log', ['title' => 'Email history', 'records' => EmailLog::latest('id')->paginate(30)]);
    }

    public function health()
    {
        return view('admin.health', ['checks' => app(HealthReport::class)->checks()]);
    }

    public function publishTranslation(TranslationRevision $revision)
    {
        TranslationRevision::create(['locale' => $revision->locale, 'messages' => $revision->messages, 'published_at' => now(), 'created_by' => auth()->id()]);
        app(AuditLogger::class)->record('translations.published', $revision);

        return back()->with('status', __('Published.'));
    }

    public function exportTranslation(TranslationRevision $revision)
    {
        return response()->json($revision->messages)->header('Content-Disposition', 'attachment; filename="catalog-'.$revision->locale.'.json"');
    }
}
