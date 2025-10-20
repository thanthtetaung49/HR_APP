@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="p-20">
        @include('management-ranks.ajax.show')
    </div>
@endsection
