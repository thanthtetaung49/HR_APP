@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="p-20">
        @include('man-power-reports.ajax.show')
    </div>
@endsection
