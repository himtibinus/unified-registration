@extends('layouts.app')

@section('content')
<form action="/events/{{ $event->id }}" method="POST" class="row flex-row-reverse justify-content-center mx-0" style="background-color: @if(isset($event->theme_color_background)) {{$event->theme_color_background}} @else #4159a7 @endif; color: @if(isset($event->theme_color_foreground)) {{$event->theme_color_foreground}} @else #ffffff @endif;">
    @csrf
    <input name="_method" type="hidden" value="PUT">
    <div class="col-12 col-xl-4 p-4 pr-sm-0">
        @if($role->admin == true)
            <h1 class="display-4 mb-4">Settings</h1>
        @else
            <h1 class="display-4 mb-4">Summary</h1>
        @endif
        <div class="card mb-4">
            <div class="card-header h4 text-white bg-primary">
                <i class="bi bi-info-circle"></i> Summary
            </div>
            <div class="card-body text-dark">
                <div class="row">
                    <div class="col-12 col-sm-4 col-xl-12">
                        <p class="h4 font-700">Seats</p>
                        <p class="display-4">{{ $event->current_seats }}/{{ $event->seats }}</p>
                    </div>
                    <div class="col-12 col-sm-4 col-xl-12">
                        <p class="h4 font-700">Attendance</p>
                        <p class="display-4">{{ $event->attending }}/{{ $event->attended }}</p>
                    </div>
                    <div class="col-12 col-sm-4 col-xl-12">
                        <p class="h4 font-700">Event Token</p>
                        <p class="display-4">{{ $event->totp_key }}</p>
                    </div>
                </div>
            </div>
        </div>
        @if($role->admin)
            <div class="card mb-4">
                <div class="card-header h4 text-white bg-primary">
                    <i class="bi bi-check-square"></i> Disable accidental delete?
                </div>
                <div class="card-body text-dark">
                  <input class="form-check-input" type="checkbox" name="flag-force-change" id="flag-force-change">
                  <label class="form-check-label fw-bold" for="flag-force-change">
                      Force-save the inputs, even when containing blanks
                  </label>
                </div>
            </div>
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
                    <input name="action-update-date" id="action-update-location" type="datetime-date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}\+[0-9]{2}:[0-9]{2}" value="{{ date("c", strtotime($event->date)) }}" class="form-control" @if(!$role->admin) disabled @else required @endif>
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
                    <label for="action-registration-auto_accept"><b>Automatically accept new participants?</b> (Current: {{($event->auto_accept) ? 'Yes' : 'No'}})</label><br>
                    <select name="action-registration-auto_accept" id="action-registration-auto_accept" @if(!$role->admin) disabled @endif>
                        <option value="-1">Unchanged</option>
                        <option value="enabled">Yes</option>
                        <option value="disabled">No</option>
                    </select>
                </div>
                <div class="form-group mb-4">
                    <label for="action-update-seats">{{ __('Maximum Available Seats') }}</label>
                    <input name="action-update-seats" id="action-update-seats" type="number" value="{{ $event->seats }}" min="1" class="form-control" @if(!$role->admin) disabled @else required @endif>
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
                <div class="form-group mb-4">
                    <label for="action-update-payment_link">{{ __('Payment URL') }}</label>
                    <input name="action-update-payment_link" id="action-update-payment_link" type="url" value="{{ $event->payment_link }}" min="0" class="form-control" @if(!$role->admin) disabled @endif>
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
    <div class="col-12 col-xl-8 p-4 pb-sm-0">
        <h1 class="display-4 mb-4">Participants</h1>
        @foreach ($registrations as $registration)
        <div class="card mb-4">
            <div class="card-header h4 @if($registration->status > 4) bg-primary @elseif($registration->status > 3) bg-info @elseif($registration->status > 1) bg-success @elseif($registration->status == 1) bg-danger @else bg-secondary @endif text-white">
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
                    @if(strlen($registration->check_in_timestamp) > 0)
                        <b>Check in time (UTC): </b> {{ $registration->check_in_timestamp }}<br>
                    @endif
                    @if(strlen($registration->check_out_timestamp) > 0)
                        <b>Check out time (UTC): </b> {{ $registration->check_out_timestamp }}<br>
                    @endif
                    @if(strlen($registration->remarks) > 0)
                        <b>Remarks: </b> {{ $registration->remarks }}
                    @endif
                </p>
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-primary" onClick="requestParticipantDetails('{{ $registration->email }}')">
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
</form>

<!-- Participant Details Modal -->
<div class="modal fade" id="participantDetailsModal" tabindex="-1" aria-labelledby="participantDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="participantDetailsModalLabel"><i class="bi bi-person-circle"></i> Participant Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul id="participantDetailsList"></ul>
            </div>
        </div>
    </div>
</div>

<script>
    var csrfToken = "{!! csrf_token() !!}";

    function requestParticipantDetails(email){
        var xhr = new XMLHttpRequest();
        var params = JSON.stringify({ email: email, allowSelf: true, eventId:{{ $event->id }} });
        xhr.open("POST", "/getuserdetails");
        xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken);
        xhr.setRequestHeader("Content-type", "application/json; charset=utf-8");
        // xhr.setRequestHeader("Content-length", params.length);
        // xhr.setRequestHeader("Connection", "close");
        xhr.onload = function() {
            if (xhr.status != 200) {
                console.error('Error ' + xhr.status + ': ' + xhr.statusText);
            } else {
                // Check whether the output is JSON
                try {
                    var json = JSON.parse(xhr.responseText);
                    // Check if the JSON data displays an error
                    if (json.error) throw json.error;
                    // Send to UI
                    showParticipantDetails(email, json);
                } catch (e) {
                    console.error('Error: ' + e);
                }
            }
        };
        xhr.send(params);
    }

    function showParticipantDetails(email, json){
        // Set up links
        list = document.getElementById("participantDetailsList");
        list.innerHTML = "";

        function createList(key, value){
            var element = document.createElement("li");
            var keyElement = document.createElement("b");
            keyElement.textContent = key + ":";
            element.appendChild(keyElement);
            var valueElement = document.createTextNode(" " + (value || ""));
            element.appendChild(valueElement);
            if (!value){
                var nullElement = document.createElement("i");
                nullElement.textContent = "NULL";
                element.appendChild(nullElement);
            }
            return element;
        }

        list.appendChild(createList("Name", json.name));
        list.appendChild(createList("Email", email));

        var i;
        for (i = 0; i < json.eventPermissions.length; i++){
            list.appendChild(createList(json.eventPermissions[i].name, json.eventPermissions[i].current_value));
        }

        console.log(json);
        var modal = new bootstrap.Modal(document.getElementById("participantDetailsModal"));
        modal.show();
    }

    function refreshToken(){
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "/refreshtoken")
        xhr.onload = function() {
            if (xhr.status != 200) {
                console.error("Unable to refresh the CSRF token");
            } else {
                // Check whether the output is JSON
                try {
                    var json = JSON.parse(xhr.responseText);
                    // Check if the JSON data displays an error
                    if (json.error) throw json.error;
                    // Refresh token
                    csrfToken = json.token;
                    document.querySelector("[name='_token']").value = json.token;
                } catch (e) {
                    console.error('Error: ' + e);
                }
            }
        };
        xhr.send();
    }

    setInterval(refreshToken, 15 * 60 * 1000);
</script>
@endsection
