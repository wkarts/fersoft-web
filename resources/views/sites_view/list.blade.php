@extends('default.layout')

@section('content')
    <h1>{{ $title ?? '' }}</h1>
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
        <iframe src="{{ $siteUrl }}" frameborder="0" style="width: 100%; height: 100%;"></iframe>
    </div>
@endsection
