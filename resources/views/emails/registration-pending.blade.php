@component('mail::message')
Hello **{{ $data->user->name }}**,

@if(strlen($data->event->kicker) > 0)
Thank you for registering to **{{ $data->event->kicker }} {{ $data->event->name }}**.
@else
Thank you for registering to **{{ $data->event->name }}**.
@endif

We are currently verifying your registration, and we will notify you if your request has been approved.

@if(strlen($data->event->description_pending) > 0)
At the meantime, please note the following information:

@component('mail::panel')
{{ $data->event->description_pending }}
@endcomponent
@endif

Sincerely,
@if(strlen($data->event->kicker) > 0)
{{ $data->event->kicker }} event committee.
@else
{{ $data->event->name }} event committee.
@endif

---

### Notice

This is an autogenerated email message from **{{ env('APP_NAME') }}** system. Please **do not** reply to this message.

If you encounter any problems, please contact the respective event committees.
@endcomponent
