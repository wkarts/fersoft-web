@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
@endpush
@section('content')
<div class="document-fiscal-page document-fiscal-page--tributos  d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->
				<form>

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Tributações por estado</h3>
						</div>

					</div>

					<div class="row">
						@foreach($estados as $e)
						<div class="col-2">
							<a style="margin-top: 5px;" class="btn @if($e['ja_cadastrado']) btn-info @else btn-light-info @endif btn-block" @if($e['ja_cadastrado']) href="/percentualuf/edit/{{$e['uf']}}" @else href="/percentualuf/novo/{{$e['uf']}}" @endif>{{$e['uf']}}</a>
						</div>
						@endforeach
					</div>
					<br>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection