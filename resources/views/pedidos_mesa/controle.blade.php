@extends('default.layout')
@section('content')
@section('css')
<style type="text/css">
	.spinner-track{
		margin-left: 20px;
		margin-top: -18px;
	}
</style>
@endsection

<div class="card card-custom gutter-b">

	<div class="card-body">
		<h4>Itens pendentes <b class="spinner spinner-track spinner-info spinner-lg d-none"></b></h4>


		<div class="row itens mt-4">
			
		</div>
	</div>
</div>


@endsection
@section('javascript')
<script type="text/javascript">
	$(function(){
		getPedidos()
		setInterval(() => {
			getPedidos()
		}, 3000)
	})

	function getPedidos(){
		$('.spinner-track').removeClass('d-none')
		$.get(path + 'pedidosMesa/itensPendentes')
		.done((res) => {
			$('.spinner-track').addClass('d-none')
			$('.itens').html(res)
		})
		.fail((err) => {
			$('.spinner-track').addClass('d-none')
			console.log(err)
			swal("Erro", "Erro ao buscar pedidos", "error")
		})
	}
</script>
@endsection