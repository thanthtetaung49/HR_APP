@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="p-20">
        @include('cause.ajax.show')
    </div>
@endsection
