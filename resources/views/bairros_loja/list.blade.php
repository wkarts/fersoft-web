@extends('default.layout')
@section('content')


<div class="card card-custom gutter-b">


	<div class="card-body">
		<div class="">
			<div class="col-12">

				<a href="/bairrosDeliveryLoja/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Bairro
				</a>

				<button class="btn btn-lg btn-info" data-toggle="modal" data-target="#modal-bairros">
					<i class="fa fa-list"></i>Bairros para sua cidade
				</button>
			</div>
		</div>
		<br>

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form method="get" action="/bairrosDeliveryLoja/filtro">
				<div class="row align-items-center">

					
					<div class="col-lg-4 col-xl-4">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<label>Pesquisar bairro</label>

								<div class="input-group">
									<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisar bairro" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
									
								</div>
							</div>
						</div>
					</div>


					<div class="col-lg-2 col-xl-2 mt-3 mt-lg-0">
						<button class="btn btn-light-primary px-6 font-weight-bold mt-5">Pesquisa</button>
					</div>
				</div>

			</form>
			<br>
			<h4>Lista de Bairros</h4>

			<div class="row">

				@foreach($bairros as $b)

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{substr($b->nome, 0, 60)}}
							</h3>

							<div class="card-toolbar">
								<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
									<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<i class="fa fa-ellipsis-h"></i>
									</a>
									<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
										<!--begin::Navigation-->
										<ul class="navi navi-hover">
											<li class="navi-header font-weight-bold py-4">
												<span class="font-size-lg">Ações:</span>
											</li>
											<li class="navi-separator mb-3 opacity-70"></li>
											<li class="navi-item">
												<a href="/bairrosDeliveryLoja/edit/{{$b->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-warning">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/bairrosDeliveryLoja/delete/{{$b->id}}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Excluir</span>
													</span>
												</a>
											</li>


										</ul>
										<!--end::Navigation-->
									</div>
								</div>

							</div>
						</div>

						<div class="card-body">

							<div class="kt-widget__info">
								<span class="kt-widget__label">Valor de entrega:</span>
								<a target="_blank" class="kt-widget__data text-success">R$ {{ moeda($b->valor_entrega) }}</a>
							</div>

						</div>

					</div>

				</div>
				@endforeach

			</div>
			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div class="d-flex flex-wrap py-2 mr-3">

					{{$bairros->links()}}

				</div>
			</div>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-bairros" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Bairros cadastrados pelo administrador</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row">
					@foreach($bairrosDoSuper as $b)
					<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
						<div class="card card-custom gutter-b example example-compact">
							<div class="card-header">
								<div class="card-">
									<h3 style="width: 230px; font-size: 12px; height: 10px;" class="">{{$b->id}} - {{substr($b->nome, 0, 30)}}
									</h3>
									<h3 style="font-size: 12px; width: 50px;" class="">R$ {{ number_format($b->valor_entrega, 2, ',', '.') }}

									</div>
								</div>
							</div>
						</div>
						@endforeach

					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<a href="/bairrosDeliveryLoja/herdar" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Usar estes bairros</a>
				</div>
			</div>
		</div>
	</div>

	@endsection	