<label class="field">{{ __('School name') }}<input name="name" value="{{ old('name',$school->name) }}" required maxlength="200"></label>
@if($full ?? false)<label class="field">{{ __('School code') }}<input name="code" value="{{ old('code',$school->code) }}" maxlength="40" dir="ltr"></label>@endif
<label class="field">{{ __('City') }}<input name="city" value="{{ old('city',$school->city) }}" maxlength="100"></label>
<label class="field">{{ __('Phone') }}<input name="phone" value="{{ old('phone',$school->phone) }}" maxlength="40" dir="ltr"></label>
<label class="field">{{ __('Email') }}<input type="email" name="email" value="{{ old('email',$school->email) }}" dir="ltr"></label>
<label class="field">{{ __('School day starts at') }}<input type="time" name="day_starts_at" value="{{ old('day_starts_at',substr((string) $school->day_starts_at,0,5)) }}" required></label>
<label class="field">{{ __('Late after (minutes)') }}<input type="number" name="late_after_minutes" value="{{ old('late_after_minutes',$school->late_after_minutes) }}" min="0" max="240" required></label>
@if($full ?? false)<label class="check-row"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$school->is_active))>{{ __('Active') }}</label>@endif
