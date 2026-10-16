@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-categoriasEcommerce">
<div class="ui-admin-panel ui-commerce-panel gutter-b">
	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/categoriaEcommerce/newSub/{{$categoria->id}}" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova SubCategoria
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">SubCategorias: <strong class="text-danger">{{$categoria->nome}}</strong></h4>
			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				@foreach($categoria->subs as $c)

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">{{$c->nome}}
							</h3>
							<div class="ui-commerce-tools">

								<a href="/categoriaEcommerce/editSubs/{{$c->id}}" class="btn btn-icon btn-circle btn-sm btn-light-primary mr-1"><i class="la la-pencil"></i></a>
								<a href="/categoriaEcommerce/deleteSub/{{$c->id}}" class="btn btn-icon btn-circle btn-sm btn-light-danger mr-1"><i class="la la-trash"></i></a>

							</div>
						</div>

					</div>

				</div>

				@endforeach

			</div>
		</div>
	</div>
</div>

</div>
@endsection