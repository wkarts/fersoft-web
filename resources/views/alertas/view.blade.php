@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-extras.css') }}?v={{ filemtime(public_path('css/ui-extras.css')) }}">
@endpush

@section('content')
<div class="fer-extra fer-extra--support fer-extra--alertas card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			
			<h3>{{$item->titulo}}</h3>
			<h5>Prioridade: {{ strtoupper($item->prioridade) }}</h5>

			<br>

			{!! $item->texto !!}
		</div>
	</div>
</div>

@endsection	
