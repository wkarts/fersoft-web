@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/contadores/filtroEmpresa">
				<div class="row align-items-center">
					<input type="hidden" name="contador_id" value="{{$contador->id}}">
					<div class="form-group col-lg-4 col-md-6 col-sm-6">
						<label class="col-form-label">Razão Social</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="nome" class="form-control" value="{{{isset($nome) ? $nome : ''}}}" />
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>

			<form class="row" method="post" action="/contadores/set-empresa">
				@csrf
				<input type="hidden" name="contador_id" value="{{ $contador->id }}">
				<div class="form-group validated col-sm-6 col-lg-4 col-12 col-sm-12 add-prod">
					<label>Empresa</label>
					<div class="input-group">

						<div class="input-group-prepend">
							<span class="input-group-text" id="focus-codigo">
								<li class="la la-search"></li>
							</span>
						</div>

						<select class="form-control select2 empresa-search select-search" style="width: 90%" id="kt_select2_1" name="empresa">
							<option value="">Digite para buscar a empresa</option>
						</select>

					</div>
				</div>
				<div class="col-sm-6 col-lg-2">
					<br>
					<button class="btn btn-success mt-2">
						Atribuir empresa
					</button>
				</div>
			</form>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de empresas contador <strong>{{$contador->razao_social}}</strong></h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: exibindo <strong class="text-success">{{sizeof($empresas)}}</strong></label>


			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Razão Social</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Nome Fantasia</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CNPJ</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($empresas as $c)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$c->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 250px;">
										{{$c->nome}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 250px;">
										{{$c->nome_fantasia}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$c->cnpj}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$c->cidade}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										<a title="Remover" class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este vínculo?", "warning").then((sim) => {if(sim){ location.href="/contadores/delete-empresa/{{ $c->id }}" }else{return false} })' href="#!">
											<i class="la la-trash"></i>	
										</a>
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

@section('javascript')
<script type="text/javascript">
	$(function(){
		setTimeout(() => {
			$("#kt_select2_1").select2({
				minimumInputLength: 2,
				language: "pt-BR",
				placeholder: "Digite para buscar a empresa",
				width: "90%",
				ajax: {
					cache: true,
					url: path + 'empresas/autocomplete',
					dataType: "json",
					data: function(params) {
						console.clear()
						var query = {
							pesquisa: params.term,
						};
						return query;
					},
					processResults: function(response) {

						var results = [];

						$.each(response, function(i, v) {
							var o = {};
							o.id = v.id;

							o.text = v.nome
							o.value = v.id;
							results.push(o);
						});
						return {
							results: results
						};
					}
				}
			});
			$('.select2-selection__arrow').addClass('select2-selection__arroww')
			$('.select2-selection__arrow').removeClass('select2-selection__arrow')
		}, 100);
	});
</script>

@endsection	
@endsection	
