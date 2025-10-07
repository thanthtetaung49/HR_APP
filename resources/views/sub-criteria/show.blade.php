@extends('layouts.app')

@section('content')
    <style>
        .mt {
            margin-top: -4px;
        }
    </style>

    <div class="p-20">
        @include('sub-criteria.ajax.show')
    </div>
@endsection
