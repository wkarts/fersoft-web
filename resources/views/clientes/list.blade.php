@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">
				<div class="row">
					<a style="margin-left: 5px; margin-top: 5px;" href="/clientes/new" class="btn btn-lg btn-success">
						<i class="fa fa-plus"></i>Novo Cliente
					</a>
					<a style="margin-left: 5px; margin-top: 5px;" href="/clientes/importacao" class="btn btn-lg btn-danger">
						<i class="fa fa-arrow-up"></i>Importação
					</a>
					<a style="margin-left: 5px; margin-top: 5px;" href="/cashback-config" class="btn btn-lg btn-info">
						<i class="fa fa-cog"></i>Configuração Cash Back
					</a>
				</div>
			</div>
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form method="get" action="/clientes/pesquisa">
				<div class="row align-items-center">
					<div class="col-lg-2 col-xl-2">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<select name="tipo_pesquisa" class="custom-select">
										@foreach(App\Models\Cliente::tiposPesquisa() as $key => $t)
										<option @isset($tipoPesquisa) @if($tipoPesquisa == $key) selected @endif @endif value="{{$key}}">{{$t}}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-2 col-xl-2">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<select name="ordem" class="custom-select">
										<option value="">selecione a ordem</option>
										<option @isset($ordem) @if($ordem == 'desc') selected @endif @endif value="desc">Mais recente</option>
										<option @isset($ordem) @if($ordem == 'asc') selected @endif @endif value="asc">Mais antigo</option>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-4 col-12">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa cliente" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
									<div class="input-group-prepend"><span class="input-group-text"><i class="la la-birthday-cake"></i></span></div>
									<div class="input-group-append">
										<span class="input-group-text">
											<label class="checkbox checkbox-inline checkbox-info">
												<input type="checkbox" name="aniversariante" @isset($aniversariante) @if($aniversariante == true) checked @endif @endif/>
												<span></span>
											</label>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-2 col-12">
						<div class="row align-items-center">
							<div class="col-md-12 my-2 my-md-0">
								<div class="input-group">
									<input type="text" name="cpf_cnpj" class="form-control cpf_cnpj" placeholder="Pesquisa CPF/CNPJ" value="{{{ isset($cpf_cnpj) ? $cpf_cnpj : ''}}}">
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>
			<h4>Lista de Clientes</h4>

			@isset($paraImprimir)
			<form method="get" action="/clientes/relatorio">
				<input type="hidden" name="pesquisa" value="{{{ isset($pesquisa) ? $pesquisa : '' }}}">
				<input type="hidden" name="tipo_pesquisa" value="{{{ isset($tipoPesquisa) ? $tipoPesquisa : '' }}}">
				<input type="hidden" name="aniversariante" value="{{ $aniversariante }}">
				<button style="margin-left: 5px; margin-top: 5px;" class="btn btn-info"><i class="fa fa-print"></i>Imprimir relatório</button>
			</form>
			@endisset

			@if(isset($totalGeralClientes))
			<label>Total de clientes cadastrados: <strong class="text-info">{{$totalGeralClientes}}</strong></label>
			@endif

			<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
				<div class="wizard-nav">
					<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
						<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
							<div class="wizard-label">
								<h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-table"></i> Tabela</span></h3>
								<div class="wizard-bar"></div>
							</div>
						</div>
						<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
							<div class="wizard-label" id="grade">
								<h3 class="wizard-title"><span><i style="font-size: 40px" class="la la-tablet"></i> Grade</span></h3>
								<div class="wizard-bar"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="pb-5" data-wizard-type="step-content">
					<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
						<table class="datatable-table" style="max-width: 100%; overflow: scroll">
							<thead class="datatable-head">
								<tr class="datatable-row">
									<th class="datatable-cell"><span style="width: 320px;">AÇÕES</span></th>
									<th class="datatable-cell"><span style="width: 250px;">RAZÃO SOCIAL</span></th>
									<th class="datatable-cell"><span style="width: 150px;">FANTASIA</span></th>
									<th class="datatable-cell"><span style="width: 150px;">CPF/CNPJ</span></th>
									<th class="datatable-cell"><span style="width: 100px;">IE/RG</span></th>
									<th class="datatable-cell"><span style="width: 200px;">CIDADE</span></th>
									<th class="datatable-cell"><span style="width: 120px;">TELEFONE</span></th>
									<th class="datatable-cell"><span style="width: 100px;">CASHBACK</span></th>
									<th class="datatable-cell"><span style="width: 100px;">CADASTRO</span></th>
								</tr>
							</thead>
							<tbody id="body" class="datatable-body">
								@foreach($clientes as $c)
								<tr class="datatable-row">
									<td class="datatable-cell">
										<span style="width: 320px;">
											<a class="btn btn-primary btn-sm" title="Consultar Receitas" onclick="verReceitas({{ $c->id }})" href="#!">
												<i class="la la-eye"></i>	
											</a>
											<a class="btn btn-info btn-sm" title="Histórico Unificado" onclick="abrirHistoricoCliente({{ $c->id }})" href="#!">
												<i class="la la-history"></i>	
											</a>
											<a class="btn btn-warning btn-sm" onclick='swal("Editar?", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/clientes/edit/{{ $c->id }}" } })' href="#!">
												<i class="la la-edit"></i>	
											</a>
											<a class="btn btn-danger btn-sm" onclick='swal("Remover?", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/clientes/delete/{{ $c->id }}" } })' href="#!">
												<i class="la la-trash"></i>	
											</a>
											@if($c->celular)
											<a class="btn btn-success btn-sm" href="#!" onclick="whatsAppClick('{{$c->celular}}')"><i class="la la-whatsapp"></i></a>
											@endif
											@if(sizeof($c->cashBacks) > 0)
											<a title="CashBack" class="btn btn-dark btn-sm" href="/clientes/cashBacks/{{ $c->id }}"><i class="la la-money"></i></a>
											@endif
											<a title="Documentos" class="btn btn-info btn-sm" href="/clientes/upload/{{ $c->id }}"><i class="la la-paperclip"></i></a>
										</span>
									</td>
									<td class="datatable-cell"><span style="width: 250px;">{{$c->razao_social}}</span></td>
									<td class="datatable-cell"><span style="width: 150px;">{{$c->nome_fantasia}}</span></td>
									<td class="datatable-cell"><span style="width: 150px;">{{$c->cpf_cnpj}}</span></td>
									<td class="datatable-cell"><span style="width: 100px;">{{$c->ie_rg}}</span></td>
									<td class="datatable-cell"><span style="width: 200px;">{{$c->cidade->nome}} ({{$c->cidade->uf}})</span></td>
									<td class="datatable-cell"><span style="width: 120px;">{{$c->telefone}}</span></td>
									<td class="datatable-cell"><span style="width: 100px;">{{ moeda($c->valor_cashback) }}</span></td>
									<td class="datatable-cell"><span style="width: 100px;">{{\carbon\carbon::parse($c->created_at)->format('d/m/Y')}}</span></td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<div class="pb-5" data-wizard-type="step-content">
					<div class="row">
						@foreach($clientes as $c)
						<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6">
							<div class="card card-custom gutter-b example example-compact">
								<div class="card-header">
									<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
										<div class="symbol symbol-circle symbol-lg-75 mt-4">
											@if($c->imagem != "" && file_exists(public_path('imgs_clientes/').$c->imagem))
											<img src="/imgs_clientes/{{ $c->imagem }}" alt="image">
											@else
											<img src="/foto_usuario/user.png" alt="image">
											@endif
										</div>
									</div>
									<div class="card-title">
										<h3 style="width: 230px; font-size: 12px;" class="card-title">{{substr($c->razao_social, 0, 30)}}</h3>
									</div>
									<div class="card-toolbar">
										<div class="dropdown dropdown-inline">
											<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown">
												<i class="fa fa-ellipsis-h"></i>
											</a>
											<div class="dropdown-menu dropdown-menu-md dropdown-menu-left">
												<ul class="navi navi-hover">
													<li class="navi-header font-weight-bold py-4"><span class="font-size-lg">Ações:</span></li>
													<li class="navi-separator mb-3 opacity-70"></li>
													
													<li class="navi-item">
														<a onclick="verReceitas({{ $c->id }})" href="#!" class="navi-link">
															<span class="navi-icon"><i class="la la-eye text-primary"></i></span>
															<span class="navi-text">Receitas Óticas</span>
														</a>
													</li>
													<li class="navi-item">
														<a onclick="abrirHistoricoCliente({{ $c->id }})" href="#!" class="navi-link">
															<span class="navi-icon"><i class="la la-history text-info"></i></span>
															<span class="navi-text">Histórico Financeiro</span>
														</a>
													</li>

													<li class="navi-item">
														<a href="/clientes/edit/{{$c->id}}" class="navi-link">
															<span class="navi-text"><span class="label label-xl label-inline label-light-primary">Editar</span></span>
														</a>
													</li>
													<li class="navi-item">
														<a onclick='swal("Excluir?", "Deseja remover?", "warning").then((sim) => {if(sim){ location.href="/clientes/delete/{{ $c->id }}" }})' href="#!" class="navi-link">
															<span class="navi-text"><span class="label label-xl label-inline label-light-danger">Excluir</span></span>
														</a>
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>
								<div class="card-body">
									<div class="kt-widget__info"><span class="kt-widget__label">Nome fantasia:</span><a class="kt-widget__data text-success">{{ $c->nome_fantasia }}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">CNPJ/CPF:</span><a class="kt-widget__data text-success">{{ $c->cpf_cnpj }}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">IE/RG:</span><a class="kt-widget__data text-success">{{$c->ie_rg}}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">Cidade:</span><a class="kt-widget__data text-success">{{$c->cidade->nome}} ({{$c->cidade->uf}})</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">Data cadastro:</span><a class="kt-widget__data text-success">{{\carbon\carbon::parse($c->created_at)->format('d/m')}}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">Data nascimento:</span><a class="kt-widget__data text-success">{{ $c->data_nascimento ?? '--' }}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">UF:</span><a class="kt-widget__data text-success">{{$c->cidade->uf}}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">Telefone:</span><a class="kt-widget__data text-success">{{$c->telefone}}</a></div>
									<div class="kt-widget__info"><span class="kt-widget__label">Email:</span><a class="kt-widget__data text-success">{{$c->email}}</a></div>
								</div>
							</div>
						</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>

		<div class="d-flex justify-content-between align-items-center flex-wrap">
			<div class="d-flex flex-wrap py-2 mr-3">@if(isset($links)) {{$clientes->links()}} @endif</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_historico_cliente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">Histórico Financeiro: <span id="nome_cliente_hist"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Nº NF / Ref.</th> 
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Pago/Utiliz.</th>
                                <th>Saldo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_historico_corpo"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_receitas_cliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"> <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Histórico de Receitas Óticas</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Médico</th>
                                <th>Lente</th>
                                <th>Armação</th>
                                <th>Referência</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_receitas_corpo"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function whatsAppClick(fone){
		fone = fone.replace(/[^0-9]/g,'');
		window.open("https://wa.me/55"+fone+"?text=Olá")
	}

    // FUNÇÃO PARA BUSCAR E MOSTRAR RECEITAS
    function verReceitas(id){
        $('#tabela_receitas_corpo').html('<tr><td colspan="6" class="text-center py-5">Buscando receitas...</td></tr>');
        $('#modal_receitas_cliente').modal('show');
        
        $.get('/clientes/receitas/' + id, function(data){
            let html = '';
            if(data.length == 0){
                html = '<tr><td colspan="6" class="text-center py-5">Nenhuma receita encontrada.</td></tr>';
            } else {
                data.forEach(r => {
                    html += `<tr>
                        <td>${r.data}</td>
                        <td>${r.medico ?? '--'}</td>
                        <td>${r.lente ?? '--'}</td>
                        <td>${r.armacao ?? '--'}</td>
                        <td>${r.referencia ?? '--'}</td>
                        <td>
                            <a href="/clientes/imprimirReceita/${r.id}" target="_blank" class="btn btn-sm btn-light-primary" title="Imprimir">
                                <i class="la la-print"></i>
                            </a>
                        </td>
                    </tr>`;
                });
            }
            $('#tabela_receitas_corpo').html(html);
        });
    }

    // FUNÇÃO PARA BUSCAR E MOSTRAR HISTÓRICO FINANCEIRO
    function abrirHistoricoCliente(id){
        $('#tabela_historico_corpo').html('<tr><td colspan="8" class="text-center py-5">Buscando financeiro...</td></tr>');
        $('#modal_historico_cliente').modal('show');
        
        $.get('/clientes/historico/' + id, function(data){
            $('#nome_cliente_hist').text(data.cliente);
            let html = '';
            if(data.historico.length == 0){
                html = '<tr><td colspan="8" class="text-center py-5 text-muted">Sem movimentações financeiras.</td></tr>';
            } else {
                data.historico.forEach(h => {
                    let badge = h.status == 'Pago' || h.status == 'Utilizado' ? 'badge-success' : 'badge-warning';
                    let corSaldo = h.saldo != '0,00' ? 'text-danger' : 'text-success';

                    html += `<tr>
                        <td>${h.data}</td>
                        <td><strong>${h.tipo}</strong></td>
                        <td class="font-weight-bold">${h.nf}</td> 
                        <td>${h.vencimento}</td>
                        <td>R$ ${h.valor}</td>
                        <td class="text-success">R$ ${h.pago}</td>
                        <td class="${corSaldo} font-weight-bold">R$ ${h.saldo}</td>
                        <td><span class="badge ${badge} badge-inline">${h.status}</span></td>
                    </tr>`;
                });
            }
            $('#tabela_historico_corpo').html(html);
        }).fail(function(){
            swal("Erro", "Não foi possível carregar o histórico.", "error");
        });
    }
</script>
@endsection