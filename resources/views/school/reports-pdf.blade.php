<html dir="{{ $rtl ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><style>
body{font-family:dejavusans;font-size:10pt;color:#1d2b27}
h1{font-size:16pt;color:#17606b;margin:0 0 4px}h2{font-size:12pt;color:#17606b;margin:16px 0 6px}
.meta{color:#5d6b66;font-size:9pt}
table{width:100%;border-collapse:collapse;margin-top:4px}th{background:#e6f2ee;font-weight:bold}th,td{border:1px solid #cfdcd7;padding:5px;text-align:{{ $rtl ? 'right' : 'left' }}}
.num{text-align:center}.cards td{text-align:center;border:1px solid #cfdcd7;padding:8px}.cards strong{font-size:14pt;display:block}
</style></head><body>
<h1>{{ __('Attendance report') }}</h1>
<p class="meta">{{ $school?->name ?? __('All schools') }} · {{ \App\Support\Dates::both($from) }} — {{ \App\Support\Dates::both($to) }} · {{ __('Generated at') }} {{ \App\Support\Dates::both(now()) }} {{ now()->format('H:i') }}</p>
<table class="cards"><tr>
<td><strong>{{ $totals['records'] }}</strong>{{ __('Attendance records') }}</td>
<td><strong>{{ $totals['absent'] }}</strong>{{ __('Unexcused absences') }}</td>
<td><strong>{{ $totals['excused'] }}</strong>{{ __('Excused absences') }}</td>
<td><strong>{{ $totals['late'] }}</strong>{{ __('Late arrivals') }}</td>
<td><strong>{{ $totals['absence_rate'] }}%</strong>{{ __('Absence rate') }}</td>
</tr></table>
<h2>{{ __('By class') }}</h2>
<table><thead><tr><th>{{ __('Grade') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Students') }}</th><th class="num">{{ __('school.status.absent') }}</th><th class="num">{{ __('school.status.excused') }}</th><th class="num">{{ __('school.status.late') }}</th><th class="num">{{ __('Absence rate') }}</th></tr></thead><tbody>
@foreach($byClass as $row)<tr><td>{{ $row['classroom']->grade?->name }}</td><td>{{ $row['classroom']->name }}</td><td class="num">{{ $row['students'] }}</td><td class="num">{{ $row['absent'] }}</td><td class="num">{{ $row['excused'] }}</td><td class="num">{{ $row['late'] }}</td><td class="num">{{ $row['absence_rate'] }}%</td></tr>@endforeach
</tbody></table>
<h2>{{ __('Most absent students') }}</h2>
<table><thead><tr><th>{{ __('Student') }}</th><th>{{ __('National ID') }}</th><th>{{ __('Class') }}</th><th class="num">{{ __('Absences') }}</th><th class="num">{{ __('Without excuse') }}</th></tr></thead><tbody>
@forelse($topAbsent as $row)<tr><td>{{ $row['student']->name }}</td><td>{{ $row['student']->national_id }}</td><td>{{ $row['student']->classroom?->label() }}</td><td class="num">{{ $row['total'] }}</td><td class="num">{{ $row['unexcused'] }}</td></tr>@empty<tr><td colspan="5">{{ __('No absences in this period.') }}</td></tr>@endforelse
</tbody></table>
<h2>{{ __('Details') }}</h2>
<table><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Status') }}</th><th>{{ __('Note') }}</th></tr></thead><tbody>
@forelse($records as $record)<tr><td>{{ \App\Support\Dates::both($record->date) }}</td><td>{{ $record->student?->name }}</td><td>{{ $record->classroom?->label() }}</td><td>{{ __('school.status.'.$record->status) }}@if($record->late_minutes) ({{ $record->late_minutes }} {{ __('min') }})@endif</td><td>{{ $record->note }}</td></tr>@empty<tr><td colspan="5">{{ __('No data for this period.') }}</td></tr>@endforelse
</tbody></table>
</body></html>
