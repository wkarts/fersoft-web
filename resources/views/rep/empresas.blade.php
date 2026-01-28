@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/rep/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">Nome/Razão social</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="nome" class="form-control" value="{{{isset($nome) ? $nome : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">CPF/CNPJ</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="cpf_cnpj" class="form-control cpf_cnpj" value="{{{isset($cpf_cnpj) ? $cpf_cnpj : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-3 col-sm-3">
						<label class="col-form-label">Plano</label>
						<div class="">
							<select name="plano" class="custom-select">
								<option @isset($plano) @if($plano == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
								@foreach($planos as $p)
								<option @isset($plano) @if($plano == $p->id) selected @endif @endisset value="{{$p->id}}">{{$p->nome}}</option>
								@endforeach
							</select>
						</div>
					</div>
					<div class="form-group col-lg-3 col-md-3 col-sm-3">
						<label class="col-form-label">Estado</label>

						<div class="">
							<select name="status" class="custom-select">
								<option @isset($status) @if($status == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
								<option @isset($status) @if($status == 1) selected @endif @endisset value="1">ATIVO</option>
								<option @isset($status) @if($status == 2) selected @endif @endisset value="2">PENDENTE</option>
								<option @isset($status) @if($status == '0') selected @endif @endisset value="0">DESATIVADO</option>
							</select>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-6 col-sm-6">
						<label class="col-form-label">Dias para expirar</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="dias_expirar" class="form-control" value="{{{isset($dias_expirar) ? $dias_expirar : ''}}}" />
								
							</div>
						</div>
					</div>
					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de empresas</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{sizeof($empresas)}}</strong></label>
			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="row">
					<a href="/rep/novaEmpresa" class="btn btn-success">
						<i class="la la-plus"></i>
						Nova Empresa
					</a>

				</div>
			</div>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Documento</span></th>

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data cadastro</span></th>
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Endereço</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Plano</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Dias para expirar</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($empresas as $e)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$e->empresa->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$e->empresa->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$e->empresa->cnpj}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($e->empresa->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 250px;">
										{{$e->empresa->rua}}, {{$e->empresa->numero}} - {{$e->empresa->bairro}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{$e->empresa->cidade}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if($e->empresa->planoEmpresa)
										{{$e->empresa->planoEmpresa->plano->nome}}
										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if(!$e->empresa->planoEmpresa)
										<span class="label label-xl label-inline label-light-danger">
											DESATIVADO
										</span>
										@else

										@if($e->empresa->planoEmpresa->expiracao == '0000-00-00' && $e->empresa->status())
										<span class="label label-xl label-inline label-light-success">
											ATIVO
										</span>
										@else
										<span class="label label-xl label-inline label-light-danger">
											DESATIVADO
										</span>
										@endif
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										@if($e->planoEmpresa && $e->tempo_expira)

										@if($e->planoEmpresa->expiracao == '0000-00-00')
										<span class="text-info">Indeterminado</span>
										@else
										@if($e->tempo_expira < 0)
										<span class="text-danger">Vencido</span>

										@elseif($e->tempo_expira >= 0 && $e->tempo_expira < 5)
										<span class="text-warning">{{$e->tempo_expira}}</span>

										@else
										<span class="text-dark">{{$e->tempo_expira}}</span>
										@endif
										@endif

										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 300px;">
										<a href="/rep/detalhes/{{$e->empresa_id}}" class="btn btn-sm btn-primary">
											Detalhes
										</a>

										<a href="/rep/financeiro/{{$e->empresa_id}}" class="btn btn-sm btn-info">
											Financeiro
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

@endsection	
