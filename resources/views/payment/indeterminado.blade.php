@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/financeiro/indeterminado_filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-5 col-md-6 col-sm-6">
						<label class="col-form-label">Empresa</label>
						<select class="form-control select2" style="width: 100%;" id="kt_select2_4" name="empresa">
							<option value="">Selecione a empresa</option>
							@foreach($empresas as $e)
							<option @isset($empresa) @if($empresa == $e->id) selected @endif @endisset value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
							@endforeach
						</select>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control date-out" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
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


					<div class="col-lg-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">
							<i class="la la-search"></i>
						</button>
					
						<a style="margin-top: 15px;" class="btn btn-danger px-6 font-weight-bold" href="/financeiro/indeterminado">
							<i class="la la-close"></i>
						</a>
					</div>
				</div>
			</form>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de Pagamentos</h4>


			<button class="btn btn-success" data-toggle="modal" data-target="#modal-add">
				<i class="la la-plus"></i> Adicionar
			</button>
			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Empresa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
							</tr>
						</thead>

						@php
						$soma = 0;
						@endphp
						<tbody class="datatable-body">
							@foreach($data as $p)

							<tr class="datatable-row">
								

								<td class="datatable-cell">
									<span class="codigo" style="width: 250px;">
										{{$p->empresa->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ \Carbon\Carbon::parse($p->data_pagamento)->format('d/m/Y')}}
									</span>
								</td>

								

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{number_format($p->valor, 2, ',', '.')}}
									</span>
								</td>


								<td class="datatable-cell">
									<span class="codigo" style="width: 200px;">
										<a onclick='swal("Atenção!", "Deseja remover este pagamento?", "warning").then((sim) => {if(sim){ location.href="/financeiro/indeterminado_delete/{{ $p->id }}" }else{return false} })' href="#!"  class="btn btn-sm btn-danger">
											Remover
										</a>
									</span>
								</td>

							</tr>

							@endforeach
						</tbody>
					</table>
				</div>

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

<div class="modal fade" id="modal-add" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Adicionar pagamento</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<form method="post" action="/financeiro/indeterminado_save">
				@csrf
				<div class="modal-body">

					<div class="row">
						<div class="form-group col-lg-6 col-md-6 col-sm-6">
							<label class="col-form-label">Empresa</label>
							<select required class="form-control select2" style="width: 100%;" id="kt_select2_2" name="empresa">
								<option value="">Selecione a empresa</option>
								@foreach($empresas as $e)
								<option value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
								@endforeach
							</select>
						</div>

						<div class="form-group col-lg-3 col-md-6 col-sm-6">
							<label class="col-form-label">Data</label>
							<input required type="date" name="data_pagamento" class="form-control">
						</div>

						<div class="form-group col-lg-3 col-md-6 col-sm-6">
							<label class="col-form-label">Valor</label>
							<input required type="text" name="valor" class="form-control money">
						</div>

					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>

@endsection	
