@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">

			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/inventario/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Inventário
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form method="get" action="/inventario/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-4 col-xl-4">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Referência</label>
								<input type="text" name="referencia" value="{{{ isset($referencia) ? $referencia : '' }}}" class="form-control" placeholder="Referência" id="kt_datatable_search_query">
							</div>
						</div>
					</div>
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data de início</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control" readonly value="{{{ isset($dataInicial) ? $dataInicial : '' }}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data de término</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control" readonly value="{{{ isset($dataFinal) ? $dataFinal : '' }}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Categoria</label>

						<select class="custom-select form-control" id="categoria" name="tipo">
							<option @if(isset($tipo) && $tipo == 'todos') selected @endif value="todos">Todos</option>
							@foreach(App\Models\Inventario::tipos() as $t)
							<option @if(isset($tipo) && $tipo == $t) selected @endif value="{{$t}}">{{$t}}</option>
							@endforeach
						</select>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 10px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>
			<h4>Lista de Inventários</h4>

			<div class="row">

				@foreach($inventarios as $i)
				<!-- inicio grid -->
				<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
					<!--begin::Card-->
					<div class="card card-custom gutter-b card-stretch">
						<!--begin::Body-->
						<div class="card-body pt-4">
							<!--begin::Toolbar-->
							<div class="d-flex justify-content-end">
								<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" >
									<a href="#" class="btn btn-clean btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										<i class="fa fa-ellipsis-h"></i>
									</a>
									<div class="dropdown-menu dropdown-menu-md dropdown-menu-right">
										<!--begin::Navigation-->
										<ul class="navi navi-hover">
											<li class="navi-header font-weight-bold py-4">
												<span class="font-size-lg">Ações:</span>
												
											</li>
											<li class="navi-separator mb-3 opacity-70"></li>

											<li class="navi-item">
												<a href="/inventario/edit/{{ $i->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-primary">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/inventario/delete/{{ $i->id }}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Remover</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a href="/inventario/apontar/{{ $i->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">Apontar</span>
													</span>
												</a>
											</li>
											
										</ul>
										<!--end::Navigation-->
									</div>
								</div>
							</div>
							<!--end::Toolbar-->
							<!--begin::User-->
							<div class="d-flex align-items-end mb-7">
								<!--begin::Pic-->
								<div class="d-flex align-items-center">
									<!--begin::Pic-->
									
									<!--end::Pic-->
									<!--begin::Title-->
									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">
											{{$i->referencia}}
										</a>

									</div>
									<!--end::Title-->
								</div>
								<!--end::Title-->
							</div>
							<!--end::User-->
							<!--begin::Desc-->
							<p class="text-muted font-weight-bold">Data de início: 
								<strong class="text-info">{{\Carbon\Carbon::parse($i->inicio)->format('d/m/Y')}}</strong>
							</p>
							<p class="text-muted font-weight-bold">Data de término: 
								<strong class="text-info">{{\Carbon\Carbon::parse($i->fim)->format('d/m/Y')}}</strong>
							</p>
							<p class="text-muted font-weight-bold">Tipo: 
								<strong class="text-danger">{{ $i->tipo }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Status: 
								@if($i->status)
								<strong class="text-success">Ativo</strong>
								@else
								<strong class="text-danger">Finalizado</strong>
								@endif

								@if($i->status)
								<a href="/inventario/alterarStatus/{{$i->id}}" class="text-danger">
									<i class="la la-close text-danger"></i>
									Desativar
								</a>
								@else
								<a href="/inventario/alterarStatus/{{$i->id}}" class="text-success">
									<i class="la la-check text-success"></i>
									Ativar
								</a>
								@endif
							</p>

							<p class="text-muted font-weight-bold">Observação: 
								<a onclick='swal("", "{{$i->observacao != '' ? $i->observacao : 'Nenhuma observação'}}", "info")' class="btn btn-sm btn-light-info font-weight-bolder text-uppercase">Ver</a>
							</p>

						</div>
						<!--end::Body-->
					</div>
					<!--end::Card-->
				</div>
				@endforeach
				<!-- fim grid -->
			</div>
		</div>
	</div>
</div>
@endsection	