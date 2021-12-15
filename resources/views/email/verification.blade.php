@component('mail::message')
# Hi, {{ $name }}!

You just recently registered a {{ config('app.name') }} account and chose to verify your account through email.

<h1>Click the button below then enter your verification code: {{ $code }}</h1>

@component('mail::button', compact('url'))
Verify my account
@endcomponent

You only have <b>{{ config('validation.expiration.verification') }} minutes</b> to verify your account.

Regards,<br>
{{ config('app.name') }}
@endcomponent