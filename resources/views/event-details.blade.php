@extends('layouts.app')

@section('content')

    <?php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    $registered = false;
    if (count($registrations) > 0) {
        $registered = true;
    }
    
    $registrations_approved = 0;
    $registrations_pending = 0;
    
    $currentTickets = DB::table('registration')
        ->selectRaw('count(*) as total')
        ->where('event_id', $event->id)
        ->where('status', '!=', 1)
        ->first();
    $offlineRegister = DB::table('registration')
        ->selectRaw('count(*) as total')
        ->where('event_id', $event->id)
        ->where('status', '!=', 1)
        ->where('offline_status', 1)
        ->first();
    $onlineRegister = DB::table('registration')
        ->selectRaw('count(*) as total')
        ->where('event_id', $event->id)
        ->where('status', '!=', 1)
        ->where('offline_status', 0)
        ->first();
    if ($currentTickets->total >= $event->seats) {
        $eligible_to_register = false;
    }
    ?>

    <div class="row justify-content-center mx-0 himti-header"
        style="background-color: @if (isset($event->theme_color_background)) {{ $event->theme_color_background }} @else #4159a7 @endif; color: @if (isset($event->theme_color_foreground)) {{ $event->theme_color_foreground }} @else #ffffff @endif;">
        <div class="col-12 col-md-6 col-xl-8 p-0">
            <img class="img" src="{{ $event->cover_image }}" alt="Card image cap" style="width: 100%">
        </div>
        <div class="col-12 col-md-6 col-xl-4 p-4 pb-sm-0">
            @if (strlen($event->kicker) > 0)
                <p class="h2 fw-normal">{{ $event->kicker }}</h2>
            @endif
            <h1 class="display-4 fw-bolder">{{ $event->name }} @if ($event->private)
                    <span class="badge rounded-pill bg-dark text-warning">Private</span>
                @endif
            </h1>
            @if ($event->team_members + $event->team_members_reserve == 0)
                <h4>Individual</h4>
            @else
                <h4>Team (Leader with {{ $event->team_members }} + {{ $event->team_members_reserve }} members)</h4>
            @endif
            <h4>Starts at <span id="eventDate">{{ Carbon::parse($event->date) }}</span></h4>
            <script>
                adjustDate('eventDate')
            </script>
            <h4>{{ $currentTickets->total >= $event->seats ? 'No more' : $event->seats - $currentTickets->total }}
                seats left</h4>
            @if ($event->price < 2)
                <h4>Free of charge</h4>
            @else
                <h4>Rp. {{ $event->price }}</h4>
            @endif
            <h4>You can only register @if ($event->slots < 2)
                    once.
                @else
                    up to {{ $event->slots }} times/slots.
                @endif
            </h4>
            <div class="btn-toolbar mb-4" role="toolbar">
                @if ($admin_or_committee)
                    <div class="btn-group mr-2" role="group">
                        <a href="/events/{{ $event->id }}/edit" class="btn btn-outline-light"
                            style="border-color: @if (isset($event->theme_color_foreground)) {{ $event->theme_color_foreground }} @else #4159a7 @endif; color: @if (isset($event->theme_color_foreground)) {{ $event->theme_color_foreground }} @else #ffffff @endif; border-color: @if (isset($event->theme_color_foreground)) {{ $event->theme_color_foreground }} @else #ffffff @endif;">Manage</a>
                    </div>
                @endif
            </div>
            @if ($registered)
                <?php
                foreach ($registrations as $registration) {
                    if ($registration->status > 1) {
                        $registrations_approved++;
                    }
                    if ($registration->status == 0) {
                        $registrations_pending++;
                    }
                }
                ?>
                @if ($registrations_pending > 0 && strlen($event->description_pending) > 0)
                    <div class="card mb-4">
                        <div class="card-header h4 bg-info text-dark">
                            <i class="bi bi-info-circle-fill"></i> Important Information for Registration/Payment
                        </div>
                        <div class="card-body text-dark">
                            @parsedown($event->description_pending)
                        </div>
                    </div>
                @endif
                @if ($registrations_approved > 0 && strlen($event->description_private) > 0)
                    <div class="card mb-4">
                        <div class="card-header h4 bg-info text-dark">
                            <i class="bi bi-info-circle-fill"></i> Important Information for Event/Attendance
                        </div>
                        <div class="card-body text-dark">
                            @parsedown($event->description_private)
                        </div>
                    </div>
                @endif
                @foreach ($registrations as $registration)
                    <?php
                    if ($registration->status > 1) {
                        $registrations_approved++;
                    }
                    if ($registration->status == 0) {
                        $registrations_pending++;
                    }
                    ?>
                    <div class="card mb-4">
                        <div class="card-header h4 bg-primary text-white">
                            <i class="bi bi-card-heading"></i> Ticket #{{ $registration->id }}
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
                                @if (strlen($registration->payment_code) > 0)
                                    <!--b>Payment Code:</b> {{ $registration->payment_code }}<br-->
                                @endif
                                @if (strlen($registration->check_in_timestamp) > 0)
                                    <b>Check in time (UTC): </b> {{ $registration->check_in_timestamp }}<br>
                                @endif
                                @if (strlen($registration->check_out_timestamp) > 0)
                                    <b>Check out time (UTC): </b> {{ $registration->check_out_timestamp }}<br>
                                @endif
                                @if (strlen($registration->remarks) > 0)
                                    <b>Remarks: </b> {{ $registration->remarks }}
                                @endif
                            </p>
                            <div class="alert alert-info" id="enableNotificationsBanner">
                                Please <b>enable notifications</b> so we can let you know when the Check In and Check Out
                                process has been successful.
                            </div>
                            <div class="btn-toolbar mb-2" role="toolbar" id="enableNotificationsButton"
                                style="display:none">
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" onClick="registerNotification()" class="btn btn-info">
                                        <i class="bi bi-bell"></i> Enable Notifications
                                    </button>
                                </div>
                            </div>
                            @if ($registration->status == 0 && strlen($registration->payment_code) > 0)
                                <div class="btn-toolbar" role="toolbar">
                                    <div class="btn-group mr-2" role="group">
                                        @if (strlen($event->payment_link) > 0)
                                            <a class="btn btn-primary text-white"
                                                href="{{ App\Http\Controllers\EventController::getPaymentLink($event, $registration) }}">
                                            @else
                                                <a class="btn btn-primary text-white"
                                                    href="/pay/{{ $registration->payment_code }}">
                                        @endif
                                        <i class="bi bi-credit-card"></i> Pay
                                        </a>
                                    </div>
                                </div>
                            @elseif ($registration->status == 0 && strlen($event->payment_link) > 0)
                                <div class="btn-toolbar" role="toolbar">
                                    <div class="btn-group mr-2" role="group">
                                        <a class="btn btn-primary text-white"
                                            href="{{ App\Http\Controllers\EventController::getPaymentLink($event, $registration) }}">
                                            <i class="bi bi-credit-card"></i> Submit Form
                                        </a>
                                    </div>
                                </div>
                            @else
                                @if ($event->event_offline_status == 1 && $registration->offline_status == 1)
                                    <div class="btn-toolbar" role="toolbar">
                                        @if ($event->attendance_is_exit)
                                            @if ($event->attendance_opened)
                                                {{-- <div class="btn-group mr-2" role="group">
                                                <button class="btn btn-success" onClick="ShowQR({{ $registration->id }})">
                                                    <i class="bi bi-qr-code"></i> Show QR
                                                </button>
                                            </div> --}}
                                                <div class="alert alert-info w-100" role="alert">
                                                    Please check QR via email that registered
                                                </div>
                                            @endif
                                        @elseif ($event->attendance_opened || $event->late)
                                            {{-- <div class="btn-group mr-2" role="group">
                                            <button class="btn btn-success" onClick="ShowQR({{ $registration->id }})">
                                                <i class="bi bi-qr-code"></i> Show QR
                                            </button>
                                        </div> --}}
                                            <div class="alert alert-info w-100" role="alert">
                                                Please check QR via email that registered
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="btn-toolbar" role="toolbar">
                                        @if ($event->attendance_is_exit)
                                            @if ($event->attendance_opened)
                                                <div class="btn-group mr-2" role="group">
                                                    <button class="btn btn-warning"
                                                        onClick="checkOutInit({{ $registration->id }})">
                                                        <i class="bi bi-box-arrow-left"></i> Check Out
                                                    </button>
                                                </div>
                                            @endif
                                        @elseif ($event->attendance_opened || $event->late)
                                            <div class="btn-group mr-2" role="group">
                                                <button class="btn btn-success"
                                                    onClick="checkIn({{ $registration->id }})">
                                                    <i class="bi bi-box-arrow-in-right"></i> Check In
                                                </button>
                                            </div>
                                        @endif
                                        {{-- <div class="btn-group mr-2" role="group">
                                        <button class="btn btn-success" onClick="checkIn({{ $registration->id }})">
                                            <i class="bi bi-box-arrow-in-right"></i> Check In
                                        </button>
                                    </div>
                                    <div class="btn-group mr-2" role="group">
                                        <button class="btn btn-warning" onClick="checkOutInit({{ $registration->id }})">
                                            <i class="bi bi-box-arrow-left"></i> Check Out
                                        </button>
                                    </div> --}}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
            @foreach ($rejected as $registration)
                <?php
                if ($registration->status != 1) {
                    $registrations_approved++;
                }
                ?>
                <div class="card mb-4">
                    <div class="card-header h4 bg-primary text-white">
                        <i class="bi bi-card-heading"></i> Ticket #{{ $registration->id }}
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
                            @if (strlen($registration->payment_code) > 0)
                                <!--b>Payment Code:</b> {{ $registration->payment_code }}<br-->
                            @endif
                            @if (strlen($registration->remarks) > 0)
                                <b>Remarks: </b> {{ $registration->remarks }}
                            @endif
                        </p>
                        @if ($registration->status == 0 && strlen($registration->payment_code) > 0)
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    @if (strlen($event->payment_link) > 0)
                                        <a class="btn btn-primary text-white"
                                            href="{{ App\Http\Controllers\EventController::getPaymentLink($event, $registration) }}">
                                        @else
                                            <a class="btn btn-primary text-white"
                                                href="/pay/{{ $registration->payment_code }}">
                                    @endif
                                    <i class="bi bi-credit-card"></i> Pay
                                    </a>
                                </div>
                            </div>
                        @elseif ($registration->status == 0 && strlen($event->payment_link) > 0)
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    <a class="btn btn-primary text-white"
                                        href="{{ App\Http\Controllers\EventController::getPaymentLink($event, $registration) }}">
                                        <i class="bi bi-credit-card"></i> Submit Form
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
            <div class="card mb-4">
                <div class="card-header h4 text-white @if ($eligible_to_register) bg-success @else bg-danger @endif">
                    <i class="bi bi-calendar-plus"></i> Register to this Event
                </div>
                <div class="card-body text-dark">
                    @if ($currentTickets->total >= $event->seats)
                        <p>Registrations are now closed due to full capacity.</p>
                    @elseif ($event->opened)
                        @if ($event->slots - $registrations_approved - $registrations_pending > 0)
                            @if (Auth::check())
                                @if (!$eligible_to_register)
                                    <h4><i class="bi bi-x-circle-fill text-danger"></i> Not Eligible</h4>
                                @else
                                    <h4><i class="bi bi-check-circle-fill text-success"></i> You are eligible to register
                                    </h4>
                                @endif
                                @if (count($event_permissions) > 0)
                                    <p>This event requires the following additional information:</p>
                                    @foreach ($event_permissions as $permission)
                                        <p>
                                            <i
                                                class="bi @if (isset($permission->satisfied)) bi-check-circle-fill text-success @elseif(!$permission->required) bi-dash-circle-fill text-secondary @else bi-x-circle-fill text-danger @endif"></i>
                                            <b>{{ $permission->name }}</b>
                                            @if ($permission->required)
                                                <b class="text-danger">*</b>
                                            @endif:
                                            <u>
                                                @if (isset($permission->current_value))
                                                    {{ $permission->current_value }}
                                                @else
                                                    <i>NULL</i>
                                                @endif
                                            </u>
                                            @if (strlen($permission->validation_description) > 0)
                                                ({{ $permission->validation_description }})
                                            @endif
                                        </p>
                                    @endforeach
                                    </ul>
                                @else
                                    <p>This event does not require any additional information (except your email address)
                                    </p>
                                @endif
                                @if ($eligible_to_register)
                                    <form action="/registerevent" method="POST">
                                        @csrf
                                        <input type="hidden" name="event_id" value="{{ $event->id }}">
                                        @if ($event->slots - $registrations_approved == 1)
                                            <input type="hidden" name="slots" value="1">
                                            <h1>Total: Rp <span id="eventregisterprice">{{ $event->price }}</span></h1>
                                            @if ($event->event_offline_status == 1)
                                                <h4 class="mt-3">Event Status</h4>

                                                @if (Session::has('OfflineInput') && Session::get('OfflineInput') == 'True')
                                                    <div class="alert alert-danger" role="alert">
                                                        You must select one of below status
                                                    </div>
                                                @endif
                                                @if ($event->seats - $event->offline_seats - $onlineRegister->total > 0)
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="OnlineOfflineStatus" id="OnlineOfflineStatus1" value="0"
                                                            onchange="OnChangePrice('{{ $event->price }}')" />
                                                        <label class="form-check-label" for="OnlineOfflineStatus1">
                                                            Online
                                                        </label>
                                                    </div>
                                                @endif
                                                @if ($event->offline_seats - $offlineRegister->total > 0)
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="radio"
                                                            name="OnlineOfflineStatus" id="OnlineOfflineStatus2" value="1"
                                                            onchange="OnChangePrice('{{ $event->offline_price }}')" />
                                                        <label class="form-check-label" for="OnlineOfflineStatus2">
                                                            Onsite
                                                        </label>
                                                    </div>
                                                @endif
                                            @endif
                                            @if ($event->price > 0)
                                                <p class="text text-danger fw-bold">You will be redirected to our
                                                    payment
                                                    form.</p>
                                            @elseif (strlen($event->payment_link) > 0)
                                                <p class="text text-danger fw-bold">You will be redirected to our
                                                    form.</p>
                                            @endif
                                            <p class="text mb-4 fw-bold">By registering to this event, you agree to our
                                                rules and regulations.</p>
                                            <button type="submit" class="btn btn-primary"
                                                onclick="this.form.submit();this.setAttribute('disabled','disabled');">Submit</button>
                                        @else
                                            <div class="form-group mb-4">
                                                <label for="slots">{{ __('Number of Tickets/Slots') }}<b
                                                        class="text-danger">*</b></label>
                                                <input name="slots" id="slots" type="number" class="form-control" min="1"
                                                    max="{{ $event->slots - $registrations_approved }}" required
                                                    onChange="validateRegistration()">
                                            </div>
                                            @if ($event->team_members > 0)
                                                <hr>
                                                <h4>Team Information</h4>
                                                <div class="alert alert-info">You will be registered as the
                                                    <b>leader</b> of
                                                    your team.
                                                </div>
                                                <div class="form-group mb-4">
                                                    <label for="team_name">Team Name<b class="text-danger">*</b></label>
                                                    <input type="text" class="form-control" name="team_name"
                                                        id="team_name" required>
                                                </div>
                                                <div class="form-group mb-4">
                                                    <label for="team_leader">Team Leader<b
                                                            class="text-danger">*</b></label>
                                                    <input type="text" class="form-control" id="team_leader" disabled
                                                        value="{{ Auth::user()->email }}">
                                                </div>
                                                @for ($i = 1; $i <= $event->team_members; $i++)
                                                    <div class="form-group mb-4">
                                                        <label for="team_member_{{ $i }}">Team Member
                                                            {{ $i }}'s registered Email Address<b
                                                                class="text-danger">*</b></label>
                                                        <input type="email" class="form-control"
                                                            name="team_member_{{ $i }}"
                                                            id="team_member_{{ $i }}" required
                                                            onChange="validateUser('team_member_{{ $i }}')">
                                                        <span role="alert" id="team_member_{{ $i }}_validation"
                                                            style="display: none"></span>
                                                    </div>
                                                @endfor
                                                @for ($i = 1; $i <= $event->team_members_reserve; $i++)
                                                    <div class="form-group mb-4">
                                                        <label for="team_member_reserve_{{ $i }}">Reserve
                                                            Team
                                                            Member {{ $i }}'s registered Email Address<b
                                                                class="text-danger">*</b></label>
                                                        <input type="email" class="form-control"
                                                            name="team_member_reserve_{{ $i }}"
                                                            id="team_member_reserve_{{ $i }}"
                                                            onChange="validateUser('team_member_reserve_{{ $i }}')">
                                                        <span role="alert"
                                                            id="team_member_reserve_{{ $i }}_validation"
                                                            style="display: none"></span>
                                                    </div>
                                                @endfor
                                            @endif
                                            <div id="submit-validation">
                                                <div class="alert alert-danger">All required fields should be entered.
                                                </div>
                                            </div>
                                        @endif
                                    </form>
                                @endif
                            @else
                                <p>Please <a href="{{ route('register') }}">sign up</a> or <a
                                        href="{{ route('login') }}">login</a> for a new <b>{{ env('APP_NAME') }}
                                        account</b> to register and join this event.</p>
                                <a href="/register" class="btn btn-primary">Sign Up</a>
                                <a href="/login" class="btn btn-outline-primary">Login</a>
                            @endif
                        @else
                            <p>Registrations are still open. However, you have reached the maximum limit of
                                <b>{{ $event->slots }} accepted tickets/slots</b> for this event.
                            </p>
                        @endif
                    @else
                        <p>Registrations are now closed.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if (strlen($event->description_public) > 0)
        <div class="container mt-4 text-break">
            <h1 class="display-4">Event Description</h1>
            @parsedown($event->description_public)
        </div>
    @endif

    <!-- Check In Modal -->
    <div class="modal fade" id="checkInModal" tabindex="-1" aria-labelledby="checkInModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkInModalLabel"><i class="bi bi-box-arrow-in-right"></i> Check In
                        (Entrance Attendance) Successful!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please visit the following link to continue</p>
                    <b id="checkInModalLink"></b>
                    <div id="checkInQRCanvas"></div>
                    <p>Timestamp: <span id="checkInTimestamp"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="checkInConfirm" type="button" class="btn btn-success" target="_blank">Open in New Tab</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Check Out Modal -->
    <div class="modal fade" id="checkOutModal" tabindex="-1" aria-labelledby="checkOutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="checkOutModalLabel"><i class="bi bi-box-arrow-left"></i> Check Out
                        (Exit Attendance)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="check_out_registration_id">
                    <div class="form-group mb-4">
                        <label for="token">Attendance Token<b class="text-danger">*</b></label>
                        <input type="number" class="form-control" name="token" id="token" required>
                        <span role="alert" id="token_validation"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" id="checkOutModalConfirm"
                        onClick="checkOut()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Check Out Success Modal -->
    <div class="modal fade" id="checkOutSuccessModal" tabindex="-1" aria-labelledby="checkOutSuccessModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="mx-auto text-center">
                        <h1 class="display-1"><i class="bi bi-check-circle-fill text-success"></i></h1>
                        <h4>Thank You for Participating!</h4>
                    </div>
                </div>
                <div class="modal-body">
                    <p>Timestamp: <span id="checkOutSuccessTimestamp"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/qrcode.min.js"></script>
    <script src="/js/countdown.min.js"></script>
    <script>
        var csrfToken = "{!! csrf_token() !!}";
        var isMemberValid = [];
        var isReserveMemberValid = [];
        var seats = {{ $event->seats }};
        var qrcode = new QRCode(document.getElementById("checkInQRCanvas"));
        var checkOutModal = new bootstrap.Modal(document.getElementById("checkOutModal"));

        function OnChangePrice(price) {
            var Price = document.getElementById("eventregisterprice");
            Price.innerHTML = price;
        }

        function validateUser(input) {
            var selected = document.getElementById(input).value;
            var xhr = new XMLHttpRequest();
            var params = JSON.stringify({
                email: selected,
                allowSelf: false,
                eventId: {{ $event->id }}
            });
            xhr.open("POST", "/getuserdetails");
            xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken);
            xhr.setRequestHeader("Content-type", "application/json; charset=utf-8");
            // xhr.setRequestHeader("Content-length", params.length);
            // xhr.setRequestHeader("Connection", "close");
            xhr.onload = function() {
                if (xhr.status != 200) {
                    setErrorMessage(input, 'Error ' + xhr.status + ': ' + xhr.statusText);
                } else {
                    // Check whether the output is JSON
                    try {
                        var json = JSON.parse(xhr.responseText);
                        // Check if the JSON data displays an error
                        if (json.error) throw json.error;
                        // Check whether the user is not eligible
                        if (!json.eligibleToRegister) {
                            var message = "This user is not eligible because:<ul>";
                            if (json.remainingSlots <= 0) message +=
                                "<li>The member has reached maximum limit of <b>{{ $event->slots }} accepted tickets/slots</b> for this event</li>";
                            var i;
                            for (i = 0; i < json.eventPermissions.length; i++) {
                                if (!json.eventPermissions[i].satisfied) {
                                    message += "<li>" + json.eventPermissions[i].validation_description +
                                        " (current: " + (json.eventPermissions[i].current_value ? json.eventPermissions[
                                            i].current_value : "<i>None</i>") + ")</li>";
                                }
                            }
                            message += "</ul>"
                        } else {
                            // Send to UI
                            setSuccessMessage(input, "User Found: " + json.name, selected);
                        }
                    } catch (e) {
                        setErrorMessage(input, 'Error: ' + e);
                    }
                }
            };
            xhr.send(params);
        }

        function setErrorMessage(input, message) {
            var match = input.match(/team_member_([1-9][0-9]*)/);
            if (match && match[1]) {
                isMemberValid[match[1] - 1] = false;
            }
            match = input.match(/team_member_reserve_([1-9][0-9]*)/)
            if (match && match[1]) {
                isReserveMemberValid[match[1] - 1] = false;
            }
            var element = document.getElementById(input + "_validation");
            element.style.display = "block";
            element.style.color = "#ff0000";
            element.innerHTML = "<strong>" + message + "</strong>";
            validateRegistration();
        }

        function setSuccessMessage(input, message, selected) {
            var match = input.match(/team_member_([1-9][0-9]*)/);
            if (match && match[1]) {
                isMemberValid[match[1] - 1] = selected;
            }
            match = input.match(/team_member_reserve_([1-9][0-9]*)/)
            if (match && match[1]) {
                isReserveMemberValid[match[1] - 1] = selected;
            }
            var element = document.getElementById(input + "_validation");
            element.style.display = "block";
            element.style.color = "#249ef2";
            element.innerHTML = "<strong>" + message + "</strong>";
            validateRegistration();
        }

        function validateRegistration() {
            var emails = isMemberValid.concat(isReserveMemberValid);
            var initPrice = Number("{{ $event->price }}");
            var i, invalidMembers = 0;
            for (i = 0; i < emails.length; i++) {
                if (emails[i] == null) emails.splice(i, 1);
                if (isMemberValid[i] == false) invalidMembers++;
            }
            var emailSet = new Set(emails);
            if (invalidMembers > 0) {
                document.getElementById("submit-validation").innerHTML =
                    '<div class="alert alert-danger">All member details should be added.</div>';
            } else if (emails.length > emailSet.size) {
                document.getElementById("submit-validation").innerHTML =
                    '<div class="alert alert-danger">Error: No duplicate emails allowed.</div>';
            } else {
                var price;
                try {
                    price = initPrice * (document.getElementsByName("slots")[0].value);
                } catch (e) {
                    price = initPrice;
                }
                document.getElementById("submit-validation").innerHTML = `<h1>Total: Rp ` + price +
                    `</h1><p class="text text-danger fw-bold">You will be redirected to our payment form.</p><p class="text mb-4 fw-bold">By registering to this event, you agree to our rules and regulations.</p><button type="submit" class="btn btn-primary" onclick="this.form.submit();this.setAttribute('disabled','disabled');">Submit</button>`;
            }
        }

        function checkIn(registrationId) {
            var xhr = new XMLHttpRequest();
            var formData = new FormData();
            formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);
            xhr.onreadystatechange = function() {
                if (xhr.readyState != 4) return;
                if (xhr.status == 401) {
                    // Use Check Out modal instead
                    checkOutInit(registrationId);
                } else if (xhr.status == 200) {
                    // Set up links
                    var response = JSON.parse(this.responseText);
                    if ("Notification" in window && Notification.permission == "granted") var notification =
                        new Notification("Check in successful for {{ $event->name }}.");
                    else if ("webkitNotifications" in window && window.webkitNotifications.checkPermission() == 0)
                        window.webkitNotifications.createNotification(null,
                            "Check in successful for {{ $event->name }}.", "HIMTI Registration").show();
                    handleSuccessResponse(response);
                }
            };
            xhr.open("POST", "/attendance/" + registrationId, true);
            xhr.send(formData);
        }

        function checkOutInit(registrationId) {
            document.querySelector('input[name="check_out_registration_id"]').value = registrationId;
            checkOutModal.show();
        }

        function checkOut() {
            var formData = new FormData();
            formData.append("_token", document.querySelector('meta[name="csrf-token"]').content);
            formData.append("token", document.querySelector('input[name="token"]').value);
            var registrationId = document.querySelector('input[name="check_out_registration_id"]').value
            var trials = 1;

            // Disable modal confirm button
            document.querySelector('input[name="token"]').disabled = true;
            document.getElementById("checkOutModalConfirm").disabled = true;

            function checkOutRequest() {
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState != 4) return;
                    if (xhr.status == 200) {
                        // Set up links
                        var response = JSON.parse(this.responseText);
                        checkOutModal.hide();
                        if ("Notification" in window && Notification.permission == "granted") var notification =
                            new Notification(
                                "Check out successful for {{ $event->name }}. Thanks for participating!");
                        else if ("webkitNotifications" in window && window.webkitNotifications.checkPermission() == 0)
                            window.webkitNotifications.createNotification(null,
                                "Check out successful for {{ $event->name }}. Thanks for participating!",
                                "HIMTI Registration").show();

                        handleSuccessResponse(response);
                    } else if (xhr.readyState == 4 && (xhr.status == 503 || xhr.status == 419)) {
                        // If this is Laravel's "Page Expired" error, try requesting a new CSRF token
                        if (xhr.status == 419) refreshToken();

                        // Delay Mode
                        var delay = Math.max(15000, Math.floor(Math.random() * 1000 * seats / 3 / trials));

                        var element = document.getElementById("token_validation");
                        element.style.display = "block";
                        element.style.color = "#249ef2";
                        element.innerHTML =
                            "<strong>The server is currently busy. Trying again in <span id='checkOutCountdown'></span></strong>";

                        var nextCheck = new Date();
                        nextCheck.setMilliseconds(nextCheck.getMilliseconds() + delay);

                        var timerId = countdown(nextCheck, function(ts) {
                            document.getElementById('checkOutCountdown').innerHTML = ts.toHTML("strong");
                        }, countdown.MINUTES | countdown.SECONDS);

                        trials++;
                        setTimeout(function() {
                            window.clearInterval(timerId);
                            checkOutRequest();
                        }, delay);
                    } else {
                        setErrorMessage("token", xhr.responseText);
                    }
                };
                xhr.open("POST", "/attendance/" + registrationId, true);
                xhr.send(formData);
            }

            checkOutRequest();
        }

        function handleSuccessResponse(response) {
            if (response.attendanceType == "entrance") {
                document.getElementById("checkInModalLink").textContent = response.url;
                document.getElementById("checkInTimestamp").textContent = response.timestamp;
                document.getElementById("checkInConfirm").setAttribute('href', response.url);
                qrcode.clear();
                qrcode.makeCode(response.url);

                var modal = new bootstrap.Modal(document.getElementById("checkInModal"));
                modal.show();
            } else {
                document.getElementById("checkOutSuccessTimestamp").textContent = response.timestamp;

                var modal = new bootstrap.Modal(document.getElementById("checkOutSuccessModal"));
                modal.show();
            }
        }

        function refreshToken() {
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
                        try {
                            document.querySelector("[name='_token']").value = json.token;
                        } catch (f) {
                            var i = 0;
                        }
                    } catch (e) {
                        console.error('Error: ' + e);
                    }
                }
            };
            xhr.send();
        }
        setInterval(refreshToken, 15 * 60 * 1000);

        function registerNotification() {
            if ("Notification" in window && Notification.permission != "granted") {
                Notification.requestPermission().then(function(permission) {
                    if (permission == "granted") {
                        document.getElementById("enableNotificationsBanner").textContent =
                            "You will be notified when the Check In and Check Out process has been successful.";
                        document.getElementById("enableNotificationsButton").style.display = "none";
                        var notification = new Notification(
                            "You will be notified when the Check In and Check Out process has been successful.");
                    }
                });
            } else if ("webkitNotifications" in window && window.webkitNotifications.checkPermission() != 0) {
                window.webkitNotifications.requestPermission(function() {
                    if (window.webkitNotifications.checkPermission() == "granted") {
                        document.getElementById("enableNotificationsBanner").textContent =
                            "You will be notified when the Check In and Check Out process has been successful.";
                        document.getElementById("enableNotificationsButton").style.display = "none";
                        window.webkitNotifications.createNotification(null,
                            "You will be notified when the Check In and Check Out process has been successful.",
                            "HIMTI Registration").show();
                    }
                })
            } else {
                alert("This browser does not support desktop notifications.");
            }
        }

        if ("Notification" in window) {
            if (Notification.permission == "granted") document.getElementById("enableNotificationsBanner").textContent =
                "You will be notified when the Check In and Check Out process has been successful.";
            else document.getElementById("enableNotificationsButton").style.display = "inline-block";
        } else if ("webkitNotifications" in window) {
            if (window.webkitNotifications.checkPermission() == 0) document.getElementById("enableNotificationsBanner")
                .textContent = "You will be notified when the Check In and Check Out process has been successful.";
            else document.getElementById("enableNotificationsButton").style.display = "inline-block";
        } else {
            document.getElementById("enableNotificationsBanner").style.display = "none";
        }
    </script>

@endsection
