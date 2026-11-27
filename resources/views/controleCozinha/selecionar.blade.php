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
<div class="ui-admin ui-commerce ui-commerce-controleCozinha">

<div class="ui-admin-panel ui-commerce-panel gutter-b">


	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="row">
			<h2 class="center-align">Selecione a tela</h2>

			<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">

				<div class="progresso" style="display: none">
					<div class="spinner spinner-track spinner-primary spinner-lg mr-15"></div>
				</div>
			</div>
		</div>

		<div class="row" id="itens">
			<div class="col-sm-4 col-lg-4 col-md-12">
				<a href="/controleCozinha/controle"><button style="height: 90px; width: 100%; margin-top: 5px;" class="btn btn-info">
					Todos
				</button></a>
			</div>
			@foreach($telas as $t)
			<div class="col-sm-4 col-lg-4 col-md-12">
				<a href="/controleCozinha/controle/{{$t->id}}"><button style="height: 90px; width: 100%; margin-top: 5px;" class="btn btn-info">
					{{$t->nome}}
				</button></a>
			</div>
			@endforeach
		</div>
	</div>
</div>

</div>
@endsection	