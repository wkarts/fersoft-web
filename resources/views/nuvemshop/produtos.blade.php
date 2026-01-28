@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
				<a href="/nuvemshop/produto_new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Produto
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

				<form method="get" action="/nuvemshop/produtos">
					<div class="row align-items-center">
						<div class="col-lg-4 col-xl-4">
							<div class="row align-items-center">
								<div class="col-md-12 my-2 my-md-0">
									<label>Descrição do produto</label>
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
			<h4>Lista de Produtos Nuvem Shop</h4>
			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{sizeof($produtos)}}</strong></label>
			<div class="row">

				@foreach($produtos as $p)
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
												<a href="/nuvemshop/produto_edit/{{$p->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-primary">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/nuvemshop/produto_delete/{{$p->id}}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Remover</span>
													</span>
												</a>
											</li>

											<li class="navi-item">
												<a href="/nuvemshop/produto_galeria/{{$p->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">Galeria</span>
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
									<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
										<div class="symbol symbol-circle symbol-lg-75">
											
											@if(sizeof($p->images) > 0)
											<img src="{{$p->images[0]->src}}" alt="image">
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
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">{{$p->name->pt}}</a>

									</div>
									<!--end::Title-->
								</div>
								<!--end::Title-->
							</div>
							<!--end::User-->
							<!--begin::Desc-->

							

							<p class="text-muted font-weight-bold">Preço: 
								<strong class="text-danger">R$ {{ number_format($p->variants[0]->price, 2, ',', '.') }}</strong>
							</p>

							<p class="text-muted font-weight-bold">Preço promocional: 
								<strong class="text-danger">R$ {{ number_format($p->variants[0]->promotional_price, 2, ',', '.') }}</strong>
							</p>

							<p class="text-muted font-weight-bold">Estoque: 
								@if($p->variants[0]->stock == 0)
								<strong class="text-info">ilimitado</strong>
								@else
								<strong class="text-danger"> {{ number_format($p->variants[0]->stock, 2, '.', '') }}</strong>
								@endif
							</p>

							<p class="text-muted font-weight-bold">Código de barras: 
								<strong class="text-danger"> {{ $p->variants[0]->barcode }}</strong>
							</p>

							<p class="text-muted font-weight-bold">Categoria(s): 
								<strong class="text-info">
									@foreach($p->categories as $key => $c)
									{{$c->name->pt}} 

									@if($key < sizeof($p->categories)-1) | @endif
									@endforeach
								</strong>
							</p>

						</div>
						<!--end::Body-->
					</div>
					<!--end::Card-->
				</div>
				@endforeach
			</div>

			@if($search == "")
			<div class="row">
				<div class="col-sm-1">
					@if($page > 1)
					<a class="btn btn-light-primary" href="/nuvemshop/produtos?page={{$page-1}}" class="float-left">
						<i class="la la-angle-double-left"></i>
					</a>
					@endif
				</div>
				<div class="col-sm-10"></div>
				<div class="col-sm-1">
					<a class="btn btn-light-primary" href="/nuvemshop/produtos?page={{$page+1}}" class="float-right">
						<i class="la la-angle-double-right"></i>
					</a>
				</div>
			</div>
			@endif
		</div>
	</div>
</div>
@endsection	