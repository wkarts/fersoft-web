@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/contadores/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">Nome</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="nome" class="form-control" value="{{{isset($nome) ? $nome : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group validated col-12 col-lg-3">
						<label class="col-form-label">Representante</label>
						<div class="">
							<select name="representante_id" class="select2-custom custom-select">
								<option value="null">selecione</option>
								@foreach($representantes as $r)
								<option @isset($representante_id) @if($representante_id == $r->id) selected @endif @endif value="{{ $r->id }}">{{ $r->nome }} {{ $r->cpf_cnpj }}</option>
								@endforeach
							</select>

						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de Contadores</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: exibindo <strong class="text-success">{{sizeof($data)}} de {{$count}}</strong></label>
			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="row">
					<a href="/contadores/new" class="btn btn-success">
						<i class="la la-plus"></i>
						Novo Contador
					</a>
				</div>
			</div>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Razão Social</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Empresa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Nome Fantasia</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CNPJ</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Representante</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data de Cadastro</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($data as $c)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$c->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 250px;">
										{{$c->razao_social}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										@if($c->empresa)
										<span class="label label-xl label-inline label-light-success">
											SIM
										</span>
										@else
										<span class="label label-xl label-inline label-light-danger">
											NÃO
										</span>
										@endif
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
										@if($c->cidade)
										{{$c->cidade->nome}} ({{$c->cidade->uf}})
										@else
										--
										@endif
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ $c->representanteExt ? $c->representanteExt->nome . " - " . ($c->representanteExt->representante ? $c->representanteExt->representante->cpf_cnpj : '') : '' }}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ __date($c->created_at) }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 200px;">
										<a href="/contadores/editar/{{$c->id}}" class="btn btn-sm btn-warning">
											<i class="la la-edit"></i>
										</a>
										<a onclick='swal("Atenção!", "Deseja remover esta registro?", "warning").then((sim) => {if(sim){ location.href="/contadores/delete/{{ $c->id }}" }else{return false} })' href="#!"  class="btn btn-sm btn-danger">
											<i class="la la-trash"></i>
										</a>
										<a href="/contadores/empresas/{{$c->id}}" class="btn btn-sm btn-info">
											<i class="la la-list"></i>
										</a>
										@if($c->empresa)
										<a href="/empresas/detalhes/{{$c->empresa->id}}" class="btn btn-sm btn-primary" title="Detalhes">
											<i class="la la-file"></i>
										</a>
										@endif
									</span>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
			<div class="d-flex justify-content-between align-items-center flex-wrap">
				@if($data instanceof \Illuminate\Pagination\AbstractPaginator)
				<div class="d-flex flex-wrap py-2 mr-3">
					{{$data->links()}}
				</div>
				@endif
			</div>
		</div>
	</div>
</div>

@endsection	
