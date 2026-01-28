@extends('default.layout')
@section('content')
<style type="text/css">
	.btn-file {
		position: relative;
		overflow: hidden;
	}

	.btn-file input[type=file] {
		position: absolute;
		top: 0;
		right: 0;
		min-width: 100%;
		min-height: 100%;
		font-size: 100px;
		text-align: right;
		filter: alpha(opacity=0);
		opacity: 0;
		outline: none;
		background: white;
		cursor: inherit;
		display: block;
	}
</style>

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<div class="col-lg-12">
					<p class="text-danger ml-4 mt-4">Atenção com os comandos executados nesta tela, pode afetar todo o banco de dados!</p>
				</div>
				
				<form class="container" method="post" action="/appUpdate/sql" enctype="multipart/form-data">
					@csrf
					<br>
					<div class="row">
						<div class="form-group validated col-lg-4">
							<div class="">
								<span style="width: 100%" class="btn btn-primary btn-file">
									Procurar arquivo SQL<input required accept=".sql" name="file" type="file">
								</span>
								<label class="text-info" id="filename"></label>

							</div>

						</div>

						<div class="form-group validated col-lg-2">
							<div class="">
								<button class="btn btn-danger">Executar</button>
							</div>

						</div>
					</div>

					<br>

				</form>

				<hr>
				<form class="container" method="post" action="/appUpdate/run-sql">
					@csrf
					<h5 class="ml-4">Comando Sql</h5>
					<div class="col-12 form-group">
						<textarea name="sql" class="form-control"></textarea>
						<label>separe os comandos com ;</label>
					</div>
					<button class="btn btn-dark float-right mr-3" type="submit">Executar Sql</button>
					<br>
				</form>
				<br><br>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	
</script>
@endsection