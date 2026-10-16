@php
$schoolName=$student->school?->name ?? '';
$status=$status ?? 'absent';
$message=__('guardian_message.'.$status,['student'=>$student->name,'date'=>\App\Support\Dates::both($date),'school'=>$schoolName]);
$wa=\App\Models\Student::whatsappNumber($student->guardian_phone);
$canLog=auth()->user()->can('communication.access');
@endphp
<div class="contact-buttons" x-data="{ log(channel){ @if($canLog) fetch('{{ route('school.communication.log') }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({student_id:{{ $student->id }},channel,absence_date:'{{ $date->toDateString() }}'}),keepalive:true}) @endif } }">
<a class="wa" @if($wa) href="https://wa.me/{{ $wa }}?text={{ rawurlencode($message) }}" target="_blank" rel="noopener" @click="log('whatsapp')" @else aria-disabled="true" @endif><x-ui-icon name="message-circle"/>{{ __('WhatsApp') }}</a>
<a class="call" @if($student->guardian_phone) href="tel:{{ preg_replace('/[^\d+]/','',$student->guardian_phone) }}" @click="log('call')" @else aria-disabled="true" @endif><x-ui-icon name="phone"/>{{ __('Call') }}</a>
<a class="mail" @if($student->guardian_email) href="mailto:{{ $student->guardian_email }}?subject={{ rawurlencode(__('guardian_message.subject',['student'=>$student->name])) }}&body={{ rawurlencode($message) }}" @click="log('email')" @else aria-disabled="true" @endif><x-ui-icon name="mail"/>{{ __('Email') }}</a>
</div>
