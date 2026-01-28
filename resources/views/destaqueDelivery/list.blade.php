@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="">

			<div class="col-12">

				<a href="/destaquesDelivery/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Destaque
				</a>
				
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4>Lista de Destaques de Delivery</h4>
			<label>Numero de registros: <strong class="text-info">{{sizeof($data)}}</strong></label>					

			<form method="get" action="/destaquesDelivery/pesquisa">
				<div class="row align-items-center">
					<div class="col-lg-5 col-xl-5">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label" id="">Loja</label>

								<select class="form-control select2" id="kt_select2_3" name="loja_id">
									<option value="">Selecione a loja</option>
									@foreach($lojas as $l)
									<option @isset($loja_id) @if($loja_id == $l->id) selected @endif @endif value="{{$l->id}}">{{$l->nome}}</option>
									@endforeach
								</select>
							</div>

						</div>
					</div>

					<div class="col-lg-2 col-xl-2">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label" id="">Status</label>

								<select class="form-control " id="" name="status">
									<option value="">Todos</option>
									<option @isset($status) @if($status == 1) selected @endif @endif value="1">Ativo</option>
									<option @isset($status) @if($status == -1) selected @endif @endif value="-1">Desativado</option>
								</select>
							</div>

						</div>
					</div>
					<div class="col-lg-2 col-xl-2 mt-lg-0">
						<br>
						<button type="submit" class="btn btn-light-primary px-6 font-weight-bold mt-4">Buscar</button>
					</div>
				</div>
				<br>
			</form>
			<div class="row">

				@foreach($data as $item)


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
												<a href="/destaquesDelivery/edit/{{ $item->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-primary">Editar</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/destaquesDelivery/delete/{{ $item->id }}" }else{return false} })' href="#!" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-danger">Remover</span>
													</span>
												</a>
											</li>
											<li class="navi-item">
												<a href="/destaquesDelivery/push/{{ $item->id }}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">Criar Push</span>
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

											<img src="/destaques_delivery/{{$item->img}}" alt="image">

										</div>
										<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
											<span class="font-size-h3 font-weight-boldest">Empresa</span>
										</div>
									</div>
									<!--end::Pic-->
									<!--begin::Title-->
									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">Empresa: {{ $item->empresa_id != null ? $item->empresa->nome : '--'}}</a>

									</div>
									<!--end::Title-->
								</div>
								<!--end::Title-->
							</div>
							<!--end::User-->
							<!--begin::Desc-->

							<div class="mb-7">
								<div class="d-flex justify-content-between align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Produto:</span>
									<a href="#" class="text-danger">{{$item->produto_id ? $item->produto->produto->nome : '--'}}</a>
								</div>
								

								<div class="d-flex justify-content-between align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Status:</span>
									<span class="text-danger">
										<div class="switch switch-outline switch-success">
											<label class="">
												<input onclick="alterarStatus({{$item->id}})" @if($item->status) checked @endif value="true" name="status" class="red-text" type="checkbox">
												<span class="lever"></span>
											</label>
										</div>
									</span>
								</div>
							</div>

						</div>
						<!--end::Body-->
					</div>
					<!--end::Card-->
				</div>

				@endforeach
			</div>
			@if(isset($links))
			<div class="d-flex justify-content-between align-items-center flex-wrap">
				<div class="d-flex flex-wrap py-2 mr-3">

					{{$data->links()}}
				</div>
			</div>
			@endif
		</div>
	</div>
</div>

@endsection	

@section('javascript')

<script type="text/javascript">
	function alterarStatus(id){

		$.ajax
		({
			type: 'GET',
			url: path + 'destaquesDelivery/alterarStatus/'+id,
			dataType: 'json',
			success: function(e){
				console.log(e)

			}, error: function(e){
				console.log(e)
			}

		});
	}
</script>
@endsection