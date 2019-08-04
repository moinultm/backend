@component('mail::message')
# Change password

Click on the button to change password

@component('mail::button', ['url' => 'http://localhost:4200/response-password-reset?token='.$token])
Reset password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
