@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<form class="card-body" method="post" action="/ordemServico/store_pdv">
		@csrf
		@if(sizeof($produtos) == 0)
		<div class="col-12">
			<p class="text-danger">Cadastre um produto de tipo serviço para continuar <a href="/produtos/new">cadastrar produto</a></p>
		</div>
		@endif
		<input type="hidden" name="os_id" value="{{ $item->id }}">
		<div class="form-group validated col-sm-6 col-lg-5 col-12">
			<label class="col-form-label" id="">Produto</label>

			<select required class="form-control select2" style="" id="kt_select2_1" name="produto_id">
				<option value="">Selecione o produto</option>
				@foreach($produtos as $p)
				<option value="{{$p->id}}">{{$p->nome}}</option>
				@endforeach
			</select>
			<h4>valor total de serviços <strong>{{ moeda($totalServico) }}</strong></h4>
		</div>

		@if(sizeof($produtos) > 0)
		<div class="col-12">
			<button type="submit" class="btn btn-success">Ir para PDV</button>
		</div>
		@endif
	</form>
</div>
@endsection