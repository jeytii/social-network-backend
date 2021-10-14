@component('mail::message')
# Hi, {{ $name }}!

You just recently registered a {{ config('app.name') }} account and chose to verify your account through email.

<h1>Your verification code is: {{ $code }}</h1>

The verification code will expire <b>{{ config('validation.expiration.verification') }} minutes</b> after you received this email, so you have to submit the code as soon as possible. Otherwise, log in then click "request a new verification code".

Regards,<br>
{{ config('app.name') }}
@endcomponent