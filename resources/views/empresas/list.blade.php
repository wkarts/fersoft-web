@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/empresas/filtro">
				<div class="row align-items-center">

					<!-- <div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">Nome/Razão social</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="nome" class="form-control" value="{{{isset($nome) ? $nome : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-3 col-md-6 col-sm-6">
						<label class="col-form-label">Nome fantasia</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="nome_fantasia" class="form-control" value="{{{isset($nome_fantasia) ? $nome_fantasia : ''}}}" />
							</div>
						</div>
					</div> -->

					<div class="form-group col-md-4 col-12">
						<label class="col-form-label">Empresa</label>
						<div class="">
							<select name="emp_id" class="custom-select w-100" id="inp-empresa_id">
								@isset($emp)
								@if($emp)
								<option value="{{ $emp->id }}">{{ $emp->nome }} - {{ $emp->nome_fantasia }}</option>
								@endif
								@endif
							</select>
						</div>
					</div>
					<div class="form-group col-lg-2 col-md-3 col-sm-2">
						<label class="col-form-label">Tipo</label>
						<div class="">
							<select name="tipo" class="custom-select">
								<option @isset($tipo) @if($tipo == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
								<option @isset($tipo) @if($tipo == 'empresa') selected @endif @else selected @endisset value="empresa">Empresa</option>
								<option @isset($tipo) @if($tipo == 'contador') selected @endif @endisset value="contador">Contador</option>
								<option @isset($tipo) @if($tipo == 'representante') selected @endif @endisset value="representante">Representante</option>
							</select>
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
						<a href="/empresas" style="margin-top: 15px;" class="btn btn-light-danger px-6 font-weight-bold">Limpar</a>
					</div>
				</div>
			</form>

			@if(env("APP_ENV") != "demo")
			<div class="form-group validated col-12">
				<label class="col-form-label">DEBUG</label>
				<span class="switch switch-outline switch-danger">
					<label>
						<input value="true" @if(env("APP_DEBUG") == 'true') checked @endif type="checkbox" id="debug">
						<span></span>
					</label>
				</span>
			</div>
			@endif

			@if(env("APP_ENV") == "demo")
			<h4 class="text-danger">Algumas dados e funções estão ocultas por estar em modo demonstração!</h4>
			@endif

			<div class="row">
				<div class="col-lg-12">
					<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight ml-3">Lista de Empresas</h4>
					@if(!isset($filtro))
					<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight ml-3">Registros: <strong class="text-success">{{ $empresas->total() }}</strong></label>
					@else
					<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight ml-3">Registros: <strong class="text-success">{{ sizeof($empresas) }}</strong></label>
					@endif
				</div>
			</div>
			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="row">
					<div class="col-lg-2">
						<a href="/empresas/nova" class="btn btn-success">
							<i class="la la-plus"></i>
							Nova Empresa
						</a>
					</div>

					@isset($paraImprimir)
					<form method="post" action="/empresas/relatorio" class="col-lg-10">
						@csrf
						<!-- <input type="hidden" name="nome" value="{{{ isset($nome) ? $nome : '' }}}">
						<input type="hidden" name="nome_fantasia" value="{{{ isset($nome_fantasia) ? $nome_fantasia : '' }}}"> -->

						<input type="hidden" name="emp_id" value="{{ $emp ? $emp->id : '' }}">
						<input type="hidden" name="status" value="{{{ isset($status) ? $status : '' }}}">
						<input type="hidden" name="tipo" value="{{{ isset($tipo) ? $tipo : '' }}}">
						<input type="hidden" name="cpf_cnpj" value="{{{ isset($cpf_cnpj) ? $cpf_cnpj : '' }}}">
						<button class="btn btn-lg btn-info float-right btn-sm">
							<i class="fa fa-print"></i>Imprimir relatório
						</button>
					</form>
					@endisset

				</div>
			</div>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight mt-3">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>


								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data cadastro</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome/Razão social</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome fantasia</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Representante</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Contador</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 140px;">CPF/CNPJ</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Telefone</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Plano</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>

								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ultimo login</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Dias para expirar</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($empresas as $e)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$e->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ env("APP_ENV") == "demo" ? "--" :  $e->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{ env("APP_ENV") == "demo" ? "--" : $e->nome_fantasia}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										@if($e->tipo_representante)
										<span class="label label-xl label-inline label-light-success">
											SIM
										</span>

										@else
										<span class="label label-xl label-inline label-light-info">
											NÃO
										</span>
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										@if($e->tipo_contador)
										<span class="label label-xl label-inline label-light-success">
											SIM
										</span>

										@else
										<span class="label label-xl label-inline label-light-info">
											NÃO
										</span>
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 140px;">
										{{ env("APP_ENV") == "demo" ? "--" :  $e->cnpj }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ env("APP_ENV") == "demo" ? "--" :  $e->telefone}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{$e->cidade}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if($e->planoEmpresa)
										{{$e->planoEmpresa->plano->nome}}
										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if($e->status() == -1)
										<span class="label label-xl label-inline label-info">
											MASTER
										</span>

										@elseif($e->status() && $e->tempo_expira >= 0)
										<span class="label label-xl label-inline label-success">
											ATIVO
										</span>
										@else

										@if(!$e->planoEmpresa)
										<span class="label label-xl label-inline label-danger">
											DESATIVADO
										</span>
										@else

										@if($e->planoEmpresa->expiracao == '0000-00-00' && $e->status())
										<span class="label label-xl label-inline label-success">
											ATIVO
										</span>
										@else
										<span class="label label-xl label-inline label-danger">
											DESATIVADO
										</span>
										@endif
										@endif
										@endif

									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">

										@if($e->ultimoLogin($e->id))
										{{
											\Carbon\Carbon::parse(
											$e->ultimoLogin($e->id)->created_at)->format('d/m/Y H:i')
										}}
										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										@if($e->tempo_expira)

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
									<span class="codigo" style="width: 280px;">
										<a href="/empresas/detalhes/{{$e->id}}" class="btn btn-sm btn-primary">
											Detalhes
										</a>

										@if(!$e->isMaster())
										<a onclick='swal("Atenção!", "Deseja remover esta empresa?", "warning").then((sim) => {if(sim){ location.href="/empresas/verDelete/{{ $e->id }}" }else{return false} })' href="#!"  class="btn btn-sm btn-danger">
											Remover
										</a>
										@endif

										@if($e->status)
										<a href="/empresas/alterarStatus/{{$e->id}}" class="btn btn-sm btn-warning">
											Bloquear
										</a>
										@else
										<a href="/empresas/alterarStatus/{{$e->id}}" class="btn btn-sm btn-success">
											Desbloquear
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
		</div>
		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<div class="d-flex flex-wrap py-2 mr-3">
				@if(isset($links))
				{{$empresas->links()}}
				@endif
			</div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">
	$('#debug').click(() => {
		location.href="/empresas/alteraDebug"
	})
</script>
@endsection

@endsection
