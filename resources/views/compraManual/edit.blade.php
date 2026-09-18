@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover{ cursor: pointer }
	.search-prod{
		position: absolute; top: 0; margin-top: 40px; left: 10px; width: 100%;
		max-height: 200px; overflow: auto; z-index: 9999; border: 1px solid #eeeeee;
		border-radius: 4px; background-color: #fff; box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.15);
	}
	.search-prod label:hover{ cursor: pointer; background-color: #f3f6f9; color: #3699ff; }
	.search-prod label{ margin-left: 0; width: 100%; padding: 8px 12px; font-size: 13px; color: #3f4254 !important; transition: all 0.2s; }
    
    /* Custom Modern Design UI */
    .summary-box { background: #ffffff; border: 1px solid #ebedf3; border-radius: 0.75rem; box-shadow: 0px 0px 20px 0px rgba(0,0,0,0.03); }
    .table-fatura th { background-color: #f3f6f9; color: #3f4254; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.6px; }
    .nav-tabs-custom .nav-link.active { border-bottom: 3px solid #3699ff !important; color: #3699ff !important; font-weight: 700; }
    .form-control-solid { background-color: #f3f6f9; border-color: #f3f6f9; color: #3f4254; transition: all 0.2s; }
    .form-control-solid:focus { background-color: #ebedf3; border-color: #3699ff; }

    /* Fix para botoes de acao na tabela de produtos */
    .btn-editar { display: inline-block !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto; z-index: 100; }
    .datatable-prod button { pointer-events: auto; z-index: 100; margin-right: 5px; }
</style>

<div class="row" id="anime" style="display: none">
	<div class="col s8 offset-s2">
		<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay></lottie-player>
	</div>
</div>

<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__bounce" id="content" style="display: block">
	<div class="d-flex flex-column flex-column-fluid" id="kt_content">
		<div class="card card-custom gutter-b summary-box">
			
            <!-- SEÇÃO DADOS INICIAIS -->
            <div class="card-header d-flex justify-content-between align-items-center border-0 pt-5">
                <h3 class="card-title font-weight-bolder text-warning"><i class="la la-edit text-warning icon-xl mr-2"></i> EDITAR COMPRA MANUAL #{{ $compra->id }}</h3>
                <button type="button" class="btn btn-light-info font-weight-bold shadow-sm" data-toggle="modal" data-target="#modal-ajuda">
                    <i class="la la-question-circle"></i> Como emitir a nota?
                </button>
                {!! __view_locais_select() !!}
            </div>

			<div class="row justify-content-center px-8 pb-5">
                <div class="col-xl-12">
                    <div class="row bg-light-secondary p-5 rounded-card" style="border-radius: 0.75rem;">
                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Data Retroativa</label>
                            <div class="input-group date">
                                <input type="text" name="data_retroativa" class="form-control date-input form-control-solid" value="{{ isset($compra->data_retroativa) ? \Carbon\Carbon::parse($compra->data_retroativa)->format('d/m/Y') : '' }}" id="data_retroativa_dynamic" />
                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Data Saída</label>
                            <div class="input-group date">
                                <input type="text" name="data_saida" class="form-control date-input form-control-solid" value="{{ isset($compra->data_saida) ? \Carbon\Carbon::parse($compra->data_saida)->format('d/m/Y') : '' }}" id="data_saida_dynamic" />
                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                            </div>
                        </div>

                        <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Nota Fiscal</label>
                            <input type="text" class="form-control form-control-solid" id="nf" name="nf" value="{{ $compra->nf ?? '' }}" placeholder="Ex: 000000">
                        </div>

                        <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Emissão da NF</label>
                            <input type="date" class="form-control form-control-solid" id="data_emissao" name="data_emissao" value="{{ (isset($compra) && $compra->data_emissao) ? \Carbon\Carbon::parse($compra->data_emissao)->format('Y-m-d') : date('Y-m-d') }}">
                        </div>

                        <div class="form-group validated col-lg-4 col-md-8 col-sm-12">
                            <label class="col-form-label font-weight-bold">Veículos Utilizados</label>
                            <select class="form-control select2 form-control-solid" id="veiculos_ids" name="veiculos_ids[]" multiple="multiple">
                                @if(isset($veiculos) && count($veiculos) > 0)
                                    @foreach($veiculos as $v)
                                        <option value="{{ $v->id }}" {{ (isset($compra) && $compra->veiculo_id == $v->id) ? 'selected' : '' }}>
                                            {{ $v->placa }} - {{ $v->marca }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NAVEGAÇÃO DOS PASSOS (WIZARD) -->
			<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
				<div class="wizard-nav border-bottom-0">
					<div class="wizard-steps px-8 py-3 px-lg-15 py-lg-3 nav-tabs-custom">
						<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
							<div class="wizard-label">
								<h3 class="wizard-title"><span>1.</span>ITENS DA NOTA</h3>
								<div class="wizard-bar"></div>
							</div>
						</div>
						<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
							<div class="wizard-label">
								<h3 class="wizard-title"><span>2.</span>TRANSPORTE / FRETE</h3>
								<div class="wizard-bar"></div>
							</div>
						</div>
						<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
							<div class="wizard-label">
								<h3 class="wizard-title"><span>3.</span>PAGAMENTO / FATURAMENTO</h3>
								<div class="wizard-bar"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="row justify-content-center py-5 px-8 px-lg-10">
					<div class="col-xl-12">
						<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
							
                            <!-- PASSO 1: FORNECEDOR E PRODUTOS -->
                            <div class="pb-5" data-wizard-type="step-content">
								<h4 class="mb-5 font-weight-bolder text-dark">Identificação do Fornecedor</h4>
								<div class="row align-items-center">
									<div class="input-group col-lg-8 col-md-10 col-sm-12">
										<select class="form-control select2 fornecedor" id="kt_select2_1" name="fornecedor">
											<option value="--">Selecione o fornecedor</option>
											@foreach($fornecedores as $f)
											    <option value="{{$f->id}}" {{ $compra->fornecedor_id == $f->id ? 'selected' : '' }}>
                                                    {{$f->razao_social}} - {{$f->nome_fantasia}} ({{$f->cpf_cnpj}})
                                                </option>
											@endforeach
										</select>
										<button type="button" onclick="novoFornecedor()" class="btn btn-warning btn-sm shadow-sm"><i class="la la-plus-circle icon-add"></i></button>
									</div>
								</div>

                                <!-- SOLUÇÃO: BLOCO DO FORNECEDOR RESTAURADO -->
                                <div class="row" id="fornecedor" style="display: none">
                                    <div class="row col-12 mt-4 bg-light p-4 rounded m-0 shadow-sm" style="border-left: 4px solid #3699ff;">
                                        <div class="col-sm-6 col-lg-6">
                                            <h6>Razão Social: <strong id="razao_social" class="text-dark">--</strong></h6>
                                            <h6>Nome Fantasia: <strong id="nome_fantasia" class="text-dark">--</strong></h6>
                                            <h6>Logradouro: <strong id="logradouro" class="text-dark">--</strong></h6>
                                            <h6>Número: <strong id="numero" class="text-dark">--</strong></h6>
                                        </div>
                                        <div class="col-sm-6 col-lg-6">
                                            <h6>CPF/CNPJ: <strong id="cnpj" class="text-dark">--</strong></h6>
                                            <h6>RG/IE: <strong id="ie" class="text-dark">--</strong></h6>
                                            <h6>Fone: <strong id="fone" class="text-dark">--</strong></h6>
                                            <h6>Cidade/UF: <strong id="cidade" class="text-dark">--</strong></h6>
                                        </div>
                                    </div>
                                </div>

								<div class="row mt-4" id="div_adiantamento" style="display:none;">
									<div class="col-lg-8"> 
										<div class="alert alert-custom alert-outline-info fade show mb-0" role="alert" style="background: #f4f6fa; border: 1px solid #17a2b8; border-radius: 0.5rem;">
											<div class="alert-icon"><i class="flaticon-questions-wheels-and-self-care text-info"></i></div>
											<div class="alert-text">
												<span class="font-weight-bold text-dark">Crédito de Adiantamento Disponível: </span>
												<span class="label label-lg label-light-info label-inline font-weight-bolder" id="label_saldo_adv" style="font-size: 1.1rem;">R$ 0,00</span>
												<div class="checkbox-inline mt-2">
													<label class="checkbox checkbox-success font-weight-bold">
														<input type="checkbox" name="usar_adiantamento" id="usar_adiantamento">
														<span></span>
														<strong class="text-dark-75">Deseja abater automaticamente o saldo neste lançamento?</strong>
													</label>
												</div>
											</div>
										</div>
									</div>
								</div>

								<hr class="my-8 opacity-10">

								<h4 class="mb-5 font-weight-bolder text-dark">Inserção de Itens</h4>
								<div class="row align-items-end bg-light-primary p-4 rounded m-0 shadow-sm">
									<div class="form-group validated col-sm-4 col-lg-4 mb-0">
										<label class="font-weight-bold text-dark-75">Buscar Produto</label>
										<div class="input-group">
											<input placeholder="Digite o nome para pesquisar..." type="search" id="produto-search" class="form-control">
											<div class="search-prod" style="display: none"></div>
											<button type="button" onclick="novoProduto()" class="btn btn-info btn-sm"><i class="la la-plus-circle icon-add"></i></button>
										</div>
									</div>
									<div class="form-group validated col-sm-2 col-lg-2 mb-0">
										<label class="font-weight-bold text-dark-75">Quantidade</label>
										<input type="text" class="form-control text-center" name="quantidade" id="quantidade">
									</div>
									<div class="form-group validated col-sm-2 col-lg-2 mb-0">
										<label class="font-weight-bold text-dark-75">Valor Unitário</label>
										<input type="text" class="form-control text-right money" name="valor" value="0" id="valor">
									</div>
									<div class="form-group validated col-sm-2 col-lg-2 mb-0">
										<label class="font-weight-bold text-dark-75">SubTotal</label>
										<input type="text" class="form-control text-right font-weight-bolder text-success" id="subtotal" value="0" disabled>
									</div>
									<div class="form-group validated col-sm-2 col-lg-2 mb-0">
										<button type="button" id="addProd" class="btn btn-success font-weight-bold text-uppercase px-9 py-3 w-100 shadow-sm"><i class="la la-plus"></i> Inserir</button>
									</div>
								</div>

                                <!-- SOLUÇÃO: TABELA LIMPA PARA O JS DO SISTEMA PREENCHER SOZINHO -->
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded prod mt-5 shadow-sm">
									<table class="datatable-table" style="max-width: 100%;overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row">
												<th class="datatable-cell" style="width: 50px;">#</th>
												<th class="datatable-cell" style="width: 80px;">Código</th>
												<th class="datatable-cell" style="width: 320px;">Descrição do Produto</th>
												<th class="datatable-cell" style="width: 120px;">Valor Un.</th>
												<th class="datatable-cell" style="width: 100px;">Qtd</th>
												<th class="datatable-cell" style="width: 120px;">Subtotal</th>
												<th class="datatable-cell text-center" style="width: 80px;">Ações</th>
											</tr>
										</thead>
										<tbody class="datatable-body">
                                            <!-- O JS global 'compra.js' vai ler o hidden #itens e injetar as linhas aqui -->
                                        </tbody>
									</table>
								</div>
							</div>

                            <!-- PASSO 2: TRANSPORTADORA E LOGÍSTICA -->
							<div class="pb-5" data-wizard-type="step-content" >
								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12 p-0">
									<h4 class="mb-5 font-weight-bolder text-dark">Dados de Transporte</h4>
									<div class="row align-items-center mb-6">
										<div class="form-group validated col-lg-6 col-md-8 col-sm-12">
                                            <label class="font-weight-bold text-dark-75">Transportadora (Opcional)</label>
											<div class="input-group">
												<select class="form-control select2" style="width: 85%" id="kt_select2_3" name="transportadora">
													<option value="null">Selecione a transportadora (opcional)</option>
													@foreach($transportadoras as $t)
													    <option value="{{$t->id}}" {{ $compra->transportadora_id == $t->id ? 'selected' : '' }}>{{$t->id}} - {{$t->razao_social}}</option>
													@endforeach
												</select>
												<button type="button" onclick="novaTransportadora()" class="btn btn-warning btn-sm shadow-sm"><i class="la la-plus-circle icon-add"></i></button>
											</div>
										</div>
									</div>
									<hr class="my-6 opacity-10">

									<h4 class="mb-5 font-weight-bolder text-dark">Informações do Frete</h4>
									<div class="row align-items-center">
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="font-weight-bold text-dark-75">Modalidade do Frete</label>
											<select class="custom-select form-control form-control-solid" id="frete" name="frete">
												<option @if($compra->tipo == '0') selected @endif value="0">0 - Emitente</option>
												<option @if($compra->tipo == '1') selected @endif value="1">1 - Destinatário</option>
												<option @if($compra->tipo == '2') selected @endif value="2">2 - Terceiros</option>
												<option @if($compra->tipo == '9') selected @endif value="9">9 - Sem Frete</option>
											</select>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Placa do Veículo</label>
											<input type="text" name="placa" class="form-control form-control-solid text-uppercase" value="{{ $compra->placa }}" id="placa"/>
										</div>
										<div class="form-group validated col-sm-2 col-lg-2 col-6">
											<label class="font-weight-bold text-dark-75">UF Placa</label>
											<select class="custom-select form-control form-control-solid" id="uf_placa" name="uf_placa">
												<option value="--">--</option>
												@foreach(\App\Models\Cidade::estados() as $uf)
                                                    <option value="{{$uf}}" {{ $compra->uf == $uf ? 'selected' : '' }}>{{$uf}}</option>
                                                @endforeach
											</select>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Valor do Frete</label>
											<input type="text" name="valor_frete" class="form-control form-control-solid money text-right" value="{{ number_format($compra->valor_frete, 2, ',', '.') }}" id="valor_frete"/>
										</div>
									</div>
									<hr class="my-6 opacity-10">

									<h4 class="mb-5 font-weight-bolder text-dark">Volumes e Pesos</h4>
									<div class="row align-items-center">
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Espécie</label>
											<input type="text" name="especie" class="form-control form-control-solid" value="{{ $compra->especie ?? 'VOLUMES' }}" id="especie"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Numeração Vol.</label>
											<input type="text" name="numeracaoVol" class="form-control form-control-solid text-center" value="{{ $compra->numeracaoVolumes ?? '0' }}" id="numeracaoVol"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Qtd. Volumes</label>
											<input type="text" name="qtdVol" class="form-control form-control-solid text-center" value="{{ $compra->qtdVolumes ?? '0' }}" id="qtdVol"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Peso Líquido</label>
											<input type="text" name="pesoL" class="form-control form-control-solid text-right" value="{{ number_format($compra->peso_liquido, 2, ',', '.') }}" id="pesoL"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-4 col-6">
											<label class="font-weight-bold text-dark-75">Peso Bruto</label>
											<input type="text" name="pesoB" class="form-control form-control-solid text-right" value="{{ number_format($compra->peso_bruto, 2, ',', '.') }}" id="pesoB"/>
										</div>
									</div>
								</div>
							</div>

                            <!-- PASSO 3: CONDICIONAL DE PAGAMENTO COM CONTA POR LINHA (ESTILO DFE) -->
							<div class="pb-5" data-wizard-type="step-content" data-wizard-state="current">
                                <h4 class="mb-6 font-weight-bolder text-dark">Estrutura de Faturamento Financeiro</h4>
                                
                                <div class="row mb-6 bg-light p-5 rounded m-0 shadow-sm border">
                                    <div class="form-group col-lg-4 mb-0">
                                        <label class="font-weight-bold text-dark-75">Condição Comercial</label>
                                        <select id="tipo_condicao" class="custom-select form-control">
                                            <option value="prazo">Montar Parcelamento Manual (A Prazo)</option>
                                            <option value="vista">Forçar À Vista (Gera 1 Parcela Hoje)</option>
                                            <option value="rateio">Ratear Parcela Única por Veículos</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group col-lg-5 id-div-rateio mb-0" style="display:none;">
                                        <label class="font-weight-bold text-success">Selecione os Veículos para o Rateio das Parcelas</label>
                                        <select id="veiculos_rateio" class="form-control select2" multiple="multiple" style="width: 100%">
                                            @if(isset($veiculos))
                                                @foreach($veiculos as $v)
                                                    <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->marca }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-2 div-gerador mb-0">
                                        <label class="font-weight-bold text-dark-75">Nº de Parcelas</label>
                                        <input type="number" id="qtd_parcelas_manual" class="form-control text-center" value="1" min="1">
                                    </div>
                                    <div class="form-group col-lg-1 div-gerador mb-0 text-right align-self-end">
                                        <button type="button" id="btn_gerar_parcelas" class="btn btn-primary font-weight-bold btn-block shadow-sm">Gerar</button>
                                    </div>
                                </div>

                                <div class="row mt-5">
                                    <div class="form-group validated col-12 mb-0">
                                        <div class="table-responsive shadow-sm rounded border">
                                            <table class="table table-bordered table-striped table-hover table-fatura m-0" id="tabela-fatura">
                                                <thead>
                                                    <tr>
                                                        <th width="90" class="text-center">Parcela</th>
                                                        <th width="140" class="text-center">Vencimento</th>
                                                        <th width="160" class="text-right">Valor da Parcela</th>
                                                        <th width="180">Forma Pagamento</th>
                                                        <th width="240" class="text-info"><i class="la la-university text-info"></i> Conta / Caixa (Baixa Automática)</th>
                                                        <th width="160">Veículo Alocado</th>
                                                        <th width="60" class="text-center">Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(isset($fatura) && count($fatura) > 0)
                                                        @foreach($fatura as $index => $fat)
                                                            <tr>
                                                                <td><input type="text" name="fatura_num[]" class="form-control form-control-sm text-center font-weight-bold" value="{{ str_pad($index + 1, 3, '0', STR_PAD_LEFT) }}"></td>
                                                                <td><input type="text" name="fatura_venc[]" class="form-control form-control-sm date-input text-center" value="{{ \Carbon\Carbon::parse($fat['data_vencimento'])->format('d/m/Y') }}"></td>
                                                                <td><input type="text" name="fatura_val[]" class="form-control form-control-sm money text-right font-weight-bold text-success" value="{{ number_format($fat['valor_integral'], 2, ',', '.') }}"></td>
                                                                <td>
                                                                    <select name="forma_pagamento[]" class="custom-select custom-select-sm">
                                                                        <option value="boleto" {{ $fat['tipo_pagamento'] == 'boleto' ? 'selected' : '' }}>Boleto</option>
                                                                        <option value="pix" {{ $fat['tipo_pagamento'] == 'pix' ? 'selected' : '' }}>Pix</option>
                                                                        <option value="dinheiro" {{ $fat['tipo_pagamento'] == 'dinheiro' ? 'selected' : '' }}>Dinheiro (Caixa)</option>
                                                                        <option value="transferencia" {{ $fat['tipo_pagamento'] == 'transferencia' ? 'selected' : '' }}>Transferência</option>
                                                                        <option value="adiantamento" {{ $fat['tipo_pagamento'] == 'adiantamento' ? 'selected' : '' }}>Adiantamento</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select name="fatura_conta[]" class="custom-select custom-select-sm border-info">
                                                                        <option value="">-- Nenhuma (A Prazo) --</option>
                                                                        @if(isset($contasEmpresa))
                                                                            @foreach($contasEmpresa as $c)
                                                                                <option value="{{$c->id}}" {{ isset($fat['conta_empresa_id']) && $fat['conta_empresa_id'] == $c->id ? 'selected' : '' }}>{{$c->nome}}</option>
                                                                            @endforeach
                                                                        @endif
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select name="fatura_veiculo[]" class="custom-select custom-select-sm">
                                                                        <option value="">-- Geral --</option>
                                                                        @foreach($veiculos as $v)
                                                                            <option value="{{$v->id}}" {{ isset($fat['veiculo_id']) && $fat['veiculo_id'] == $v->id ? 'selected' : '' }}>{{$v->placa}}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button>
                                                                    <input type="hidden" name="fatura_db_id[]" value="{{ $fat['id'] }}">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-right mt-3">
                                            <button type="button" id="btn-adicionar-linha-fatura" class="btn btn-sm btn-light-primary font-weight-bold">
                                                <i class="la la-plus"></i> Adicionar Nova Linha
                                            </button>
                                        </div>
                                    </div>
                                </div>
							</div>
						</form>

                        <!-- BLOCO CENTRALIZADO DE RESUMO FINANCEIRO E FINALIZAÇÃO DO LOG -->
                        <div class="card card-custom bg-light-primary border-primary mt-8 mb-4 shadow-sm" style="border: 1px solid #3699ff; border-radius: 0.75rem;">
                            <div class="card-header border-0 pb-0 pt-5">
                                <h3 class="card-title font-weight-bolder text-primary"><i class="la la-calculator text-primary mr-2"></i> Fechamento e Resumo Financeiro</h3>
                                <div class="card-toolbar">
                                    <h3 class="font-weight-bolder text-dark mb-0">VALOR TOTAL NF: <span id="total" class="text-success font-size-h1 ml-2">R$ {{ number_format($compra->valor, 2, ',', '.') }}</span></h3>
                                </div>
                            </div>
                            <div class="card-body pt-3 pb-5">
                                <div class="row align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="row">
                                            <div class="form-group col-6 mb-2">
                                                <label class="font-weight-bold text-dark-75">Desconto (R$)</label>
                                                <input type="text" class="form-control form-control-sm money" id="desconto" value="{{ number_format($compra->desconto, 2, ',', '.') }}" placeholder="0,00">
                                            </div>
                                            <div class="form-group col-6 mb-2">
                                                <label class="font-weight-bold text-dark-75">Acréscimo (R$)</label>
                                                <input type="text" class="form-control form-control-sm money" id="acrescimo" value="{{ number_format($compra->acrescimo, 2, ',', '.') }}" placeholder="0,00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-lg-5 col-md-6 mb-2">
                                        <label class="font-weight-bold text-dark-75">Observação Interna / Histórico</label>
                                        <input type="text" class="form-control form-control-sm" id="obs" value="{{ $compra->observacao }}" placeholder="Escreva observações ou observações adicionais da NFe...">
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 mb-2">
                                        <label class="font-weight-bold text-danger">Categoria da Conta (Financeiro) *</label>
                                        <select class="custom-select form-control form-control-sm border-danger font-weight-bold" id="categoria_conta_id" name="categoria_conta_id" required>
                                            <option value="">-- Selecione --</option>
                                            @foreach($categoriasDeConta as $c)
                                                <option value="{{$c->id}}" {{ $compra->categoria_conta_id == $c->id ? 'selected' : '' }}>{{$c->nome}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 mb-2">
                                        <label class="font-weight-bold text-dark-75">Nº Controle Lote</label>
                                        <input type="text" class="form-control form-control-sm text-center" id="lote" value="{{ $compra->lote }}" placeholder="Opcional">
                                    </div>

                                    <div class="form-group col-12 mt-4 text-right mb-0">
                                        <!-- BOTÃO DE ATUALIZAR -->
                                        <button type="button" class="btn btn-warning font-weight-bolder text-uppercase px-15 py-3 shadow" id="salvar-venda" onclick="atualizarCompra()">
                                            <i class="la la-sync icon-lg"></i> Atualizar Compra
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIM DO BLOCO DE RESUMO -->

					</div>
				</div>
			</div>
            
            <!-- CAMPOS OCULTOS OBRIGATÓRIOS PARA A EDIÇÃO FUNCIONAR -->
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
            <input type="hidden" id="itens" value="{{json_encode($compra->itens)}}">
            <input type="hidden" id="fatura" value="{{json_encode($fatura)}}">
            <input type="hidden" id="compra_id" value="{{ $compra->id }}">
            
            <!-- SOLUÇÃO: TABELA FANTASMA PARA EVITAR CRASH NO SCRIPT DO SISTEMA -->
            <div class="fatura" style="display:none"><table class="datatable-table"><tbody class="datatable-body"></tbody></table></div>
		</div>
	</div>
</div>

<!-- ==============================================
     MODAIS DO SISTEMA 
=============================================== -->

<!-- SOLUÇÃO: MODAL DE EDITAR ITEM RESTAURADO COM O HTML EXATO QUE O SISTEMA ESPERA -->
<div class="modal fade" id="modal-edit-item" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alterar item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Número item do pedido (não editável) -->
                    <div class="form-group col-12 col-lg-4">
                        <label class="col-form-label">Nº item do pedido</label>
                        <input type="text" id="id_item" name="id_item" class="form-control" readonly>
                    </div>

                    <!-- Código do produto (não editável) -->
                    <div class="form-group col-12 col-lg-4">
                        <label class="col-form-label">Código do Produto</label>
                        <input type="text" id="codigo_produto" name="codigo_produto" class="form-control" readonly>
                    </div>

                    <!-- Descrição do produto (não editável) -->
                    <div class="form-group col-12 col-lg-4">
                        <label class="col-form-label">Descrição</label>
                        <input type="text" id="produto_nome" name="produto_nome" class="form-control" readonly>
                    </div>

                    <!-- Quantidade (editável) -->
                    <div class="form-group col-12 col-lg-6">
                        <label class="col-form-label">Quantidade</label>
                        <input type="text" id="qtd_item" name="qtd_item" class="form-control qtd-p" value="">
                    </div>

                    <!-- Valor unitário (editável) -->
                    <div class="form-group col-12 col-lg-6">
                        <label class="col-form-label">Valor unitário</label>
                        <input type="text" id="vl_item" name="vl_item" class="form-control money" value="">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                <button type="button" id="salvar-edit" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-fornecedor" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Fornecedor</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-xl-12">
						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success"><input name="group1" type="radio" id="pessoaFisica"/><span></span>FISICA</label>
									<label class="radio radio-success"><input name="group1" type="radio" id="pessoaJuridica"/><span></span>JURIDICA</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
								<div class=""><input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj"></div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label text-left col-lg-12 col-sm-12">UF</label>
								<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c) <option value="{{$c}}">{{$c}}</option> @endforeach
								</select>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
									<span><i class="fa fa-search"></i></span>
								</a>
							</div>
						</div>
						<div class="row">
							<div class="form-group validated col-sm-6">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class=""><input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-6">
								<label class="col-form-label">Nome Fantasia</label>
								<div class=""><input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_ie_rg">RG</label>
								<div class=""><input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
								<label class="col-form-label">Contribuinte</label>
								<select class="custom-select form-control" id="contribuinte">
									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>
							</div>
						</div>
						<hr>
						<h5>Endereço</h5>
						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-8">
								<label class="col-form-label">Rua</label>
								<div class=""><input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-2 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class=""><input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-8 col-lg-5">
								<label class="col-form-label">Bairro</label>
								<div class=""><input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">CEP</label>
								<div class=""><input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Email</label>
								<div class=""><input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif"></div>
							</div>
							@php $cidade = App\Models\Cidade::getCidadeCod($config->codMun); @endphp
							<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_4">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
									@endforeach
								</select>
							</div>
							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class=""><input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif"></div>
							</div>
							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Celular (Opcional)</label>
								<div class=""><input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarFornecedor()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-ajuda" tabindex="-1" role="dialog" aria-labelledby="modalAjudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white" id="modalAjudaLabel"><i class="la la-info-circle text-white mr-2"></i> Instruções: Compra Manual e Emissão de NF-e</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">x</button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="font-weight-bold text-primary">Edição de Notas e Faturas</h6>
                    <ul class="text-dark-75 mb-0">
                        <li>Ao remover parcelas da tabela de faturamento, elas serão excluídas do financeiro quando clicar em "Atualizar".</li>
                        <li>Verifique os itens marcados na tabela caso modifique quantidades ou valores.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light-primary font-weight-bold" data-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
// 1. Usamos 'var' em vez de 'let' nas variáveis para evitar o erro de 
// SyntaxError "already been declared" caso o script carregue mais de uma vez.
var PARCELAS_REMOVIDAS = [];

// 2. Carregamos os itens do PHP diretamente no array global do sistema (window.ITENS)
window.ITENS = [];
@if(isset($compra->itens) && count($compra->itens) > 0)
    @foreach($compra->itens as $i)
        window.ITENS.push({
            id: {{ $i->id }},
            codigo: {{ $i->produto_id }},
            nome: "{{ $i->produto->nome ?? 'Produto' }}",
            quantidade: "{{ number_format($i->quantidade, 2, ',', '') }}",
            valor: "{{ number_format($i->valor_unitario, 2, ',', '') }}"
        });
    @endforeach
@endif

// 3. Função segura para buscar o Total da Nota da Tela (Global)
window.obterValorTotalSeguro = function() {
    let txtTotal = $('#total').text().replace('R$', '').trim();
    if(!txtTotal) return 0;
    txtTotal = txtTotal.replace(/\./g, '').replace(',', '.');
    return parseFloat(txtTotal) || 0;
};

// 4. Recriamos a função de montar a tabela incluindo os botões de Editar e Excluir
window.montaTabela = function() {
    let t = "";
    window.ITENS.map((v) => {
        let qty = parseFloat(v.quantidade.toString().replace(',', '.'));
        let val = parseFloat(v.valor.toString().replace(',', '.'));
        let subtotal = qty * val;

        t += "<tr class='datatable-row'>";
        t += "<td class='datatable-cell' style='width: 50px;'>" + v.id + "</td>";
        t += "<td class='datatable-cell cod' style='width: 80px;'><span class='codigo'>" + v.codigo + "</span></td>";
        t += "<td class='datatable-cell' style='width: 320px;'>" + v.nome + "</td>";
        t += "<td class='datatable-cell' style='width: 120px;'>" + v.valor + "</td>";
        t += "<td class='datatable-cell' style='width: 100px;'>" + v.quantidade + "</td>";
        t += "<td class='datatable-cell' style='width: 120px;'>" + formatReal(subtotal) + "</td>";
        t += "<td class='datatable-cell text-center' style='width: 80px;'>";
        t += "<button type='button' class='btn btn-sm btn-warning btn-icon mr-1' onclick='editarItem(" + v.id + ")'><i class='la la-edit'></i></button>";
        t += "<button type='button' class='btn btn-sm btn-danger btn-icon' onclick='deleteItem(" + v.id + ")'><i class='la la-trash'></i></button>";
        t += "</td>";
        t += "</tr>";
    });
    return t;
};

document.addEventListener("DOMContentLoaded", function() {
    
    // =====================================================================
    // 5. EXIBIR A TABELA ASSIM QUE A TELA CARREGAR
    // =====================================================================
    if (window.ITENS.length > 0) {
        $('.prod tbody').html(montaTabela());
        calcTotal();
    }

    const optContas = `<option value="">-- Nenhuma (A Prazo) --</option>
        @if(isset($contasEmpresa))
            @foreach($contasEmpresa as $c)
                <option value="{{$c->id}}">{{$c->nome}}</option>
            @endforeach
        @endif`;
        
    const optVeiculos = `<option value="">-- Geral --</option>
        @if(isset($veiculos))
            @foreach($veiculos as $v)
                <option value="{{$v->id}}">{{$v->placa}}</option>
            @endforeach
        @endif`;
    
    setTimeout(function() {

        // CONSULTA DO SALDO DE ADIANTAMENTO DISPONÍVEL
        function buscarSaldoAdiantamento(id_fornecedor) {
            if(id_fornecedor && id_fornecedor !== '--') {
                let url = path + 'adiantamentos/consulta-saldo/fornecedor/' + id_fornecedor;
                $.get(url, function(data) {
                    let saldo = parseFloat(data.saldo);
                    if(saldo > 0) {
                        $('#label_saldo_adv').text('R$ ' + saldo.toLocaleString('pt-br', {minimumFractionDigits: 2}));
                        $('#div_adiantamento').show(); 
                    } else {
                        $('#div_adiantamento').hide(); 
                        $('#usar_adiantamento').prop('checked', false);
                    }
                }).fail(function() {
                    console.error("Erro na busca de saldo.");
                });
            } else {
                $('#div_adiantamento').hide();
                $('#usar_adiantamento').prop('checked', false);
            }
        }

        $('#kt_select2_1').on('change', function() { buscarSaldoAdiantamento($(this).val()); });
        let fornecedorJaPreenchido = $('#kt_select2_1').val();
        if(fornecedorJaPreenchido && fornecedorJaPreenchido !== '--') { buscarSaldoAdiantamento(fornecedorJaPreenchido); }

        // INTEGRAÇÃO DO FATURAMENTO DINÂMICO
        $('#tipo_condicao').on('change', function() {
            let tipo = $(this).val();
            if (tipo === 'rateio') {
                $('.id-div-rateio').fadeIn(); $('.div-gerador').hide(); executarRateioVeiculos();
            } else if (tipo === 'prazo') {
                $('.div-gerador').fadeIn(); $('.id-div-rateio').hide();
            } else if (tipo === 'vista') {
                $('.div-gerador').hide(); $('.id-div-rateio').hide();
                let totalNF = obterValorTotalSeguro().toLocaleString('pt-br', {minimumFractionDigits: 2});
                let hoje = $('#data_retroativa_dynamic').val() || "{{ date('d/m/Y') }}";
                
                $('#tabela-fatura tbody').html(`
                    <tr>
                        <td><input type="text" name="fatura_num[]" class="form-control form-control-sm text-center font-weight-bold" value="001"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control form-control-sm date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control form-control-sm money text-right font-weight-bold text-success" value="${totalNF}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select custom-select-sm">
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="pix">Pix</option>
                                <option value="boleto">Boleto</option>
                                <option value="transferencia">Transferência</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                            </select>
                        </td>
                        <td><select name="fatura_conta[]" class="custom-select custom-select-sm border-info">${optContas}</select></td>
                        <td><select name="fatura_veiculo[]" class="custom-select custom-select-sm">${optVeiculos}</select></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button>
                            <input type="hidden" name="fatura_db_id[]" value="">
                        </td>
                    </tr>
                `);
                $('.money').mask('#.##0,00', {reverse: true});
                $('.date-input').mask('00/00/0000');
                if($('#veiculos_ids').val() && $('#veiculos_ids').val().length > 0) { $('#tabela-fatura tbody tr:last .select-veiculo-parcela').val($('#veiculos_ids').val()[0]); }
                revezarFaturaEstatica();
            }
        });

        $('#veiculos_rateio').on('change', function() { executarRateioVeiculos(); });

        function executarRateioVeiculos() {
            let veiculosSelecionados = $('#veiculos_rateio').val();
            if (!veiculosSelecionados || veiculosSelecionados.length === 0) {
                $('#tabela-fatura tbody').html('<tr><td colspan="7" class="text-center text-danger font-weight-bold py-4">Selecione os veículos acima no campo do rateio!</td></tr>');
                return;
            }

            let totalNF = obterValorTotalSeguro();
            let qtdVeiculos = veiculosSelecionados.length;
            let valorFatiado = (totalNF / qtdVeiculos).toFixed(2);
            let valorFormatado = parseFloat(valorFatiado).toLocaleString('pt-br', {minimumFractionDigits: 2});
            let hoje = $('#data_retroativa_dynamic').val() || "{{ date('d/m/Y') }}";
            let html = '';

            veiculosSelecionados.forEach(function(veiculoId, index) {
                let numeroParcela = String(index + 1).padStart(3, '0');
                let vOptions = `<option value="">-- Geral --</option>`;
                @if(isset($veiculos))
                    @foreach($veiculos as $v)
                        vOptions += `<option value="{{$v->id}}" ${veiculoId == "{{$v->id}}" ? 'selected' : ''}>{{$v->placa}}</option>`;
                    @endforeach
                @endif

                html += `
                    <tr>
                        <td><input type="text" name="fatura_num[]" class="form-control form-control-sm text-center font-weight-bold" value="${numeroParcela}"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control form-control-sm date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control form-control-sm money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select custom-select-sm">
                                <option value="pix">Pix</option>
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="boleto">Boleto</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                            </select>
                        </td>
                        <td><select name="fatura_conta[]" class="custom-select custom-select-sm border-info">${optContas}</select></td>
                        <td><select name="fatura_veiculo[]" class="custom-select custom-select-sm">${vOptions}</select></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button>
                            <input type="hidden" name="fatura_db_id[]" value="">
                        </td>
                    </tr>
                `;
            });
            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true}); $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        }

        $('#btn_gerar_parcelas').off('click').on('click', function() {
            let qtd = parseInt($('#qtd_parcelas_manual').val()) || 1;
            let totalNF = obterValorTotalSeguro();
            let valorParcela = (totalNF / qtd).toFixed(2);
            let valorFormatado = parseFloat(valorParcela).toLocaleString('pt-br', {minimumFractionDigits: 2});
            let html = '';
            let dataBase = new Date();

            for (let i = 1; i <= qtd; i++) {
                dataBase.setMonth(dataBase.getMonth() + 1);
                let dia = String(dataBase.getDate()).padStart(2, '0');
                let msg = String(dataBase.getMonth() + 1).padStart(2, '0');
                let ano = dataBase.getFullYear();
                let dataStr = `${dia}/${msg}/${ano}`;

                html += `
                    <tr>
                        <td><input type="text" name="fatura_num[]" class="form-control form-control-sm text-center font-weight-bold" value="${String(i).padStart(3, '0')}"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control form-control-sm date-input text-center" value="${dataStr}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control form-control-sm money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select custom-select-sm">
                                <option value="boleto">Boleto</option>
                                <option value="pix">Pix</option>
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                            </select>
                        </td>
                        <td><select name="fatura_conta[]" class="custom-select custom-select-sm border-info">${optContas}</select></td>
                        <td><select name="fatura_veiculo[]" class="custom-select custom-select-sm">${optVeiculos}</select></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button>
                            <input type="hidden" name="fatura_db_id[]" value="">
                        </td>
                    </tr>
                `;
            }
            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true}); $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        });

        $('#btn-adicionar-linha-fatura').off('click').on('click', function() {
            let numLinhas = $('#tabela-fatura tbody tr').length + 1;
            let numFormatado = String(numLinhas).padStart(3, '0');
            let hoje = $('#data_retroativa_dynamic').val() || "{{ date('d/m/Y') }}";
            
            let novaLinha = `
                <tr>
                    <td><input type="text" name="fatura_num[]" class="form-control form-control-sm text-center font-weight-bold" value="${numFormatado}"></td>
                    <td><input type="text" name="fatura_venc[]" class="form-control form-control-sm date-input text-center" value="${hoje}"></td>
                    <td><input type="text" name="fatura_val[]" class="form-control form-control-sm money text-right font-weight-bold text-success" value="0,00"></td>
                    <td>
                        <select name="forma_pagamento[]" class="custom-select custom-select-sm">
                            <option value="boleto">Boleto</option>
                            <option value="pix">Pix</option>
                            <option value="dinheiro">Dinheiro (Caixa)</option>
                            <option value="adiantamento">Adiantamento Fornecedor</option>
                        </select>
                    </td>
                    <td><select name="fatura_conta[]" class="custom-select custom-select-sm border-info">${optContas}</select></td>
                    <td><select name="fatura_veiculo[]" class="custom-select custom-select-sm">${optVeiculos}</select></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        <input type="hidden" name="fatura_db_id[]" value="">
                    </td>
                </tr>
            `;
            $('#tabela-fatura tbody').append(novaLinha);
            $('.money').mask('#.##0,00', {reverse: true}); $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        });

        // REMOÇÃO INTELIGENTE DE FATURAS NA EDIÇÃO
        $(document).off('click', '.btn-remover-fat').on('click', '.btn-remover-fat', function() {
            let db_id = $(this).closest('tr').find('input[name="fatura_db_id[]"]').val();
            if (db_id) {
                PARCELAS_REMOVIDAS.push(db_id);
            }
            $(this).closest('tr').remove();
            revezarFaturaEstatica();
        });

        $(document).on('blur', 'input[name="fatura_venc[]"], input[name="fatura_val[]"]', function() { revezarFaturaEstatica(); });
        $(document).on('change', 'select[name="forma_pagamento[]"], select[name="fatura_veiculo[]"], select[name="fatura_conta[]"]', function() { revezarFaturaEstatica(); });

        // COMPILAÇÃO MATEMÁTICA E LIBERAÇÃO AUTOMÁTICA
        function revezarFaturaEstatica() {
            FATURA = [];
            $('#tabela-fatura tbody tr').each(function() {
                let num = $(this).find('input[name="fatura_num[]"]').val();
                let venc = $(this).find('input[name="fatura_venc[]"]').val();
                let val = $(this).find('input[name="fatura_val[]"]').val();
                let forma = $(this).find('select[name="forma_pagamento[]"]').val();
                let veiculo = $(this).find('select[name="fatura_veiculo[]"]').val();
                let conta = $(this).find('select[name="fatura_conta[]"]').val();
                let db_id = $(this).find('input[name="fatura_db_id[]"]').val() || null;
                
                if(num && venc && val) {
                    FATURA.push({
                        numero: num,
                        data: venc,
                        valor: val,
                        forma_pagamento: forma,
                        veiculo_id: veiculo,
                        conta_empresa_id: conta,
                        db_id: db_id
                    });
                }
            });
            
            if (FATURA.length > 0) { $('#salvar-venda').removeAttr('disabled'); } 
            else { $('#salvar-venda').attr('disabled', 1); }
        }

        // ========================================================
        // DISPARO AJAX DE ATUALIZAÇÃO
        // ========================================================
        window.atualizarCompra = function() {
            revezarFaturaEstatica();
            if(salvando == false){
                salvando = true;
                $('#preloader2').css('display', 'block');

                var fornecedor = $('.fornecedor').val();
                if (fornecedor == '--') {
                    swal({title: "Erro", text: "Selecione um fornecedor para continuar!", type: "warning"});
                    salvando = false; $('#preloader2').css('display', 'none'); return;
                } 
                
                var categoria = $('#categoria_conta_id').val();
                if (!categoria || categoria == '') {
                    swal({title: "Aviso", text: "Selecione uma categoria de conta para atualizar!", type: "warning"});
                    salvando = false; $('#preloader2').css('display', 'none'); return;
                }
              
                var transportadora = $('#kt_select2_3').val();
                transportadora = transportadora == 'null' ? null : transportadora;
                
                let js = {
                    id: $('#compra_id').val(),
                    fornecedor_id: fornecedor,
                    formaPagamento: $('#tipo_condicao').val() === 'vista' ? 'a_vista' : 'a_prazo',
                    usar_adiantamento: $('#usar_adiantamento').is(':checked') ? 1 : 0,
                    rateio_veiculo: $('#tipo_condicao').val() === 'rateio' ? 1 : 0,
                    fatura_manual: FATURA,

                    nf: $('#nf').val(),
                    numero_emissao: 0,
                    data_emissao: $('#data_emissao').val(),
                    veiculo_id: $('#veiculos_ids').val() ? $('#veiculos_ids').val()[0] : null,

                    itens: window.ITENS, 
                    fatura: FATURA,
                    faturas_removidas: PARCELAS_REMOVIDAS, 
                    total: obterValorTotalSeguro(),
                    desconto: $('#desconto').val(),
                    acrescimo: $('#acrescimo').val(),
                    observacao: $('#obs').val(),
                    categoria_conta_id: categoria,
                    especie: $('#especie').val(),
                    numeracaoVol: $('#numeracaoVol').val(),
                    qtdVol: $('#qtdVol').val(),
                    pesoL: $('#pesoL').val(),
                    pesoB: $('#pesoB').val(),
                    transportadora: transportadora,
                    frete: $('#frete').val(),
                    placaVeiculo: $('#placa').val(),
                    ufPlaca: $('#uf_placa').val(),
                    valorFrete: $('#valor_frete').val(),
                    data_retroativa: $('#data_retroativa_dynamic').val(),
                    data_saida: $('#data_saida_dynamic').val()
                };

                let token = $('#_token').val();
                
                $.ajax({
                    type: 'POST',
                    data: { compra: js, _token: token },
                    url: path + 'compraManual/update',
                    dataType: 'json',
                    success: function (e) {
                        $('#preloader2').css('display', 'none'); sucesso(e);
                    }, error: function (e) {
                        $('#preloader2').css('display', 'none'); swal("Erro", "Erro ao atualizar a compra.", "warning");
                    }
                });
            }
            salvando = false;
        };

        // Dá o gatilho inicial para ler faturas que vieram do BD
        setTimeout(function() { revezarFaturaEstatica(); }, 500);
    }, 1000); 
});
  
function editarItem(itemId) {
    let item = window.ITENS.find(i => i.id == itemId); 
    
    if (item) {
        $('#id_item').val(item.id);
        $('#codigo_produto').val(item.codigo);
        $('#produto_nome').val(item.nome);
        $('#qtd_item').val(item.quantidade);
        $('#vl_item').val(item.valor);
        $('#modal-edit-item').modal('show');
    } else {
        swal("Erro", "Item não encontrado!", "error");
    }
}

$('#salvar-edit').off('click').on('click', function() {
    let id = $('#id_item').val();
    let qtd = $('#qtd_item').val();
    let val = $('#vl_item').val();
    
    let item = window.ITENS.find(i => i.id == id);
    item.quantidade = qtd;
    item.valor = val;
    
    $('.prod tbody').html(montaTabela());
    calcTotal();
    $('#modal-edit-item').modal('hide');
});
</script>
@endsection