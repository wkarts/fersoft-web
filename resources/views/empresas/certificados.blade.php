@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-admin-empresas">
<div class="ui-admin-panel gutter-b">
	<div class="ui-admin-panel-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Certificados da Empresa</h4>
			

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					@foreach($certificados as $c)
					<a class="btn btn-light-info" href="/empresas/download_file/{{$c}}">{{$c}}</a>
					@endforeach
					
				</div>
			</div>
		</div>
	</div>
</div>

</div>
@endsection	
