@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
				<a href="/categoriasConta/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i> Nova Categoria
				</a>
			</div>
		</div>
		<br>
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row">
				@foreach($categorias as $c)
				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="card card-custom gutter-b example example-compact shadow-sm">
						<div class="card-body">
							<div class="d-flex align-items-center justify-content-between">
								<h3 class="font-size-h4 text-dark-75 font-weight-bolder mb-0">{{$c->nome}}</h3>
								
								<div class="d-flex">
									<a href="/categoriasConta/edit/{{$c->id}}" class="btn btn-icon btn-circle btn-sm btn-warning mr-1" title="Editar"><i class="la la-pencil"></i></a>
									<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriasConta/delete/{{$c->id}}" }})' class="btn btn-icon btn-circle btn-sm btn-danger mr-1" title="Excluir"><i class="la la-trash"></i></a>
								</div>
							</div>

							<hr>

							<div class="d-flex flex-column">
								<div class="d-flex justify-content-between mb-2">
									<span class="text-muted font-weight-bold">Tipo:</span>
									<span class="label label-inline {{ $c->tipo == 'receber' ? 'label-light-success' : 'label-light-danger' }} font-weight-bold">
										{{ strtoupper($c->tipo) }}
									</span>
								</div>

								<div class="d-flex justify-content-between mb-2">
									<span class="text-muted font-weight-bold">Grupo DRE:</span>
									@if($c->dre_grupo)
										<span class="label label-inline label-light-primary font-weight-bold">
											{{ App\Models\CategoriaConta::gruposDRE()[$c->dre_grupo] ?? $c->dre_grupo }}
										</span>
									@else
										<span class="label label-inline label-light-dark font-weight-bold">Não Definido</span>
									@endif
								</div>

								<div class="d-flex justify-content-between">
									<span class="text-muted font-weight-bold">Apuração:</span>
									@if($c->incluir_resultado)
										<span class="text-success font-weight-bold"><i class="la la-check text-success"></i> No Resultado</span>
									@else
										<span class="text-muted font-weight-bold"><i class="la la-close text-muted"></i> Ignorar</span>
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
</div>
@endsection