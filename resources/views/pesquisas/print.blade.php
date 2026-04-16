<!DOCTYPE html>
<html>
<head>
	<title></title>
	<!--  -->

	<style type="text/css">

		.content{
			margin-top: -30px;
		}
		.titulo{
			font-size: 20px;
			margin-bottom: 0px;
			font-weight: bold;
		}

		.b-top{
			border-top: 1px solid #000;
		}
		.b-bottom{
			border-bottom: 1px solid #000;
		}
		.page_break { page-break-before: always; }

		.resposta{
			width: 100% !important;
			margin-top: 20px;
			margin-bottom: 20px;
		}
		.resposta .empresa{
			font-size: 15px;
			width: 100%;
			margin-top: 10px;
		}

		.resposta .nota{
			font-size: 15px;
			display: block;
			width: 230px;
			float: right;
			margin-top: -20px;
		}

		.nota i{
			color: #999!important;
		}

		i.check{
			color: #F5D80D!important;
		}

		.texto{
			width: 100%;
			display: block;
			margin-top: 10px;
			float: left;
		}

		.divider{
			width: 100%;
			height: 1px;
			background: #000;
		}

	</style>

</head>
<body>
	<div class="content">
		<table>
			<tr>

				@if($config->logo != "")
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('logos/').$config->logo))}}" width="100px;">
				</td>
				@else
				<td class="" style="width: 150px;">
					<img src="{{'data:image/png;base64,' . base64_encode(safe_file_get_contents(@public_path('imgs/slym.png')))}}" width="100px;">
				</td>
				@endif

				<td class="" style="width: 400px;">
					<center><label class="titulo">Pesquisa de satisfação</label></center>
				</td>
			</tr>
		</table>


		<table>
			<tr>
				<td class="" style="width: 700px;">
					<center><h2>{{ $item->titulo }}</h2></center>
					{!! $item->texto !!}
				</td>
			</tr>
		</table>

		<br>

		@foreach($item->respostas as $r)
		<div class="resposta">
			<div class="empresa">
				<span>Empresa: <strong>{{ $r->empresa->nome }}</strong> - NOTA: <strong>{{ $r->nota }}</strong></span>
			</div>

			<div class="texto">

				@if($r->resposta == "")
				<p>sem nenhum texto</p>
				@else
				<p>{{ $r->resposta }}</p>
				@endif
			</div>
		</div>
		<div class="divider"></div>
		@endforeach
	</div>
	<br>

</body>
</html>
