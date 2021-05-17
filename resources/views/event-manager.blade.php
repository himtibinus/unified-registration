@extends('layouts.app')

@section('content')
<form action="/events/{{ $event->id }}" method="PUT" class="row justify-content-center mx-0" style="background-color: @if(isset($event->theme_color_background)) {{$event->theme_color_background}} @else #4159a7 @endif; color: @if(isset($event->theme_color_foreground)) {{$event->theme_color_foreground}} @else #ffffff @endif;">
    <input name="_method" type="hidden" value="PUT">
    <div class="col-12 col-xl-8 p-4 pb-sm-0">
        <h1 class="display-4 mb-4">Participants</h1>
        @foreach ($registrations as $registration)
        <div class="card mb-4">
            <div class="card-header h4 @if($registration->status > 3) bg-info @elseif($registration->status > 1) bg-success @elseif($registration->status == 1) bg-danger @else bg-secondary @endif text-white">
                #{{ $registration->id }}: {{ $registration->name }}
            </div>
            <div class="card-body text-dark">
                <p class="card-title">
                    <b>Status:</b>
                    @switch($registration->status)
                        @case(0)
                            Pending
                            @break
                        @case(1)
                            Rejected
                            @break
                        @case(2)
                            Accepted
                            @break
                        @case(3)
                            Cancelled
                            @break
                        @case(4)
                            Attending
                            @break
                        @case(5)
                            Attended
                            @break
                        @default
                            Unknown ({{ $registration->status }})
                    @endswitch
                    <br>
                    @if(strlen($registration->payment_code) > 0)
                        <b>Email:</b> {{ $registration->payment_code }}<br>
                    @endif
                    @if(strlen($registration->payment_code) > 0)
                        <b>Payment Code:</b> {{ $registration->payment_code }}<br>
                    @endif
                    @if(strlen($registration->remarks) > 0)
                        <b>Remarks: </b> {{ $registration->remarks }}
                    @endif
                </p>
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-primary" onClick="requestParticipantDetails({{ $registration->id }})">
                            <i class="bi bi-person-circle"></i> View Participant Details
                        </button>
                    </div>
                    <div class="btn-group mr-2" role="group">
                        <select name="status-{{$registration->id}}" id="status-{{$registration->id}}">
                            <option value="-1">Override Status to...</option>
                            <option value="0">0: Not yet accepted</option>
                            <option value="1">1: Rejected</option>
                            <option value="2">2: Approved</option>
                            <option value="3">3: Cancelled</option>
                            <option value="4">4: Attending</option>
                            <option value="5">5: Attended</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @if($role->admin == true)
            <div class="card mb-4">
                <div class="card-header h4 text-white bg-primary">
                    <i class="bi bi-check-circle"></i> Confirmation
                </div>
                <div class="card-body text-dark">
                    <p>Make sure that you have saved all changes</p>
                    <input type="submit" value="Save" class="btn btn-primary">
                </div>
            </div>
        @endif
    </div>
    <div class="col-12 col-xl-4 p-4 pr-sm-0">
        @if($role->admin == true)
            <h1 class="display-4 mb-4">Settings</h1>
        @else
            <h1 class="display-4 mb-4">Summary</h1>
        @endif
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-calendar-date"></i> Event Details
            </div>
            <div class="card-body text-dark">
                <div class="form-group mb-4">
                    <label for="action-update-kicker">{{ __('Kicker') }}</label>
                    <input name="action-update-kicker" id="action-update-kicker" type="text" value="{{ $event->kicker }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-name">{{ __('Event Name') }}<b class="text-danger">*</b></label>
                    <input name="action-update-name" id="action-update-name" type="text" value="{{ $event->name }}" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-date">{{ __('Event Date') }}<b class="text-danger">*</b></label>
                    <input name="action-update-date" id="action-update-location" type="datetime-date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}+[0-9]{2}:[0-9]{2}" value="{{ date("c", strtotime($event->date)) }}" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-location">{{ __('Event Location') }}</label>
                    <input name="action-update-location" id="action-update-location" type="text" value="{{ $event->location }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-price">{{ __('Event Price (Rp)') }}<b class="text-danger">*</b></label>
                    <input name="action-update-price" id="action-update-price" type="number" min="0" value="{{ $event->price }}" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-cover_image">{{ __('Cover Image (URL)') }}</label>
                    <input name="action-update-cover_image" id="action-update-cover_image" type="text" value="{{ $event->cover_image }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-theme_color_foreground">{{ __('Foreground Color') }}</label>
                    <input name="action-update-theme_color_foreground" id="action-update-theme_color_foreground" type="text" value="{{ $event->theme_color_foreground }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-theme_color_background">{{ __('Background Color') }}</label>
                    <input name="action-update-theme_color_background" id="action-update-theme_color_background" type="text" value="{{ $event->theme_color_background }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-globe"></i> Public Description
            </div>
            <div class="card-body text-dark">
                <p>This description will be publicly visible towards all users, registered or not. <b>Markdown is supported and enabled by default.</b></p>
                <div class="form-group mb-4">
                    <textarea name="action-update-description_public" id="action-update-description_public" type="text" rows="4" class="form-control" @if(!$role->admin) disabled @endif>{{ $event->description_public }}</textarea>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-clock-history"></i> Description for Pending Tickets
            </div>
            <div class="card-body text-dark">
                <p>This description will be visible to users who has been recently registered, such as placing payment information. <b>Markdown is supported and enabled by default.</b></p>
                <div class="form-group mb-4">
                    <textarea name="action-update-description_pending" id="action-update-description_pending" type="text" rows="4" class="form-control" @if(!$role->admin) disabled @endif>{{ $event->description_pending }}</textarea>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-file-lock"></i> Private Description
            </div>
            <div class="card-body text-dark">
                <p>This description will be visible to users who has been accepted to the event. <b>Markdown is supported and enabled by default.</b></p>
                <div class="form-group mb-4">
                    <textarea name="action-update-description_private" id="action-update-description_private" type="text" rows="4" class="form-control" @if(!$role->admin) disabled @endif>{{ $event->description_private }}</textarea>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-sliders"></i> Registration Settings
            </div>
            <div class="card-body text-dark">
                <div class="form-group mb-4">
                    <label for="action-registration-status"><b>Open for New Registrations?</b> (Current: {{($event->opened) ? 'Yes' : 'No'}})</label><br>
                    <select name="action-registration-status" id="action-registration-status" @if(!$role->admin) disabled @endif>
                        <option value="-1">Unchanged</option>
                        <option value="enabled">Yes</option>
                        <option value="disabled">No</option>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label for="action-registration-private"><b>Event Visibility</b> (Current: {{($event->private) ? 'Private' : 'Public'}})</label><br>
                    <select name="action-registration-private" id="action-registration-private" @if(!$role->admin) disabled @endif>
                        <option value="-1">Unchanged</option>
                        <option value="private">Private</option>
                        <option value="public">Public</option>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-seats">{{ __('Maximum Available Seats') }}</label>
                    <input name="action-update-seats" id="action-update-seats" type="number" value="{{ $event->slots }}" min="1" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-slots">{{ __('Maximum Tickets per User') }}</label>
                    <input name="action-update-slots" id="action-update-slots" type="number" value="{{ $event->slots }}" min="1" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-team_members">{{ __('Required Team Members') }}</label>
                    <input name="action-update-team_members" id="action-update-team_members" type="number" value="{{ $event->team_members }}" min="0" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-team_members_reserve">{{ __('Reserve Team Members') }}</label>
                    <input name="action-update-team_members_reserve" id="action-update-team_members_reserve" type="number" value="{{ $event->team_members_reserve }}" min="0" class="form-control" @if(!$role->admin) disabled @else required @endif>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-sliders"></i> Attendance Settings
            </div>
            <div class="card-body text-dark">
                <div class="form-group mb-4">
                    <label for="action-attendance-status"><b>Open for Attendance?</b> (Current: {{($event->attendance_opened) ? 'Yes' : 'No'}})</label><br>
                    <select name="action-attendance-status" id="action-attendance-status" @if(!$role->admin) disabled @endif>
                        <option value="-1">Unchanged</option>
                        <option value="enabled">Yes</option>
                        <option value="disabled">No</option>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label for="action-attendance-type"><b>Attendance Type</b> (Current: {{($event->attendance_is_exit) ? 'Exit' : 'Entrance'}})</label><br>
                    <select name="action-attendance-type" id="action-attendance-type" @if(!$role->admin) disabled @endif>
                        <option value="-1">Unchanged</option>
                        <option value="entrance">Entrance</option>
                        <option value="exit">Exit</option>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-url_link"><b>{{ __('URL for Online Participants') }}</b><br>Participants will be redirected to this link after check in.</label>
                    <input name="action-update-url_link" id="action-update-url_link" type="text" value="{{ $event->url_link }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-totp_key"><b>{{ __('Event Check Out Token') }}</b><br>Online participants will be propted to enter the following token to check out.</label>
                    <input name="action-update-totp_key" id="action-update-totp_key" type="text" value="{{ $event->totp_key }}" class="form-control" @if(!$role->admin) disabled @endif>
                </div>
            </div>
        </div>
        @if($role->admin == true)
            <div class="card mb-4">
                <div class="card-header h4 text-white bg-primary">
                    <i class="bi bi-check-circle"></i> Confirmation
                </div>
                <div class="card-body text-dark">
                    <p>Make sure that you have saved all changes</p>
                    <input type="submit" value="Save" class="btn btn-primary">
                </div>
            </div>
        @endif
    </div>
</form>
@endsection
