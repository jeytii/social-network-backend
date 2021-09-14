@component('mail::message')
# Hi, {{ $name }}!

You have requested to reset your forgotten password. Please click the button below to proceed.

@component('mail::button', compact('url'))
Reset my password
@endcomponent

You only have <b>60 minutes</b> to reset your password. Otherwise, do another request.

Regards,<br>
{{ config('app.name') }}
@endcomponent
