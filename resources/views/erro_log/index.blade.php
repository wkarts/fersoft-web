@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/errosLog/filtro">
				<div class="row align-items-center">

					<div class="form-group validated col-lg-6 col-12">

						<label class="col-form-label text-left">Empresa</label>
						<select class="form-control select2" style="width: 100%;" id="kt_select2_5" name="empresa">
							<option value="null">Selecione a empresa</option>
							@foreach($empresas as $e)
							<option @isset($empresa) @if($empresa == $e->id) selected @endif @endif value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
							@endforeach
						</select>

					</div>

					<div class="form-group col-lg-2 col-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control date-input" value="@isset($data_inicial) {{$data_inicial}} @endif" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>
					<div class="form-group col-lg-2 col-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control date-input" value="@isset($data_final) {{$data_final}} @endif" id="kt_datepicker_3" />
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

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight mt-2">Erros do sistema</h4>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">ID</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Empresa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Linha</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Arquivo</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Mensagem</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($data as $item)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$item->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>
								
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$item->empresa->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{$item->linha}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$item->arquivo}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 300px;">
										{{$item->erro}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										<form action="{{ route('errosLog.destroy', $item->id) }}" method="post" id="form-{{$item->id}}">
											@method('delete')
											@csrf
											<button class="btn btn-sm btn-danger btn-delete">
												<i class="la la-trash"></i>
											</button>

											
										</form>
										
									</span>
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

@endsection	
