@extends('layouts.app')

@section('content')
<form action="/events/{{ $event->id }}" method="PUT" class="row justify-content-center mx-0" style="background-color: @if(isset($event->theme_color_background)) {{$event->theme_color_background}} @else #4159a7 @endif; color: @if(isset($event->theme_color_foreground)) {{$event->theme_color_foreground}} @else #ffffff @endif;">
    <h1>Participants</h1>
    <div class="col-12 col-xl-8 p-4 pb-sm-0">
        @foreach ($registrations as $registration)
        <div class="card mb-4">
            <div class="card-header h4 bg-primary text-white">
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
                        <button type="button" class="btn btn-primary" onClick="viewDetails({{ $registration->id }})">
                            <i class="bi bi-person-circle"></i> View Participant Details
                        </button>
                    </div>
                    <div class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#checkOutModal">
                            <i class="bi bi-person-check"></i> Change Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="col-12 col-xl-4 p-4 pb-sm-0">
    </div>
</form>
@endsection