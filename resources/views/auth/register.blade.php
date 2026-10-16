@php($mode=app(\App\Settings\SettingsService::class)->get('guardians.match_mode'))
<x-guest-layout>
    <p class="auth-lead">{{ __('Guardian registration: enter your details and your child’s details exactly as registered at the school.') }}</p>
    <form method="POST" action="{{ route('register') }}" class="register-form">
        @csrf<x-recaptcha action="register"/>

        <fieldset>
            <legend>{{ __('Guardian details') }}</legend>
            <div>
                <x-input-label for="name" :value="__('Full name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" dir="ltr" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="phone" :value="__('Mobile number')" />
                <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required autocomplete="tel" dir="ltr" placeholder="05xxxxxxxx" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="relation" :value="__('Relation to the student')" />
                <select id="relation" name="relation" required class="block mt-1 w-full">
                    @foreach(\App\Models\Student::RELATIONS as $relation)<option value="{{ $relation }}" @selected(old('relation')===$relation)>{{ __('school.relation.'.$relation) }}</option>@endforeach
                </select>
                <x-input-error :messages="$errors->get('relation')" class="mt-2" />
            </div>
        </fieldset>

        <fieldset class="mt-4">
            <legend>{{ __('Student details') }}</legend>
            <div>
                <x-input-label for="student_national_id" :value="__('Student national ID')" />
                <x-text-input id="student_national_id" class="block mt-1 w-full" type="text" inputmode="numeric" name="student_national_id" :value="old('student_national_id')" required dir="ltr" />
                <x-input-error :messages="$errors->get('student_national_id')" class="mt-2" />
            </div>
            @if(str_contains($mode,'name'))
            <div class="mt-4">
                <x-input-label for="student_name" :value="__('Student full name')" />
                <x-text-input id="student_name" class="block mt-1 w-full" type="text" name="student_name" :value="old('student_name')" required />
                <x-input-error :messages="$errors->get('student_name')" class="mt-2" />
            </div>
            @endif
            @if(str_contains($mode,'phone'))
            <div class="mt-4">
                <x-input-label for="student_phone" :value="__('Student mobile number')" />
                <x-text-input id="student_phone" class="block mt-1 w-full" type="tel" name="student_phone" :value="old('student_phone')" required dir="ltr" />
                <x-input-error :messages="$errors->get('student_phone')" class="mt-2" />
            </div>
            @endif
        </fieldset>

        <fieldset class="mt-4">
            <legend>{{ __('Password') }}</legend>
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </fieldset>

        <div class="mt-4"><label><input type="checkbox" name="terms" value="1" required> {{ __('I accept the terms of service.') }} <a href="{{ route('pages.show','terms') }}">{{ __('Terms') }}</a></label><label><input type="checkbox" name="marketing" value="1"> {{ __('Email me occasional news and offers.') }}</label></div>
        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">{{ __('Already registered?') }}</a>
            <x-primary-button class="ms-4">{{ __('Register') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
