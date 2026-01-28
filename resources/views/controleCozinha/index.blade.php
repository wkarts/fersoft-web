@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="row">
			<h2 class="center-align">Controle de Pedidos <strong> - {{$tela}}</strong> <a href="/controleCozinha/selecionar" class="btn btn-danger">voltar</a></h2>
			<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">

				<div class="progresso" style="display: none">
					<div class="spinner spinner-track spinner-primary spinner-lg mr-15"></div>
				</div>
			</div>
		</div>

		<input type="hidden" value="{{$id}}" id="tela" name="">

		<div class="row" id="itens">

		</div>
	</div>
</div>

@endsection	