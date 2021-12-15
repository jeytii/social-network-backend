@component('mail::message')
# Hi, {{ $name }}!

You have requested to reset your forgotten password. Please click the button below to proceed.

@component('mail::button', compact('url'))
Reset my password
@endcomponent

You only have <b>{{ config('validation.expiration.password_reset') }} minutes</b> to reset your password.

Regards,<br>
{{ config('app.name') }}
@endcomponent
