@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<h4>Atualizar Valor</h4>

			<h5>Produto: <strong class="text-danger">{{$produto->produto->nome}}</strong></h5>

			<form method="post" action="/listaDePrecos/salvarPreco">
				<input type="hidden" name="id" value="{{$produto->id}}">
				@csrf
				<div class="row">
					<div class="form-group validated col-sm-3 col-lg-3">
						<label class="col-form-label">Valor</label>
						<div class="">
							<input type="text" id="novo_valor" class="form-control @if($errors->has('novo_valor')) is-invalid @endif money" name="novo_valor" value="{{{ isset($produto->valor) ? moeda($produto->valor) : old('novo_valor') }}}">
							@if($errors->has('novo_valor'))
							<div class="invalid-feedback">
								{{ $errors->first('novo_valor') }}
							</div>
							@endif
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-12">
						<a class="btn btn-danger" href="/listaDePrecos">Cancelar</a>
						<button type="submit" class="btn btn-success mr-2">Salvar</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection