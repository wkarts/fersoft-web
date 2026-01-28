@extends('default.layout')
@section('css')
<style type="text/css">
	.img-thumb{
		height: 70px;
		width: 100px;
		border-radius: 10px;
	}

	.btn-fab {
		position: fixed;
		bottom: 20px;
		right: 30px;
		z-index: 9999;
		border: none;
		outline: none;
		background-color: red;
		color: white;
		cursor: pointer;
		padding: 15px;
		border-radius: 10px;
	}  
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="">

			<div class="col-12">

			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4>Lista de Produtos de Delivery</h4>

			<p class="text-danger">*filtro todos os produtos do sistema</p>
			<form method="get" action="/deliveryProduto/editManySearch">
				<div class="row align-items-center">
					<div class="col-lg-5 col-12">

						<label>Produto</label>
						<input type="text" name="pesquisa" class="form-control" value="{{{isset($pesquisa) ? $pesquisa : ''}}}">
					</div>
					<div class="col-lg-2 col-6">

						<label>Tipo</label>
						<select name="tipo" class="form-control">
							<option value="">Todos</option>
							<option @isset($tipo) @if($tipo == 'delivery') selected @endif @endif value="delivery">Cadastrado no delivery</option>
							<option @isset($tipo) @if($tipo == 'nao_delivery') selected @endif @endif value="nao_delivery">Não cadastrado no delivery</option>
						</select>
					</div>
					<div class="col-lg-2 col-xl-2 mt-6">
						<button type="submit" class="btn btn-light-primary px-6 font-weight-bold">Buscar</button>
					</div>
				</div>
				<br>
			</form>
			<form class="row" method="get" action="/deliveryProduto/confirmMany">

				@isset($data)
				<table class="table">
					<thead>
						<tr>
							<th></th>
							<th>Produto</th>
							<th>Imagem</th>
							<th>Delivery</th>
						</tr>
					</thead>
					<tbody>
						@foreach($data as $item)
						<tr>
							<td>
								<input value="{{$item->id}}" type="checkbox" name="check[]" class="check">
							</td>
							<td>
								{{ $item->nome }}
							</td>
							<td>
								@if(!$item->delivery)
								<img class="img-thumb" src="{{ $item->img }}">
								@else
								<img class="img-thumb" src="{{ $item->delivery->img }}">
								@endif
							</td>
							<td>
								@if($item->delivery)
								<i class="fa fa-check text-success"></i>
								@else
								<i class="fa fa-close text-danger"></i>
								@endif
							</td>
						</tr>
						@endforeach
					</tbody>
				</table>
				@endisset

				<button type="submit" class="btn btn-fab btn-success btn-pronto d-none">
					<i class="la la-check"></i> Pronto
				</button>

			</form>
			
		</div>
	</div>
</div>
@endsection
@section('javascript')
<script type="text/javascript">
	$(function(){
		validCheck()
	})

	$('.check').click(() => {
		validCheck()
	})

	function validCheck(){
		$('.btn-pronto').addClass('d-none')
		$('.check').each(function(i,x){
			if(x.checked){
				$('.btn-pronto').removeClass('d-none')
			}
		})
	}
</script>
@endsection