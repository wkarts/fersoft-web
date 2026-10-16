@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-produtoEcommerce">

<div class="ui-admin-panel ui-commerce-panel gutter-b">
	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="">

			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">

				<a href="/produtoEcommerce/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Produto de Ecommerce
				</a>
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4>Lista de Produtos de Ecommerce</h4>
			<label>Numero de registros: <strong class="text-info">{{sizeof($produtos)}}</strong></label>					

			<form method="get" action="/produtoEcommerce/pesquisa">
				<div class="row align-items-center">
					<div class="col-lg-5 col-xl-5">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-icon">
									<input type="text" name="pesquisa" class="form-control" value="{{{isset($pesquisa) ? $pesquisa : ''}}}"
									placeholder="Produto..." id="kt_datatable_search_query">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</div>
							</div>

						</div>
					</div>
					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button type="submit" class="btn btn-light-primary px-6 font-weight-bold">Buscar</button>
					</div>
				</div>
				<br>
			</form>

			<div class="ui-commerce-grid" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
				<!--begin: Wizard Nav-->

				<div class="ui-commerce-tabs" role="tablist" aria-label="Visualização de produtos de ecommerce">
                        <button type="button" role="tab" class="ui-commerce-tab" id="produto-tab-tabela" data-wizard-type="step" data-wizard-state="done" aria-controls="produto-painel-tabela" aria-selected="true"><i class="la la-table" aria-hidden="true"></i> Tabela</button>
                        <button type="button" role="tab" class="ui-commerce-tab" id="grade" data-wizard-type="step" data-wizard-state="current" aria-controls="produto-painel-grade" aria-selected="false"><i class="la la-tablet" aria-hidden="true"></i> Grade</button>
                    </div>

				<div class="pb-5" id="produto-painel-tabela" data-wizard-type="step-content" role="tabpanel" aria-labelledby="produto-tab-tabela">
					<div class="row">
						<div class="col-xl-12">

							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

								<table class="datatable-table" style="max-width: 100%; overflow: scroll">
									<thead class="datatable-head">
										<tr class="datatable-row" style="left: 0px;">
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">NOME</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CATEGORIA</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">VALOR</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">ATIVO</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">CONTROLE ESTOQUE</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">DESTAQUE</span></th>

											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">AÇÕES</span></th>
										</tr>
									</thead>

									<tbody id="body" class="datatable-body">
										@foreach($produtos as $p)
										<tr class="datatable-row" @if($p->inativo) style="background: #ffcdd2;" @endif>
											<td class="datatable-cell"><span class="codigo" style="width: 250px;" id="id">
												{{$p->produto->nome}}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">
												{{$p->categoria->nome}}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">
												{{number_format($p->valor, 2, ',', '.')}}</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if($p->status)
													<span class="label label-xl label-inline label-light-success">Ativo</span>
													@else
													<span class="label label-xl label-inline label-light-danger">Desativado</span>
													@endif
												</span>
											</td>

											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if($p->controlar_estoque)
													<span class="label label-xl label-inline label-light-success">Ativo</span>
													@else
													<span class="label label-xl label-inline label-light-danger">Desativado</span>
													@endif
												</span>
											</td>

											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if($p->destaque)
													<span class="label label-xl label-inline label-light-success">Sim</span>
													@else
													<span class="label label-xl label-inline label-light-danger">Não</span>
													@endif
												</span>
											</td>

											<td class="datatable-cell">
												<span class="codigo" style="width: 200px;" id="id">
													<a class="btn btn-warning btn-sm" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/produtoEcommerce/editGrade/{{ $p->id }}" }else{return false} })' href="#!">
														<i class="la la-edit"></i>	

													</a>

													
													<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/produtoEcommerce/delete/{{ $p->id }}" }else{return false} })' href="#!">
														<i class="la la-trash"></i>	
													</a>

													<a class="btn btn-success btn-sm" href="/produtoEcommerce/galeria/{{$p->id}}">
														<i class="la la-photo"></i>	
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

				<div class="pb-5" id="produto-painel-grade" data-wizard-type="step-content" role="tabpanel" aria-labelledby="grade" hidden>

					<div class="row">

						@foreach($produtos as $p)


						<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
							<!--begin::Card-->
							<div class="ui-admin-panel ui-commerce-panel gutter-b">
								<!--begin::Body-->
								<div class="ui-admin-panel-body ui-commerce-panel-body pt-4">
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
														<a href="/produtoEcommerce/edit/{{ $p->id }}" class="navi-link">
															<span class="navi-text">
																<span class="label label-xl label-inline label-light-primary">Editar</span>
															</span>
														</a>
													</li>
													<li class="navi-item">
														<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/produtoEcommerce/delete/{{ $p->id }}" }else{return false} })' href="#!" class="navi-link">
															<span class="navi-text">
																<span class="label label-xl label-inline label-light-danger">Remover</span>
															</span>
														</a>
													</li>

													<li class="navi-item">
														<a href="/produtoEcommerce/galeria/{{ $p->id }}" class="navi-link">
															<span class="navi-text">
																<span class="label label-xl label-inline label-light-success">Galeria</span>
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
											<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
												<div class="symbol symbol-circle symbol-lg-75">
													@if(sizeof($p->galeria) > 0)
													<img src="/ecommerce/produtos/{{$p->galeria[0]->img}}" alt="image">
													@else
													<img src="imgs/no_image.png" alt="image">
													@endif
												</div>
												<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
													<span class="font-size-h3 font-weight-boldest">JM</span>
												</div>
											</div>
											<!--end::Pic-->
											<!--begin::Title-->
											<div class="d-flex flex-column">
												<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">{{$p->produto->nome}}</a>

											</div>
											<!--end::Title-->
										</div>
										<!--end::Title-->
									</div>
									<!--end::User-->
									<!--begin::Desc-->

									<div class="mb-7">
										<div class="d-flex justify-content-between align-items-center">
											<span class="text-dark-75 font-weight-bolder mr-2">Categoria:</span>
											<a href="#" class="text-danger">{{$p->categoria->nome}}</a>
										</div>
										<div class="d-flex justify-content-between align-items-cente my-1">
											<span class="text-dark-75 font-weight-bolder mr-2">Valor:</span>
											<a href="#" class="text-danger">

												<label>R$ {{ number_format($p->valor, 2, ',', '.') }}</label>
											</a>
										</div>

										<div class="d-flex justify-content-between align-items-center">
											<span class="text-dark-75 font-weight-bolder mr-2">Total de imagens:</span>
											<span class="text-danger">{{count($p->galeria)}}</span>
										</div>

										<div class="d-flex justify-content-between align-items-center">
											<span class="text-dark-75 font-weight-bolder mr-2">Ativo:</span>
											<span class="text-danger">
												<div class="switch switch-outline switch-info">
													<label class="">
														<input 
														onclick="alterarStatus({{$p->id}})" @if($p->status) checked @endif value="true" name="status" class="red-text" type="checkbox">
														<span class="lever"></span>
													</label>
												</div>
											</span>
										</div>

										<div class="d-flex justify-content-between align-items-center">
											<span class="text-dark-75 font-weight-bolder mr-2">Controle estoque:</span>
											<span class="text-danger">
												<div class="switch switch-outline switch-danger">
													<label class="">
														<input onclick="alterarControlarEstoque({{$p->id}})" @if($p->controlar_estoque) checked @endif value="true" name="controlar_estoque" class="red-text" type="checkbox">
														<span class="lever"></span>
													</label>
												</div>
											</span>
										</div>

										<div class="d-flex justify-content-between align-items-center">
											<span class="text-dark-75 font-weight-bolder mr-2">Destaque:</span>
											<span class="text-danger">
												<div class="switch switch-outline switch-success">
													<label class="">
														<input onclick="alterarDestaque({{$p->id}})" @if($p->destaque) checked @endif value="true" name="destaque" class="red-text" type="checkbox">
														<span class="lever"></span>
													</label>
												</div>
											</span>
										</div>
									</div>


								</div>
								<!--end::Body-->
							</div>
							<!--end::Card-->
						</div>

						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
@section('javascript')
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('kt_wizard_v3');
    if (!root) return;
    const tabs = Array.from(root.querySelectorAll('.ui-commerce-tab'));
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
    selectTab(tabs[0], false);
});


	function alterarStatus(id){
		$.ajax
		({
			type: 'GET',
			url: path + 'produtoEcommerce/alterarStatus/'+id,
			dataType: 'json',
			success: function(e){

				console.log(e)

			}, error: function(e){
				console.log(e)
			}

		});
	}

	function alterarControlarEstoque(id){
		$.ajax
		({
			type: 'GET',
			url: path + 'produtoEcommerce/alterarControlarEstoque/'+id,
			dataType: 'json',
			success: function(e){

				console.log(e)

			}, error: function(e){
				console.log(e)
			}

		});
	}

	function alterarDestaque(id){
		$.ajax
		({
			type: 'GET',
			url: path + 'produtoEcommerce/alterarDestaque/'+id,
			dataType: 'json',
			success: function(e){

				console.log(e)

			}, error: function(e){
				console.log(e)
			}

		});
	}
</script>
@endsection
@endsection	