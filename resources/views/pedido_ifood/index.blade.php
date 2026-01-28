@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">
			<div class="col-12">
				<a href="{{ route('ifood.new-orders') }}" class="btn btn-lg btn-danger btn-refresh spinner-white spinner-right">
					<i class="fa fa-refresh"></i>Atualizar/Buscar Pedidos
				</a>

				<!-- <button class="btn btn-lg btn-info btn-refresh spinner-white spinner-right">
					<i class="fa fa-check"></i>
					Status da loja
				</button> -->
			</div>
		</div>
		<br>

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

				<form method="get" class="mt-2" action="/ifood/pedidosFilter">
					<div class="row align-items-center">
						<div class="col-lg-4 col-xl-4">
							<div class="row align-items-center">
								<div class="col-md-12 my-2 my-md-0">
									<label>Nome do cliente</label>
									<input type="text" name="search" class="form-control" value="{{{isset($search) ? $search : ''}}}" id="">
								</div>
							</div>
						</div>

						<div class="col-lg-2">
							<div class="row align-items-center">
								<div class="col-md-12 my-2 my-md-0">
									<label>Status</label>
									<select class="form-control" name="status">
										<option value="">Todos</option>
										<option @isset($status) @if($status == 'PLC') selected @endif @endif value="PLC">Novo pedido</option>
										<option @isset($status) @if($status == 'CFM') selected @endif @endif value="CFM">Confirmado</option>
										<option @isset($status) @if($status == 'RTP') selected @endif @endif value="RTP">Pronto para ser retirado</option>
										<option @isset($status) @if($status == 'DSP') selected @endif @endif value="DSP">Saiu para entrega</option>
										<option @isset($status) @if($status == 'CON') selected @endif @endif value="CON">Concluído</option>
										<option @isset($status) @if($status == 'CAN') selected @endif @endif value="CAN">Cancelados</option>
									</select>
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
			@if(isset($links))
			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Total de pedidos: <strong class="text-success">{{($data->total())}}</strong></label>
			@endif
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
												<a href="/ifood/pedidosDetail/{{$item->id}}" class="navi-link">
													<span class="navi-text">
														<span class="label label-xl label-inline label-light-info">Detalhes do pedido</span>
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
											{{ $item->pedido_id }}
										</a>

									</div>
									<!--end::Title-->
								</div>
								<!--end::Title-->
							</div>
							<!--end::User-->
							<!--begin::Desc-->

							<p class="text-muted font-weight-bold">Cliente: 
								<strong class="text-info"> {{ $item->nome_cliente ?? '--' }}</strong>
							</p>

							<p class="text-muted font-weight-bold">Valor: 
								<strong class="text-danger"> R$ {{ number_format($item->valor_total, 2, ',', '.') }}</strong>
							</p>
							<p class="text-muted font-weight-bold">Status:
								@if($item->status == 'CAN')
								<strong class="text-danger"> {{ $item->status }}</strong>
								@elseif($item->status == 'CFM')
								<strong class="text-success"> {{ $item->status }}</strong>
								@else
								<strong class="text-info"> {{ $item->status }}</strong>
								@endif
							</p>
							<p class="text-muted font-weight-bold">Data: 
								<strong class="text-danger"> 
									{{ \Carbon\Carbon::parse($item->data_pedido)->format('d/m/Y H:i') }}
								</strong>
							</p>

							<p class="text-muted font-weight-bold">Solicitação de entrega:
								@if($item->status_driver)
								<strong class="text-success">SIM</strong>
								@else
								<strong class="text-danger">NÃO</strong>
								@endif
							</p>

							@if($item->status == 'CFM')
							<a class="btn btn-success btn-block" href="/ifood/dispatch/{{ $item->id }}">
								<i class="la la-check"></i>
								Alterar para despachado
							</a>
							@endif

							@if(!$item->status_driver)
							<a class="btn btn-info btn-block" href="/ifood/requestDriver/{{ $item->id }}">
								<i class="la la-motorcycle"></i>
								Solicitar entregador
							</a>
							@endif

							
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


