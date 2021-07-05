@extends('layouts.app')

@section('content')
<div class="container pt-3">
    <h1>External Clients List</h1>

    <ul>
    @foreach($clients as $client)
        <li><b>{{ $client->name }}</b> ({{ $client->id }}) @if($client->enabled) <b class="text-danger">ENABLED</b> @endif</li>
    @endforeach
    </li>
</div>
@endsection
