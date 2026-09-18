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
                <h3 class="card-title font-weight-bolder text-dark"><i class="la la-file-invoice text-dark icon-xl mr-2"></i> DADOS INICIAIS DA COMPRA</h3>
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
                                <input type="text" name="data_retroativa" class="form-control date-input form-control-solid" value="{{ isset($compra->data_retroativa) ? \Carbon\Carbon::parse($compra->data_retroativa)->format('d/m/Y') : old('data_retroativa', date('d/m/Y')) }}" id="data_retroativa_dynamic" />
                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                            </div>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Data Saída</label>
                            <div class="input-group date">
                                <input type="text" name="data_saida" class="form-control date-input form-control-solid" value="{{ isset($compra->data_saida) ? \Carbon\Carbon::parse($compra->data_saida)->format('d/m/Y') : old('data_saida', date('d/m/Y')) }}" id="data_saida_dynamic" />
                                <div class="input-group-append"><span class="input-group-text"><i class="la la-calendar"></i></span></div>
                            </div>
                        </div>

                        <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Nota Fiscal</label>
                            <input type="text" class="form-control form-control-solid" id="numero_emissao" name="numero_emissao" value="{{ $compra->nf ?? '' }}" placeholder="Ex: 000000">
                        </div>

                        <div class="form-group validated col-lg-2 col-md-4 col-sm-6">
                            <label class="col-form-label font-weight-bold">Emissão da NF</label>
                            <input type="date" class="form-control form-control-solid" id="data_emissao" name="data_emissao" value="{{ (isset($compra) && $compra->data_emissao) ? \Carbon\Carbon::parse($compra->data_emissao)->format('Y-m-d') : date('Y-m-d') }}">
                        </div>

                        <div class="form-group validated col-lg-4 col-md-8 col-sm-12">
                            <label class="col-form-label font-weight-bold">Veículos Utilizados (Para Múltiplos Selecione Abaixo)</label>
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
											    <option value="{{$f->id}}">{{$f->razao_social}} - {{$f->nome_fantasia}} ({{$f->cpf_cnpj}})</option>
											@endforeach
										</select>
										<button type="button" onclick="novoFornecedor()" class="btn btn-warning btn-sm shadow-sm"><i class="la la-plus-circle icon-add"></i></button>
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

								<div class="row" id="fornecedor" style="display: none">
									<div class="row col-12 mt-4 bg-light p-4 rounded m-0 shadow-sm" style="border-left: 4px solid #3699ff;">
										<div class="col-sm-6 col-lg-6">
											<h6>Razão Social: <strong id="razao_social" class="text-dark">--</strong></h6>
											<h6>Nome Fantasia: <strong id="nome_fantasia" class="text-dark">--</strong></h6>
											<h6>Endereço: <strong id="logradouro" class="text-dark">--</strong>, <strong id="numero" class="text-dark">--</strong></h6>
										</div>
										<div class="col-sm-6 col-lg-6">
											<h6>CPF/CNPJ: <strong id="cnpj" class="text-dark">--</strong></h6>
											<h6>Inscrição Estadual: <strong id="ie" class="text-dark">--</strong></h6>
											<h6>Cidade/UF: <strong id="cidade" class="text-dark">--</strong></h6>
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
										<tbody class="datatable-body"></tbody>
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
													    <option value="{{$t->id}}">{{$t->id}} - {{$t->razao_social}}</option>
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
												<option @if($config->frete_padrao == '0') selected @endif value="0">0 - Emitente</option>
												<option @if($config->frete_padrao == '1') selected @endif  value="1">1 - Destinatário</option>
												<option @if($config->frete_padrao == '2') selected @endif  value="2">2 - Terceiros</option>
												<option @if($config->frete_padrao == '9') selected @endif  value="9">9 - Sem Frete</option>
											</select>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Placa do Veículo</label>
											<input type="text" name="placa" class="form-control form-control-solid text-uppercase" value="" id="placa"/>
										</div>
										<div class="form-group validated col-sm-2 col-lg-2 col-6">
											<label class="font-weight-bold text-dark-75">UF Placa</label>
											<select class="custom-select form-control form-control-solid" id="uf_placa" name="uf_placa">
												<option value="--">--</option>
												@foreach(\App\Models\Cidade::estados() as $uf)
                                                    <option value="{{$uf}}">{{$uf}}</option>
                                                @endforeach
											</select>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Valor do Frete</label>
											<input type="text" name="valor_frete" class="form-control form-control-solid money text-right" value="" id="valor_frete"/>
										</div>
									</div>
									<hr class="my-6 opacity-10">

									<h4 class="mb-5 font-weight-bolder text-dark">Volumes e Pesos</h4>
									<div class="row align-items-center">
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Espécie</label>
											<input type="text" name="especie" class="form-control form-control-solid" value="VOLUMES" id="especie"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Numeração Vol.</label>
											<input type="text" name="numeracaoVol" class="form-control form-control-solid text-center" value="0" id="numeracaoVol"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Qtd. Volumes</label>
											<input type="text" name="qtdVol" class="form-control form-control-solid text-center" value="0" id="qtdVol"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-6 col-6">
											<label class="font-weight-bold text-dark-75">Peso Líquido</label>
											<input type="text" name="pesoL" class="form-control form-control-solid text-right" value="0,00" id="pesoL"/>
										</div>
										<div class="form-group col-lg-2 col-md-4 col-sm-4 col-6">
											<label class="font-weight-bold text-dark-75">Peso Bruto</label>
											<input type="text" name="pesoB" class="form-control form-control-solid text-right" value="0,00" id="pesoB"/>
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
                                                    <!-- Injetado dinamicamente via JS igual no DFe -->
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
                                    <h3 class="font-weight-bolder text-dark mb-0">VALOR TOTAL NF: <span id="total" class="text-success font-size-h1 ml-2">R$ 0,00</span></h3>
                                </div>
                            </div>
                            <div class="card-body pt-3 pb-5">
                                <div class="row align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="row">
                                            <div class="form-group col-6 mb-2">
                                                <label class="font-weight-bold text-dark-75">Desconto (R$)</label>
                                                <input type="text" class="form-control form-control-sm money" id="desconto" placeholder="0,00">
                                            </div>
                                            <div class="form-group col-6 mb-2">
                                                <label class="font-weight-bold text-dark-75">Acréscimo (R$)</label>
                                                <input type="text" class="form-control form-control-sm money" id="acrescimo" placeholder="0,00">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-lg-5 col-md-6 mb-2">
                                        <label class="font-weight-bold text-dark-75">Observação Interna / Histórico</label>
                                        <input type="text" class="form-control form-control-sm" id="obs" placeholder="Escreva observações ou observações adicionais da NFe...">
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 mb-2">
                                        <label class="font-weight-bold text-danger">Categoria da Conta (Financeiro) *</label>
                                        <select class="custom-select form-control form-control-sm border-danger font-weight-bold" id="categoria_conta_id" name="categoria_conta_id" required>
                                            <option value="">-- Selecione --</option>
                                            @foreach($categoriasDeConta as $c)
                                                <option value="{{$c->id}}">{{$c->nome}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 mb-2">
                                        <label class="font-weight-bold text-dark-75">Nº Controle Lote</label>
                                        <input type="text" class="form-control form-control-sm text-center" id="lote" placeholder="Opcional">
                                    </div>

                                    <div class="form-group col-12 mt-4 text-right mb-0">
                                        <button disabled type="button" class="btn btn-success font-weight-bolder text-uppercase px-15 py-3 shadow" id="salvar-venda" onclick="salvarCompra()">
                                            <i class="la la-check-circle icon-lg"></i> Finalizar Emissão
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIM DO BLOCO DE RESUMO -->

					</div>
				</div>
			</div>
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
		</div>
	</div>
</div>




<div class="modal fade" id="modal-fornecedor" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Fornecedor</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="col-xl-12">

						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaFisica"/>
										<span></span>
										FISICA
									</label>
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaJuridica"/>
										<span></span>
										JURIDICA
									</label>

								</div>

							</div>
						</div>
						<div class="row">

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
								<div class="">
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">

								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label text-left col-lg-12 col-sm-12">UF</label>

								<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c)
									<option value="{{$c}}">{{$c}}
									</option>
									@endforeach
								</select>

							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-sm-6">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-6">
								<label class="col-form-label">Nome Fantasia</label>
								<div class="">
									<input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_ie_rg">RG</label>
								<div class="">
									<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
								</div>
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
								<div class="">
									<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-2 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class="">
									<input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-5">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">

								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_4">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>

							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class="">
									<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Celular (Opcional)</label>
								<div class="">
									<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif">
								</div>
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


