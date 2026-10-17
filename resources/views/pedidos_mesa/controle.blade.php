@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush

@section('content')
<header class="fer-page__heading"><h1>Controle de Pedidos da Mesa</h1></header>
@section('css')
<style type="text/css">
	.spinner-track{
		margin-left: 20px;
		margin-top: -18px;
	}
</style>
@endsection

<div class="card card-custom gutter-b fer-page fer-detail">

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