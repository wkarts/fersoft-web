@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">
			<div class="col-12">
				<a href="/ifood/productsCreate" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Produto
				</a>

				<a href="/ifood/refreshProduct" class="btn btn-lg btn-danger btn-refresh spinner-white spinner-right">
					<i class="fa fa-refresh"></i>Atualizar/Buscar Produtos
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

				<form method="get" action="/ifood/productsFilter">
					<div class="row align-items-center">
						<div class="col-lg-4 col-xl-4">
							<div class="row align-items-center">
								<div class="col-md-12 my-2 my-md-0">
									<label>Nome do produto</label>
									<div class="input-icon">
										<input type="text" name="search" class="form-control" value="{{{isset($search) ? $search : ''}}}" id="kt_datatable_search_query">
										<span>
											<i class="fa fa-search"></i>
										</span>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-2 col-xl-2">
							<button style="margin-top: 25px;" type="submit" class="btn btn-light-primary font-weight-bold">Pesquisa</button>
						</div>
					</div>
				</form>
			</div>
			<br>
			<h4>Lista de Produtos iFood</h4>
			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Total de produtos: <strong class="text-success">{{($data->total())}}</strong></label>
			<div class="row">

				@foreach($data as $item)
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
												<a href="/ifood/productsEdit/{{$item->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-primary">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ifood/productsDestroy/{{$item->id}}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Remover</span>
													</span>
												</a>
											</li>

											<!-- <li class="navi-item">
												<a href="/ifood/galeria/{{$item->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">Galeria</span>
													</span>
												</a>
											</li> -->
											
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
									<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
										<div class="symbol symbol-circle symbol-lg-75">

											@if($item->imagem != "")
											<img src="{{$item->imagem}}" alt="image">
											@else
											<img src="/imgs/no_image.png" alt="image">
											@endif
										</div>
										<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
											<span class="font-size-h3 font-weight-boldest">JM</span>
										</div>
									</div>
									<!--end::Pic-->
									<!--begin::Title-->
									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">
											{{ $item->nome }}
										</a>
									</div>
									<!--end::Title-->
								</div>
								<!--end::Title-->
							</div>
							<!--end::User-->
							<!--begin::Desc-->

							<p class="text-muted font-weight-bold">Valor: 
								<strong class="text-danger"> R$ {{ number_format($item->valor, 2, ',', '.') }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Código de barras: 
								<strong class="text-danger"> {{ $item->ean }}</strong>
							</p>
							<p class="text-muted font-weight-bold">ID do Ifood: 
								<strong class="text-danger"> {{ $item->id_ifood_aux }}</strong>
							</p>

							<p class="text-muted font-weight-bold">Categoria: 
								<strong class="text-danger"> {{ $item->categoria->nome }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Estoque: 
								<strong class="text-danger"> {{ $item->estoque ?? '--' }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Status:
								@if($item->status == 'AVAILABLE')
								<strong class="text-success">Disponível</strong>
								@else

								@if($item->status == '')
								<strong class="text-info">--</strong>
								@else
								<strong class="text-warning">Pausado</strong>
								@endif

								@endif
							</p>

						</div>
						<!--end::Body-->
					</div>
					<!--end::Card-->
				</div>
				@endforeach
			</div>

			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div class="d-flex flex-wrap py-2 mr-3">
					@if(isset($links))
					{{$data->links()}}
					@endif
				</div>
			</div>
			
		</div>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-refresh').click(() => {
		$('.btn-refresh').addClass('spinner')
	})
</script>
@endsection


