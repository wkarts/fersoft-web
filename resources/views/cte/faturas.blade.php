@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/cte/filtroFatura">
				<div class="row align-items-center">
					
					<div class="form-group col-lg-5 col-md-4 col-sm-6">

						<label class="col-form-label">Rementente</label>
						<div class="row">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<select class="form-control select2-custom" style="width: 100%" name="remetente_id">
										<option value="">Selecione o remetente</option>
										@foreach($clientes as $a)
										<option @isset($remetente_id) @if($remetente_id == $a->id) selected @endif @endif value="{{$a->id}}">{{ $a->razao_social }}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>
					
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
					</div>
				</div>
			</form>
			<br>
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Lista de CTe</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Registros: <strong class="text-success">{{sizeof($data)}}</strong></label>
			

		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->

					<div class="wizard-nav">

						
						<table class="table">
							<thead>
								<tr>
									<th>ID</th>
									<th>Remetente</th>
									<th>Data</th>
									<th>Total de documentos</th>
									<th>Desconto</th>
									<th>Valor</th>
									<th>Valor total</th>
									<th>Conta receber</th>
									<th>Ações</th>
								</tr>
							</thead>
							<tbody>
								@foreach($data as $item)
								<tr>
									<td>#{{$item->id}}</td>
									<td>{{$item->remetente->razao_social}}</td>
									<td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
									<td>{{ sizeof($item->documentos) }}</td>
									<td>{{ moeda($item->desconto) }}</td>
									<td>{{ moeda($item->valor_total) }}</td>
									<td>{{ moeda($item->valor_total-$item->desconto) }}</td>
									<td>
										@if($item->conta_receber_id)
										<a href="/contasReceber/receber/{{ $item->conta_receber_id }}" class="btn btn-sm btn-info">ver</a>
										@else
										--
										@endif
									</td>
									<td>
										<a class="btn btn-info btn-sm" href="/cte/imprimirFatura/{{$item->id}}">
											<i class="la la-print"></i>
										</a>

										<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/cte/deleteFatura/{{ $item->id }}" }else{return false} })' href="#!">
											<i class="la la-trash"></i>	
										</a>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>




@endsection	