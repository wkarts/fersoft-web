@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-admin-usuarios">

<div class="ui-admin-panel gutter-b">

	<div class="ui-admin-panel-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/usuarios/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Usuário
				</a>
			</div>
		</div>
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside">

			<h4>Lista de Usuários</h4>
			<label>Total de registros: {{sizeof($usuarios)}}</label>
			<div class="row">

				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
						<div class="ui-admin-tabs" role="tablist" aria-label="Visualização de usuários">
							<button class="ui-admin-tab" type="button" role="tab" id="usuarios-tab-tabela" data-wizard-type="step" data-wizard-state="done" aria-controls="usuarios-painel-tabela" aria-selected="true"><i class="la la-table" aria-hidden="true"></i> Tabela</button>
							<button class="ui-admin-tab" type="button" role="tab" id="grade" data-wizard-type="step" data-wizard-state="current" aria-controls="usuarios-painel-grade" aria-selected="false"><i class="la la-tablet" aria-hidden="true"></i> Grade</button>
						</div>

						<div class="pb-5" id="usuarios-painel-tabela" data-wizard-type="step-content" role="tabpanel" aria-labelledby="usuarios-tab-tabela">
							<div class="row">
								<div class="col-xl-12">

									<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

										<table class="datatable-table" style="max-width: 100%; overflow: scroll">
											<thead class="datatable-head">
												<tr class="datatable-row" style="left: 0px;">
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">NOME</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">LOGIN</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">ADM</span></th>
													@if(empresaComFilial())
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">LOCAIS</span></th>
													@endif
													@if(session('user_logged')['adm'])
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">LOGIN</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">LOGOFF</span></th>
													@endif													
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">AÇÕES</span></th>
												</tr>
											</thead>
											<tbody id="body" class="datatable-body">
												@foreach($usuarios as $u)
												<tr class="datatable-row">
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{$u->nome}}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{$u->login}}</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 150px;" id="id">
															@if($u->adm)
															<span class="label label-xl label-inline label-light-success">SIM</span>
															@else
															<span class="label label-xl label-inline label-light-danger">Não</span>
															@endif
														</span>
													</td>
													@if(empresaComFilial())
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{ __get_locais($u->locais) }}</span>
													</td>
													@endif
													@if(session('user_logged')['adm'])
													<td class="datatable-cell">
														<span class="codigo text-success" style="width: 100px;" id="id">
															@if($u->acesso())
															{{ \Carbon\Carbon::parse($u->acesso()->created_at)->format('d/m/Y H:i:s') }}
															@else
															--
															@endif
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo text-danger" style="width: 100px;" id="id">
															@if($u->ultimoAcesso())
															{{ \Carbon\Carbon::parse($u->ultimoAcesso()->updated_at)->format('d/m/Y H:i:s') }}
															@else
															--
															@endif
														</span>
													</td>
													
													@endif
													
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															<a class="btn btn-warning btn-sm" aria-label="Editar {{ $u->nome }}" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/usuarios/edit/{{ $u->id }}" }else{return false} })' href="#!">
																<i class="la la-edit"></i>	
															</a>
															<a class="btn btn-danger btn-sm" aria-label="Remover {{ $u->nome }}" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/usuarios/delete/{{ $u->id }}" }else{return false} })' href="#!">
																<i class="la la-trash"></i>	
															</a>

															@if(session('user_logged')['adm'])
															<a class="btn btn-info btn-sm" aria-label="Histórico de {{ $u->nome }}" href="/usuarios/historico/{{ $u->id }}">
																<i class="la la-list"></i>	
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
						</div>
						<div class="pb-5" id="usuarios-painel-grade" data-wizard-type="step-content" role="tabpanel" aria-labelledby="grade" hidden>
							<div class="row">

								@foreach($usuarios as $c)


								<div class="col-sm-12 col-xl-3 col-lg-4">
									<div class="ui-admin-panel gutter-b ">
										<div class="ui-admin-panel-head">
											<div class="card-title">
												<h3 class="card-title ui-admin-card-title">{{$c->nome}}
												</h3>
											</div>

											<div class="card-toolbar">
												<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
															<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-label="Ações de {{ $c->nome }}" aria-haspopup="true" aria-expanded="false">
														<i class="fa fa-ellipsis-h"></i>
													</a>
													<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
														<!--begin::Navigation-->
														<ul class="navi navi-hover">
															<li class="navi-header font-weight-bold py-4">
																<span class="font-size-lg">Ações:</span>
															</li>
															<li class="navi-separator mb-3 opacity-70"></li>
															<li class="navi-item">
																<a href="/usuarios/edit/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-primary">Editar</span>
																	</span>
																</a>
															</li>
															<li class="navi-item">
																<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/usuarios/delete/{{$c->id}}" }else{return false} })' href="#!" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-danger">Excluir</span>
																	</span>
																</a>
															</li>


														</ul>
														<!--end::Navigation-->
													</div>
												</div>

											</div>
										</div>

										<div class="ui-admin-panel-body">


											<div class="kt-widget__info">
												<span class="kt-widget__label">Login:</span>
												<a class="kt-widget__data text-success">{{$c->login}}</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">ADM:</span>
												<a class="kt-widget__data text-success">{{$c->adm ? 'Sim' : 'Não'}}</a>
											</div>


										</div>
									</div>

								</div>

								@endforeach
							</div>
						</div>
					</div>
				</div>

			</div>

			@if(method_exists($usuarios, 'links'))
				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="d-flex flex-wrap py-2 mr-3">
						{{ $usuarios->links() }}
					</div>
				</div>
			@endif
		</div>
	</div>
</div>

</div>
@endsection

@section('javascript')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('kt_wizard_v3');
    if (!root) return;
    const tabs = Array.from(root.querySelectorAll('.ui-admin-tab'));
    function selectTab(tab, focus) {
        tabs.forEach(function (item) {
            const selected = item === tab;
            item.setAttribute('aria-selected', String(selected));
            item.tabIndex = selected ? 0 : -1;
            document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
        });
        if (focus) tab.focus();
    }
    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { selectTab(tab, false); });
        tab.addEventListener('keydown', function (event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 :
                (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            selectTab(tabs[next], true);
        });
    });
    selectTab(root.querySelector('.ui-admin-tab[aria-selected="true"]') || tabs[0], false);
});
</script>
@endsection
