@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-cidadeDelivery">


<div class="ui-admin-panel ui-commerce-panel gutter-b">


	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/cidadeDelivery/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Cidade
				</a>
			</div>
		</div>
		<br>

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			
			<br>
			<h4>Lista de Cidades</h4>
			<label>Total de registros: {{sizeof($cidades)}}</label>
			<div class="row">

				@foreach($cidades as $c)


				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">
							<div class="">
								<h3 class="ui-commerce-title">{{$c->nome}}
								</h3>
							</div>

							<div class="ui-commerce-tools">
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
												<a href="/cidadeDelivery/edit/{{$c->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-warning">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/cidadeDelivery/delete/{{$c->id}}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Excluir</span>
													</span>
												</a>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>

						<div class="ui-admin-panel-body ui-commerce-panel-body">

							<div class="kt-widget__info">
								<p class="font-weight-bold">UF: 
									<strong class="">{{ $c->uf }}</strong>
								</p>
								<p class="font-weight-bold">CEP: 
									<strong class="">{{ $c->cep }}</strong>
								</p>
							</div>
						</div>

					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
</div>

</div>
@endsection	