<div class="modal fade" id="modal-produto" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="wizard wizard-3" id="kt_wizard_v4" data-wizard-state="between" data-wizard-clickable="true">
					<div class="wizard-nav">

						<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
								<div class="wizard-label">
									<h3 class="wizard-title">
										<span>
											IDENTIFICAÇÃO
										</span>
									</h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
								<div class="wizard-label">
									<h3 class="wizard-title">
										<span>
											ALÍQUOTAS
										</span>
									</h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-body">
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

							<form class="form fv-plugins-bootstrap fv-plugins-framework form-prod" id="kt_form">
								<p class="kt-widget__data text-danger">Campos com (*) obrigatório</p>

								<div class="pb-5" data-wizard-type="step-content">
									<div class="row">

										<div class="col-xl-12">
											<div class="row">

												<div class="form-group validated col-sm-9 col-lg-9">
													<label class="col-form-label">Nome*</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" id="nome">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Referência</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" id="referencia">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Valor de Compra*</label>
													<div class="">
														<input type="text" id="valor_compra" class="form-control @if($errors->has('valor_compra')) is-invalid @endif money">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">% lucro*</label>
													<div class="">
														<input type="text" id="percentual_lucro" class="form-control money" name="percentual_lucro" value="{{$config->percentual_lucro_padrao }}">
													</div>
												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Valor de Venda*</label>
													<div class="">
														<input type="text" id="valor_venda" class="form-control @if($errors->has('valor_venda')) is-invalid @endif money">

													</div>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Iniciar com Estoque</label>
													<div class="">
														<input type="text" id="estoque" class="form-control @if($errors->has('estoque')) is-invalid @endif money">

													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-4">
													<label class="col-form-label">Código de Barras EAN13</label>
													<div class="">
														<input type="text" class="form-control @if($errors->has('codBarras')) is-invalid @endif" id="codBarras">
													</div>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Estoque minimo</label>
													<div class="">
														<input type="text" id="estoque_minimo" class="form-control @if($errors->has('estoque_minimo')) is-invalid @endif">
													</div>
												</div>


												<div class="form-group validated col-sm-6 col-lg-4">
													<label class="col-form-label">Gerenciar estoque</label>
													<div class="col-6">
														<span class="switch switch-outline switch-primary">
															<label>
																<input value="true" type="checkbox" id="gerenciar_estoque">
																<span></span>
															</label>
														</span>
													</div>
												</div>

												<div class="form-group validated col-sm-6 col-lg-2">
													<label class="col-form-label">Inativo</label>
													<div class="col-6">
														<span class="switch switch-outline switch-danger">
															<label>
																<input value="true" type="checkbox" id="inativo">
																<span></span>
															</label>
														</span>
													</div>
												</div>

												<div class="form-group validated col-lg-3 col-md-5 col-sm-10">
													<label class="col-form-label ">Categoria</label>
													<div class="input-group">

														<select id="categoria_id" class="form-control custom-select">
															@foreach($categorias as $cat)
															<option value="{{$cat->id}}">{{$cat->nome}}
															</option>
															@endforeach
														</select>

													</div>
												</div>

												<div class="form-group validated col-sm-4 col-lg-3">
													<label class="col-form-label">Limite maximo desconto %</label>
													<div class="">
														<input type="text" id="limite_maximo_desconto" class="form-control @if($errors->has('limite_maximo_desconto')) is-invalid @endif">
													</div>
												</div>



												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">Alerta de Venc. (Dias)</label>
													<div class="">
														<input type="text" id="alerta_vencimento" class="form-control @if($errors->has('alerta_vencimento')) is-invalid @endif">
													</div>
												</div>


												<div class="form-group validated col-lg-3 col-md-6 col-sm-10">
													<label class="col-form-label">Unidade de compra *</label>

													<select class="custom-select form-control" id="unidade_compra" id="unidade_compra">
														@foreach($unidadesDeMedida as $u)
														<option @if($u == 'UN') selected @endif value="{{$u}}">{{$u}}
														</option>
														@endforeach
													</select>
												</div>


												<div class="form-group validated col-sm-3 col-lg-3" id="conversao" style="display: none">
													<label class="col-form-label">Conversão Unitária</label>
													<div class="">
														<input type="text" id="conversao_unitaria" class="form-control @if($errors->has('conversao_unitaria')) is-invalid @endif">
													</div>
												</div>
												<div class="form-group validated col-lg-3 col-md-6 col-sm-10">
													<label class="col-form-label">Unidade de venda *</label>

													<select class="custom-select form-control" id="unidade_venda">
														@foreach($unidadesDeMedida as $u)
														<option @if($u == 'UN') selected @endif value="{{$u}}">{{$u}}
														</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-sm-3 col-lg-3">
													<label class="col-form-label">NCM *</label>
													<div class="">
														<input data-mask="0000.00.00" type="text" id="NCM" class="form-control @if($errors->has('NCM')) is-invalid @endif" value="{{$tributacao->ncm_padrao}}">
													</div>
												</div>

												<div class="form-group validated col-sm-2 col-lg-3">
													<label class="col-form-label">CEST</label>
													<div class="">
														<input type="text" id="CEST" class="form-control @if($errors->has('CEST')) is-invalid @endif">
													</div>
												</div>
												<hr>

												<div class="form-group validated col-12">
													<h3>Derivado Petróleo</h3>
												</div>

												<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
													<label class="col-form-label">ANP</label>

													<select class="custom-select form-control" id="anp">
														<option value="">--</option>
														@foreach($anps as $key => $a)
														<option value="{{$key}}">[{{$key}}] - {{$a}}
														</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GLP</label>

													<input type="text" id="perc_glp" class="form-control @if($errors->has('perc_glp')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GNn</label>

													<input type="text" id="perc_gnn" class="form-control @if($errors->has('perc_gnn')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">%GNi</label>

													<input type="text" id="perc_gni" class="form-control @if($errors->has('perc_gni')) is-invalid @endif trib">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Valor de partida</label>

													<input type="text" id="valor_partida" class="form-control @if($errors->has('valor_partida')) is-invalid @endif money">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Un. tributável</label>

													<input type="text" id="unidade_tributavel" class="form-control @if($errors->has('unidade_tributavel')) is-invalid @endif" data-mask="AAAA">
												</div>

												<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
													<label class="col-form-label">Qtd. tributável</label>

													<input type="text" id="quantidade_tributavel" class="form-control @if($errors->has('quantidade_tributavel')) is-invalid @endif" data-mask="00000,00" data-mask-reverse="true">
												</div>


												<hr>
												<div class="form-group validated col-12">
													<h3>Dados de dimensão e peso do produto (Opcional)</h3>
												</div>


												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Largura (cm)</label>

													<input type="text" id="largura" class="form-control @if($errors->has('largura')) is-invalid @endif">

												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Altura (cm)</label>

													<input type="text" id="altura" class="form-control @if($errors->has('altura')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Comprimento (cm)</label>

													<input type="text" id="comprimento" class="form-control @if($errors->has('comprimento')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Peso liquido</label>

													<input type="text" id="peso_liquido" class="form-control @if($errors->has('peso_liquido')) is-invalid @endif">
												</div>

												<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
													<label class="col-form-label">Peso bruto</label>

													<input type="text" id="peso_bruto" class="form-control @if($errors->has('peso_bruto')) is-invalid @endif">
												</div>

												<div class="col-lg-12 col-xl-12">
													<p class="text-danger">*Se atente a preencher todos os dados para utilizar a Api dos correios.</p>
												</div>

											</div>

										</div>
									</div>

								</div>
							</div>
							<div class="pb-5" data-wizard-type="step-content">

								<div class="row">

									<div class="col-xl-12">

										<div class="row">

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">
													@if($tributacao->regime == 1)
													CST
													@else
													CSOSN
													@endif
												*</label>

												<select class="custom-select form-control" id="CST_CSOSN">
													@foreach($listaCSTCSOSN as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_CSOSN)
														selected
														@endif
														@else
														@if($key == $config->CST_CSOSN_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST PIS *</label>

												<select class="custom-select form-control" id="CST_PIS">
													@foreach($listaCST_PIS_COFINS as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_PIS)
														selected
														@endif
														@else
														@if($key == $config->CST_PIS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST COFINS *</label>

												<select class="custom-select form-control" id="CST_COFINS">
													@foreach($listaCST_PIS_COFINS as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_COFINS)
														selected
														@endif
														@else
														@if($key == $config->CST_COFINS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">CST IPI *</label>

												<select class="custom-select form-control" id="CST_IPI">
													@foreach($listaCST_IPI as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_IPI)
														selected
														@endif
														@else
														@if($key == $config->CST_IPI_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>
											</div>

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">
													@if($tributacao->regime == 1)
													CST Exportação
													@else
													CSOSN Exportação
													@endif
												*</label>

												<select class="custom-select form-control" id="CST_CSOSN_EXP">
													<option value="">--</option>
													@foreach($listaCSTCSOSN as $key => $c)
													<option value="{{$key}}" @if(isset($produto)) @if($key==$produto->CST_CSOSN_EXP)
														selected
														@endif
														@endif

														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-sm-4 col-lg-3">
												<label class="col-form-label">CFOP saida interno *</label>
												<div class="">
													<input type="text" id="CFOP_saida_estadual" class="form-control @if($errors->has('CFOP_saida_estadual')) is-invalid @endif" value="{{{ isset($produto->CFOP_saida_estadual) ? $produto->CFOP_saida_estadual : $natureza->CFOP_saida_estadual }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-4 col-lg-3">
												<label class="col-form-label">CFOP saida externo *</label>
												<div class="">
													<input type="text" id="CFOP_saida_inter_estadual" class="form-control @if($errors->has('CFOP_saida_inter_estadual')) is-invalid @endif" value="{{{ isset($produto->CFOP_saida_inter_estadual) ? $produto->CFOP_saida_inter_estadual : $natureza->CFOP_saida_inter_estadual }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS *</label>
												<div class="">
													<input type="text" id="perc_icms" class="form-control trib @if($errors->has('perc_icms')) is-invalid @endif" value="{{{ isset($produto->perc_icms) ? $produto->perc_icms : $tributacao->icms }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%PIS *</label>
												<div class="">
													<input type="text" id="perc_pis" class="form-control trib @if($errors->has('perc_pis')) is-invalid @endif" value="{{{ isset($produto->perc_pis) ? $produto->perc_pis : $tributacao->pis }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%COFINS *</label>
												<div class="">
													<input type="text" id="perc_cofins" class="form-control trib @if($errors->has('perc_cofins')) is-invalid @endif" value="{{{ isset($produto->perc_cofins) ? $produto->perc_cofins : $tributacao->cofins }}}">
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%IPI *</label>
												<div class="">
													<input type="text" id="perc_ipi" class="form-control trib @if($errors->has('perc_ipi')) is-invalid @endif" value="{{{ isset($produto->perc_ipi) ? $produto->perc_ipi : $tributacao->ipi }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ISS*</label>
												<div class="">
													<input type="text" id="perc_iss" class="form-control trib @if($errors->has('perc_iss')) is-invalid @endif" value="{{{ isset($produto->perc_iss) ? $produto->perc_iss : 0.00 }}}">
												</div>
											</div>

											<div class="form-group validated col-sm-2 col-lg-2">
												<label class="col-form-label">%Redução BC</label>
												<div class="">
													<input type="text" id="pRedBC" class="form-control @if($errors->has('pRedBC')) is-invalid @endif" value="{{{ isset($produto->pRedBC) ? $produto->pRedBC : 0.00 }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">Cod benefício</label>
												<div class="">
													<input type="text" id="cBenef" class="form-control @if($errors->has('cBenef')) is-invalid @endif" value="{{{ isset($produto->cBenef) ? $produto->cBenef : old('cBenef') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS interestadual</label>
												<div class="">
													<input type="text" id="perc_icms_interestadual" class="form-control @if($errors->has('perc_icms_interestadual')) is-invalid @endif trib" value="{{{ isset($produto->perc_icms_interestadual) ? $produto->perc_icms_interestadual : old('perc_icms_interestadual') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%ICMS interno</label>
												<div class="">
													<input type="text" id="perc_icms_interno" class="form-control @if($errors->has('perc_icms_interno')) is-invalid @endif trib" value="{{{ isset($produto->perc_icms_interno) ? $produto->perc_icms_interno : old('perc_icms_interno') }}}">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">%FCP interestadual</label>
												<div class="">
													<input type="text" id="perc_fcp_interestadual" class="form-control @if($errors->has('perc_fcp_interestadual')) is-invalid @endif trib" value="{{{ isset($produto->perc_fcp_interestadual) ? $produto->perc_fcp_interestadual : old('perc_fcp_interestadual') }}}">

												</div>
											</div>

											<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
												<label class="col-form-label">
													@if($tributacao->regime == 1)
													CST entrada
													@else
													CSOSN entrada
												@endif *</label>

												<select class="custom-select form-control" id="CST_CSOSN_entrada" name="CST_CSOSN_entrada">
													@foreach($listaCSTCSOSN as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_CSOSN_entrada)
														selected
														@endif
														@else
														@if($key == $config->CST_CSOSN_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST PIS entrada *</label>

												<select class="custom-select form-control" id="CST_PIS_entrada" name="CST_PIS_entrada">
													@foreach(App\Models\Produto::listaCST_PIS_COFINS_Entrada() as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_PIS_entrada)
														selected
														@endif
														@else
														@if($key == $config->CST_PIS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST COFINS entrada *</label>

												<select class="custom-select form-control" id="CST_COFINS_entrada" name="CST_COFINS_entrada">
													@foreach(App\Models\Produto::listaCST_PIS_COFINS_Entrada() as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_COFINS_entrada)
														selected
														@endif
														@else
														@if($key == $config->CST_COFINS_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>

											</div>

											<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
												<label class="col-form-label">CST IPI entrada *</label>

												<select class="custom-select form-control" id="CST_IPI_entrada" name="CST_IPI_entrada">
													@foreach(App\Models\Produto::listaCST_IPI_Entrada() as $key => $c)
													<option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->CST_IPI_entrada)
														selected
														@endif
														@else
														@if($key == $config->CST_IPI_padrao)
														selected
														@endif
														@endif

														@endif
														>{{$key}} - {{$c}}
													</option>
													@endforeach
												</select>
											</div>

										</div>
									</div>
								</div>
							</div>

						</div>

					</form>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarProduto()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-transportadora" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nova Transportadora</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="col-xl-12">

						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaFisica3"/>
										<span></span>
										FISICA
									</label>
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaJuridica3"/>
										<span></span>
										JURIDICA
									</label>

								</div>

							</div>
						</div>
						<div class="row">

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj3">CPF</label>
								<div class="">
									<input type="text" id="cpf_cnpj3" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj">

								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro3" onclick="consultaCadastro3()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-12 col-lg-6">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social3" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-12 col-lg-6">
								<label class="col-form-label">Logradouro</label>
								<div class="">
									<input id="logradouro3" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-6 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class="">
									<input id="numero3" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-6 col-lg-3">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email3" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">

								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-4 col-12">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_10">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>
							</div>

							<div class="form-group validated col-12 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class="">
									<input id="telefone3" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
								</div>
							</div>

						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarTransportadora()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-ajuda" tabindex="-1" role="dialog" aria-labelledby="modalAjudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white" id="modalAjudaLabel"><i class="la la-info-circle text-white mr-2"></i> Instruções: Compra Manual e Emissão de NF-e</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    x
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="font-weight-bold text-primary">Passo 1: Preenchendo os Dados Iniciais</h6>
                    <ul class="text-dark-75 mb-0">
                        <li>Selecione o <strong>Fornecedor</strong> e os <strong>Produtos</strong>. Caso não existam, use o botão azul <strong>(+)</strong> para cadastrar na hora.</li>
                        <li>Se a nota for de <strong>Entrada Própria</strong>, deixe o campo "Número da NF" em branco (ele será gerado automaticamente).</li>
                    </ul>
                </div>

                <div class="mb-4 p-3 bg-light rounded border-left border-info border-3">
                    <h6 class="font-weight-bold text-info"><i class="la la-gavel"></i> Passo 2: Seleção da Natureza de Operação</h6>
                    <ul class="text-dark-75 mb-0" style="padding-left: 20px;">
                        <li><strong>Compra de Mercadoria:</strong> Selecione sempre as naturezas de <u>"Compras"</u> correspondentes.</li>
                        <li><strong>Notas de Devolução:</strong> Selecione obrigatoriamente a <u>"Natureza de Devolução"</u> correta para que os impostos sejam calculados de forma inversa.</li>
                    </ul>
                </div>

                <div class="mb-4 p-3 bg-light rounded border-left border-warning border-3">
                    <h6 class="font-weight-bold text-warning"><i class="la la-money-bill"></i> Passo 3: Forma de Pagamento e Regras da SEFAZ</h6>
                    <ul class="text-dark-75 mb-0" style="padding-left: 20px;">
                        <li><strong>Operações Normais:</strong> Selecione a forma real utilizada (PIX, Dinheiro, Boleto, etc.).</li>
                        <li><strong class="text-danger">Regra para Devolução / Simples Remessa:</strong> Nestes casos, você DEVE selecionar a opção <strong>"Sem Pagamento" (Código 90)</strong>. Isso zera o pagamento no XML e evita rejeição.</li>
                        <li><strong>Evitando duplicidade de data:</strong> Se o pagamento for no mesmo dia da emissão da nota, selecione a opção de prazo <strong>"À Vista"</strong>. A opção "A Prazo" só deve ser usada para datas futuras.</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6 class="font-weight-bold text-primary">Passo 4: Finalizar a Compra</h6>
                    <ul class="text-dark-75 mb-0">
                        <li>Desça até o final da tela, confira o valor total e clique em <strong>"Finalizar"</strong>.</li>
                        <li>O sistema vai salvar a compra e gerar as faturas no financeiro (caso não seja à vista ou sem pagamento).</li>
                    </ul>
                </div>

                <div class="mb-4">
                    <h6 class="font-weight-bold text-primary">Passo 5: Emitir a Nota Fiscal (Próxima Tela)</h6>
                    <ul class="text-dark-75 mb-0">
                        <li>Após salvar, você será levado para a tela de detalhes da compra.</li>
                        <li>Verifique se há itens marcados em <strong class="text-danger">vermelho</strong>. Se houver, clique em "Editar Produto" para corrigir impostos (CST e CFOP).</li>
                        <li>Tudo certo? Basta clicar no botão verde <strong>"Transmitir para Sefaz"</strong>.</li>
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
document.addEventListener("DOMContentLoaded", function() {
    
    // Options montadas dinamicamente via Blade para alimentar o JS
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

    // AVISO DE INSTRUÇÕES AO ABRIR A TELA (SweetAlert)
    setTimeout(function() {
        if (!localStorage.getItem('avisoCompraManualLido')) {
            swal({
                title: "Instruções Importantes",
                text: "<div style='text-align: left; font-size: 14px;'>" +
                      "<p><strong>1. Número da NF:</strong> Informe apenas se já emitida. Em nota de entrada, deixe em branco.</p>" +
                      "<p><strong>2. Categoria:</strong> Selecione a categoria que corresponde à despesa.</p>" +
                      "<p><strong>3. Pagamento À Vista:</strong> Se o pagamento for hoje, use 'À Vista' para evitar rejeição da SEFAZ por duplicidade de data.</p>" +
                      "</div>",
                html: true, 
                type: "info",
                confirmButtonText: "Entendi"
            }, function() {
                localStorage.setItem('avisoCompraManualLido', 'true');
            });
        }
    }, 500);
    
    setTimeout(function() {
        console.log("Forçando a captura das faturas e adiantamentos!");

        // 1. CONSULTA DO SALDO DE ADIANTAMENTO DISPONÍVEL
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

        $('#kt_select2_1').on('change', function() {
            buscarSaldoAdiantamento($(this).val());
        });

        let fornecedorJaPreenchido = $('#kt_select2_1').val();
        if(fornecedorJaPreenchido && fornecedorJaPreenchido !== '--') {
            buscarSaldoAdiantamento(fornecedorJaPreenchido);
        }

        // 2. MÁGICA DA INTEGRAÇÃO DO FATURAMENTO DINÂMICO (IGUAL AO DFE)
        $('#tipo_condicao').change(function() {
            let tipo = $(this).val();
            if (tipo === 'rateio') {
                $('.id-div-rateio').fadeIn();
                $('.div-gerador').hide();
                executarRateioVeiculos();
            } else if (tipo === 'prazo') {
                $('.div-gerador').fadeIn();
                $('.id-div-rateio').hide();
            } else if (tipo === 'vista') {
                $('.div-gerador').hide();
                $('.id-div-rateio').hide();
                
                let totalNF = parseFloat(TOTAL || 0).toLocaleString('pt-br', {minimumFractionDigits: 2});
                let hoje = $('#data_retroativa_dynamic').val() || "{{ date('d/m/Y') }}";
                
                // À Vista cria uma linha pré-faturada, permitindo escolher a conta empresa por linha!
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
                        <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button></td>
                    </tr>
                `);
                $('.money').mask('#.##0,00', {reverse: true});
                $('.date-input').mask('00/00/0000');
                
                // Pré-seleciona o veículo geral no select se já houver um selecionado lá em cima
                if($('#veiculos_ids').val() && $('#veiculos_ids').val().length > 0) {
                    $('#tabela-fatura tbody tr:last .select-veiculo-parcela').val($('#veiculos_ids').val()[0]);
                }
                revezarFaturaEstatica();
            }
        });

        $('#veiculos_rateio').change(function() { executarRateioVeiculos(); });

        function executarRateioVeiculos() {
            let veiculosSelecionados = $('#veiculos_rateio').val();
            if (!veiculosSelecionados || veiculosSelecionados.length === 0) {
                $('#tabela-fatura tbody').html('<tr><td colspan="7" class="text-center text-danger font-weight-bold py-4">Selecione os veículos acima no campo do rateio!</td></tr>');
                return;
            }

            let totalNF = parseFloat(TOTAL) || 0;
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
                        <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button></td>
                    </tr>
                `;
            });

            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        }

        $('#btn_gerar_parcelas').click(function() {
            let qtd = parseInt($('#qtd_parcelas_manual').val()) || 1;
            let totalNF = parseFloat(TOTAL) || 0;
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
                        <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button></td>
                    </tr>
                `;
            }
            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        });

        $('#btn-adicionar-linha-fatura').click(function() {
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
                    <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-light-danger btn-remover-fat"><i class="la la-trash"></i></button></td>
                </tr>
            `;
            $('#tabela-fatura tbody').append(novaLinha);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            revezarFaturaEstatica();
        });

        $(document).on('click', '.btn-remover-fat', function() {
            $(this).closest('tr').remove();
            revezarFaturaEstatica();
        });

        $(document).on('blur', 'input[name="fatura_venc[]"], input[name="fatura_val[]"]', function() { revezarFaturaEstatica(); });
        $(document).on('change', 'select[name="forma_pagamento[]"], select[name="fatura_veiculo[]"], select[name="fatura_conta[]"]', function() { revezarFaturaEstatica(); });

        // COMPILAÇÃO MATEMÁTICA E LIBERAÇÃO AUTOMÁTICA DO FINANCEIRO
        function revezarFaturaEstatica() {
            FATURA = [];
            $('#tabela-fatura tbody tr').each(function() {
                let num = $(this).find('input[name="fatura_num[]"]').val();
                let venc = $(this).find('input[name="fatura_venc[]"]').val();
                let val = $(this).find('input[name="fatura_val[]"]').val();
                let forma = $(this).find('select[name="forma_pagamento[]"]').val();
                let veiculo = $(this).find('select[name="fatura_veiculo[]"]').val();
                let conta = $(this).find('select[name="fatura_conta[]"]').val();
                
                if(num && venc && val) {
                    FATURA.push({
                        numero: num,
                        data: venc,
                        valor: val,
                        forma_pagamento: forma,
                        veiculo_id: veiculo,
                        conta_empresa_id: conta
                    });
                }
            });
            
            // 🔓 DESTRAVA AUTOMÁTICA DO BOTÃO FINALIZAR (IGUAL AO DFE)
            if (FATURA.length > 0) {
                $('#salvar-venda').removeAttr('disabled');
            } else {
                $('#salvar-venda').attr('disabled', 1);
            }
        }

        // DISPAROS AJAX DE SUBMISSÃO
        window.salvarCompra = function() {
            revezarFaturaEstatica();
            $('#salvar-venda').attr('disabled', 1);
            if(salvando == false){
                salvando = true;
                $('#preloader2').css('display', 'block');

                var fornecedor = $('.fornecedor').val();
                if (fornecedor == '--') {
                    swal({title: "Erro", text: "Selecione um fornecedor para continuar!", type: "warning"});
                    salvando = false; $('#preloader2').css('display', 'none'); $('#salvar-venda').removeAttr('disabled'); return;
                }

                var categoria = $('#categoria_conta_id').val();
                if (!categoria || categoria == '') {
                    swal({title: "Aviso", text: "Selecione uma categoria de conta para finalizar!", type: "warning"});
                    salvando = false; $('#preloader2').css('display', 'none'); $('#salvar-venda').removeAttr('disabled'); return;
                }
              
                var transportadora = $('#kt_select2_3').val();
                transportadora = transportadora == 'null' ? null : transportadora;
                
                let js = {
                    fornecedor: fornecedor,
                    formaPagamento: $('#tipo_condicao').val() === 'vista' ? 'a_vista' : 'a_prazo',
                    usar_adiantamento: $('#usar_adiantamento').is(':checked') ? 1 : 0,
                    rateio_veiculo: $('#tipo_condicao').val() === 'rateio' ? 1 : 0,
                    fatura_manual: FATURA,

                    nf: $('#numero_emissao').val(),
                    numero_emissao: 0,
                    data_emissao: $('#data_emissao').val(),
                    veiculo_id: $('#veiculos_ids').val() ? $('#veiculos_ids').val()[0] : null,
                    filial_id: $('#filial_id').val(),

                    itens: ITENS,
                    fatura: FATURA,
                    total: TOTAL,
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
                    url: path + 'compraManual/salvar',
                    dataType: 'json',
                    success: function (e) {
                        $('#preloader2').css('display', 'none');
                        sucesso(e);
                    }, error: function (e) {
                        $('#preloader2').css('display', 'none');
                        swal("Erro", "Erro ao salvar a compra.", "warning");
                    }
                });
            }
            salvando = false;
        };

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

                    nf: $('#numero_emissao').val(),
                    numero_emissao: 0,
                    data_emissao: $('#data_emissao').val(),
                    veiculo_id: $('#veiculos_ids').val() ? $('#veiculos_ids').val()[0] : null,

                    itens: ITENS,
                    fatura: FATURA,
                    faturas_removidas: PARCELAS_REMOVIDAS,
                    total: TOTAL,
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

    }, 1000); 
});
</script>
@endsection