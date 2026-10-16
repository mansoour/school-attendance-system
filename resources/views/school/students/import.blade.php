<x-admin-layout :title="__('Import students')">
<div class="page-head"><div><h1>{{ __('Import students from Excel') }}</h1><p>{{ __('Students are matched by national ID: existing students are updated and new ones are added.') }}</p></div>
<div class="page-actions"><a class="button secondary" href="{{ route('school.students.template') }}"><x-ui-icon name="download"/>{{ __('Download template') }}</a></div></div>
@if(session('import_errors'))<div class="errors" role="alert"><strong>{{ __('Rows that need attention') }}</strong><ul>@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid-2">
<form method="post" action="{{ route('school.students.import.store') }}" enctype="multipart/form-data" class="card">@csrf
<h2><x-ui-icon name="upload"/>{{ __('Upload file') }}</h2>
<label class="field">{{ __('Excel or CSV file') }}<input type="file" name="file" accept=".xlsx,.xls,.csv" required></label>
<label class="check-row" style="margin-top:12px"><input type="checkbox" name="create_classes" value="1" checked>{{ __('Create missing grades and classes automatically') }}</label>
<div class="form-actions"><button><x-ui-icon name="upload"/>{{ __('Import') }}</button></div>
</form>
<section class="card"><h2><x-ui-icon name="file-spreadsheet"/>{{ __('Accepted columns') }}</h2>
<p class="small">{{ __('The first row must contain column headers. Name and national ID are required.') }}</p>
<div class="table-wrap"><table><thead><tr><th>{{ __('Field') }}</th><th>{{ __('Accepted headers') }}</th></tr></thead><tbody>
@foreach($columns as $key=>$aliases)<tr><td><strong>{{ $aliases[0] }}</strong>@if(in_array($key,['name','national_id'])) *@endif</td><td class="small muted">{{ implode('، ',$aliases) }}</td></tr>@endforeach
</tbody></table></div></section>
</div>
</x-admin-layout>
