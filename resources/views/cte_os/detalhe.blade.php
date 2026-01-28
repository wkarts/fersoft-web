@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<div class="row">
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">

								<div class="row">
									<div class="col-lg-12 col-md-12 col-xl-12 col-12">
										<h4>Código: <strong class="text-info">{{$cte->id}}</strong></h4>
										<h4>Natureza de operação: <strong class="text-info">{{$cte->natureza->natureza}}</strong></h4>
										<h4>Data de registro: <strong class="text-info">{{ \Carbon\Carbon::parse($cte->data_registro)->format('d/m/Y H:i:s')}}</strong></h4>
										<h4>Valor de transporte: R$ <strong class="text-info">{{ number_format($cte->valor_transporte, 2, ',', '.') }}</strong></h4>
										<h4>Valor a receber: R$ <strong class="text-info">{{ number_format($cte->valor_receber, 2, ',', '.') }}</strong></h4>
										<h4>Descrição do serviço: <strong class="text-info">{{ $cte->descricao_servico }}</strong></h4>
										

										@if($adm)
										<a href="/cteos/estadoFiscal/{{$cte->id}}" class="btn btn-danger">
											<i class="la la-warning"></i>
											Alterar estado fiscal da CTeOs
										</a>
										@endif
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-sm-6 col-lg-6 col-md-6">

						<div class="card card-custom gutter-b">
							<div class="card-header">
								<h3 class="card-title">EMITENTE</h3>
							</div>
							<div class="card-body">
								<h5>Razao Social: <strong>{{$cte->emitente->razao_social}}</strong></h5>
								<h5>CNPJ: <strong>{{$cte->emitente->cpf_cnpj}}</strong></h5>
								<h5>Rua: <strong>{{$cte->emitente->rua}}, {{$cte->emitente->numero}}</strong></h5>
								<h5>Bairro: <strong>{{$cte->emitente->bairro}}</strong></h5>
								<h5>Cidade: <strong>{{$cte->emitente->cidade->nome}}</strong></h5>
							</div>
						</div>
					</div>

					<div class="col-sm-6 col-lg-6 col-md-6">

						<div class="card card-custom gutter-b">
							<div class="card-header">
								<h3 class="card-title">TOMADOR</h3>
							</div>
							<div class="card-body">
								<h5>Razao Social: <strong>{{$cte->tomador_cli->razao_social}}</strong></h5>
								<h5>CNPJ: <strong>{{$cte->tomador_cli->cpf_cnpj}}</strong></h5>
								<h5>Rua: <strong>{{$cte->tomador_cli->rua}}, {{$cte->tomador_cli->numero}}</strong></h5>
								<h5>Bairro: <strong>{{$cte->tomador_cli->bairro}}</strong></h5>
								<h5>Cidade: <strong>{{$cte->tomador_cli->cidade->nome}}</strong></h5>
							</div>
						</div>
					</div>

					<div class="col-sm-6 col-lg-6 col-md-6">

						<div class="card card-custom gutter-b">
							<div class="card-header">
								<h3 class="card-title">VEICULO</h3>
							</div>
							<div class="card-body">
								<h5>Marca: <strong>{{$cte->veiculo->marca}}</strong></h5>
								<h5>Modelo: <strong>{{$cte->veiculo->modelo}}</strong></h5>
								<h5>Placa: <strong>{{$cte->veiculo->placa}}</strong></h5>
								<h5>Cor: <strong>{{$cte->veiculo->cor}}</strong></h5>
								<h5>RNTRC: <strong>{{$cte->veiculo->cor}}</strong></h5>
							</div>
						</div>
					</div>

				</div>

			</div>
		</div>
	</div>
</div>


@endsection	