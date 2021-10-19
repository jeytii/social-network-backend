@component('mail::message')
# Hi, {{ $name }}!

You just recently registered a {{ config('app.name') }} account and chose to verify your account through email.

<h1>Your verification code is: {{ $code }}</h1>

You only have <b>{{ config('validation.expiration.verification') }} minutes</b> to verify your account. Otherwise, request for another verification code.

Regards,<br>
{{ config('app.name') }}
@endcomponent