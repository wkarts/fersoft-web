@extends('default.layout')
@if(config('ui_theme.active', false))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-auth.css') }}?v={{ filemtime(public_path('css/ui-auth.css')) }}">
@endpush
@endif
@section('content')

<div class="card card-custom gutter-b @if(config('ui_theme.active', false)) fer-permission @endif">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="row">

				<div class="col-12 col-lg-3">
					<img style="" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRWh_wVJU6XKt45JWb7WryuW7QMN9JviMpzzOzry02x9eUqYEdr">
				</div>
				<div class="col-12 col-lg-9"><br><br>
					<h2 style="font-size: 30px;" class="text-danger">Você não possui permissão para acessar este local!</h2>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection	
