@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-admin-modules.css') }}?v={{ filemtime(public_path('css/ui-admin-modules.css')) }}">
@endpush

@section('content')
<div class="document-fiscal-page admin-page admin-page--sites-view">
    <h1>{{ $title ?? '' }}</h1>
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
        <iframe src="{{ $siteUrl }}" frameborder="0" style="width: 100%; height: 100%;"></iframe>
    </div>
</div>
@endsection
