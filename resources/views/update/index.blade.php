@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<a class="link float-right" href="/appUpdate/sql">Comandos SQL</a>
				<p class="text-danger">Atenção antes de fazer a atualização realize o backup do banco de dados e da aplicação!</p>
				@if(env("URLUPADTE") == "")
				<p class="text-danger">Atenção defina a variavel URLUPADTE no .env</p>
				@endif
				@if(env("SERIALNUMBER") == "")
				<p class="text-danger">Atenção defina a variavel SERIALNUMBER no .env</p>
				@endif
				@if(env("APPVERSION") == "")
				<p class="text-danger">Atenção defina a variavel APPVERSION no .env</p>
				@endif
				<p>Clique abaixo para verificar se existe nova atualização</p>
				<h4>Versão atual <strong>{{ $version }}</strong></h4>
				<button class="btn btn-info btn-consulta spinner-white spinner-right">Consultar nova atualização </button>

				<hr>

				<div class="div-download d-none">
					<h4 class="versao"></h4>
					<h6 class="note"></h6>
					<br>
					<a href="/appUpdate/download" class="btn btn-lg btn-success btn-download spinner-white spinner-right">Download</a>
				</div>

				<br>

			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-consulta').click(() => {
		console.clear()
		$('.btn-consulta').addClass('spinner')
		$.get('{{env("URLUPADTE")}}/api/find_version', {
			'version': '{{ $version }}', 'app_version' : '{{env("APPVERSION")}}'
		}).done((success) => {
			$('.btn-consulta').removeClass('spinner')
			swal("Nova atualização", "Clique abaixo para realizar o download da aplicação", "success")
			$('.versao').html("Versão: " + success.number)
			$('.note').html(success.note)
			$('.div-download').removeClass('d-none')
		})
		.fail((err) => {
			$('.btn-consulta').removeClass('spinner')
			let msg = err.responseJSON
			if(msg == "Nada novo"){
				swal("Tudo certo", "Sua aplicação esta na ultima versão", "success")
			}else{
				swal("Algo deu errado", "Problema ao tentar conexão com servidor de atualização!", "error")
			}
		})
	})

	$('.btn-download').click(() => {
		$('.btn-download').addClass('spinner')
	})
</script>
@endsection