@extends('layouts.app')

@section('content')

    <?php
    // Check which events are being attended
    $registeredEvents = DB::table('registration')
        ->select('registration.id', 'registration.event_id', 'registration.remarks', 'registration.status', 'events.*')
        ->join('events', 'events.id', 'registration.event_id')
        ->where('ticket_id', Auth::id())
        ->where('PassEvent', '0')
        ->get();
    $registeredEventIDs = [];
    foreach ($registeredEvents as $event) {
        array_push($registeredEventIDs, $event->event_id);
    }
    $availableEvents = Cache::get('availableEvents', []);
    if (count($availableEvents) == 0) {
        $availableEvents = DB::table('events')
            ->selectRaw('events.*, event_groups.name as group_name')
            ->leftJoin('event_groups', 'events.event_group_id', 'event_groups.id')
            ->where('private', false)
            ->where('opened', true)
            ->orderBy('event_group_id', 'asc')
            ->get();
        Cache::put('availableEvents', $availableEvents, 300);
    }
    ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            @component('components.status-badge')
            @endcomponent
            <div class="col-md-10 col-lg-8">
                <h1 class="mb-4">Your Events</h1>
                @if (!Auth::check())
                    <div class="card mb-4">
                        <div class="card-header h4 bg-danger text-white">{{ __('You haven\'t logged in yet.') }}</div>
                        <div class="card-body">
                            <b>{{ __('First time here?') }}</b> {{ __('Please ') }}<a
                                href="{{ route('register') }}">sign up</a>{{ __(' or ') }} <a
                                href="{{ route('login') }}">login</a>{{ __(' to a ') . config('app.name', 'Laravel') . __(' account to join these events.') }}
                        </div>
                    </div>
                @elseif(count($registeredEvents) == 0)
                    <div class="card mb-4">
                        <div class="card-header h4 bg-danger text-white">
                            {{ __('You currently haven\'t registered to any new events yet.') }}</div>
                        <div class="card-body">
                            <b>{{ __('First time here?') }}</b> {{ __('Select one of the events below to register.') }}
                        </div>
                    </div>
                @else
                    @foreach ($registeredEvents as $event)
                        @component('components.event-card', ['event' => $event, 'type' => 'registeredEvents'])
                            ;
                        @endcomponent
                    @endforeach
                @endif
                <hr>
                <h1 class="mb-4">Available Events</h1>
                <?php
                $current_group = null;
                ?>
                @foreach ($availableEvents as $event)
                    @if ($event->event_group_id != $current_group)
                        <h2 class="mb-4">{{ isset($event->group_name) ? $event->group_name : 'Unknown' }}</h2>
                        <?php $current_group = $event->event_group_id; ?>
                    @endif
                    @component('components.event-card', ['event' => $event, 'type' => 'availableEvents'])
                        ;
                    @endcomponent
                @endforeach
            </div>
        </div>
    </div>
@endsection
