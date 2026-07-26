@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover{ cursor: pointer }
	.search-prod{
		position: absolute; top: 0; margin-top: 40px; left: 10px; width: 100%;
		max-height: 200px; overflow: auto; z-index: 9999; border: 1px solid #eeeeee;
		border-radius: 4px; background-color: #fff; box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
	}
	.search-prod label:hover{ cursor: pointer; }
	.search-prod label{ margin-left: 10px; width: 100%; margin-top: 7px; font-size: 14px; }
</style>

<div class="row" id="anime" style="display: none">
	<div class="col s8 offset-s2">
		<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay></lottie-player>
	</div>
</div>

<input type="hidden" id="fornecedor_id" value="{{ isset($forn->id) ? $forn->id : (isset($fornecedor['id']) ? $fornecedor['id'] : 0) }}">

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form class="row" method="post" action="/dfe/salvar" id="form-salvar-compra">
					@csrf
                    <input type="hidden" value="{{$dfe->id}}" name="dfe_id">
					<input type="hidden" value="{{ $forn ? $forn->id : '' }}" name="fornecedor">
					<input type="hidden" value="{{json_encode($itens)}}" name="itens">
					<input type="hidden" value="{{$vDesc}}" name="vDesc">
					<input type="hidden" value="{{$nNf}}" name="nNf">
                    <input type="hidden" name="vProd" value="{{ $infos['vProd'] }}">
					<input type="hidden" name="chave" value="{{ $infos['chave'] }}">

					<div class="col-xl-12">
						<div class="card card-custom gutter-b example example-compact">
							<div class="card-header">
								<h3 class="card-title">Importando Nota Fiscal: <strong class="text-success ml-2"> {{ $infos['nNf'] }}</strong></h3>
							</div>
						</div>
					</div>

					<div class="col-xl-12">
						<div class="card card-custom gutter-b">
							<div class="card-body">
								<div class="row">
									<div class="col-md-6">
										<h4 class="font-weight-bold text-dark mb-4">Fornecedor</h4>
										<h5>Razão Social: <strong>{{ $fornecedor['razaoSocial'] }}</strong></h5>
										<h5>Nome Fantasia: <strong>{{ $fornecedor['nomeFantasia'] }}</strong></h5>
										<h5>Logradouro: <strong>{{ $fornecedor['logradouro'] }}, {{ $fornecedor['numero'] }}</strong></h5>
										<h5>Bairro: <strong>{{ $fornecedor['bairro'] }}</strong></h5>
									</div>
									<div class="col-md-6 text-right">
										<h4 class="font-weight-bold text-dark mb-4">Documento</h4>
										@if($fornecedor['cnpj'])
											<h5>CNPJ: <strong>{{ $fornecedor['cnpj'] }}</strong></h5>
										@else
											<h5>CPF: <strong>{{ $fornecedor['cpf'] }}</strong></h5>
										@endif
										<h5>IE: <strong>{{ $fornecedor['ie'] }}</strong></h5>
										<h5>Cidade/UF: <strong class="text-success">{{ $fornecedor['cidade'] ?? '' }} / {{ $fornecedor['uf'] ?? '' }}</strong></h5>
									</div>
								</div>
                                @if(isset($fornecedor['novo_cadastrado']) && $fornecedor['novo_cadastrado'])
                                <div class="alert alert-custom alert-light-success mt-4 mb-0" role="alert">
                                    <div class="alert-icon"><i class="flaticon-warning"></i></div>
                                    <div class="alert-text">Este fornecedor não está cadastrado no sistema, ele será cadastrado automaticamente com a importação!</div>
                                </div>
                                @endif
							</div>
						</div>
					</div>

					<div class="col-xl-12">
                        <div class="card card-custom gutter-b">
                            <div class="card-header">
								<h3 class="card-title font-weight-bold text-dark">Itens da NFe</h3>
							</div>
                            <div class="card-body">
                                {!! __view_locais_select() !!}

                                <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                    <table class="datatable-table" style="max-width: 100%;overflow-x: auto;">
                                        <thead class="datatable-head">
                                            <tr class="datatable-row" style="left: 0px;">
                                                <th class="datatable-cell"><span style="width: 70px;">#</span></th>
                                                <th class="datatable-cell"><span style="width: 180px;">Produto</span></th>
                                                <th class="datatable-cell"><span style="width: 130px;">Finalidade</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">NCM</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">CFOP Ent</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">CST ICMS</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">PIS/COF</span></th>
                                                <th class="datatable-cell"><span style="width: 120px;">IBS / CBS</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">Valor</span></th>
                                                <th class="datatable-cell"><span style="width: 60px;">Qtd</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">Subtotal</span></th>
                                                <th class="datatable-cell"><span style="width: 80px;">Ações</span></th>
                                            </tr>
                                        </thead>

                                        @php $contaSemRegistro = 0; @endphp
                                        <tbody class="datatable-body">
                                            @foreach($itens as $i)
                                            @php
                                            if($i['produtoNovo']) $contaSemRegistro++;
                                            @endphp
                                            <tr class="datatable-row" id="tr_{{$i['codigo']}}" style="left: 0px;">
                                                <td class="datatable-cell"><span class="codigo" style="width: 70px;">{{$i['codigo']}}</span></td>
                                                <td class="datatable-cell" id="th_{{$loop->index}}"><span class="nome {{$i['produtoNovo'] == true ? 'text-danger font-weight-bold' : ''}}" style="width: 180px;">{{$i['xProd']}}</span></td>

                                                <td class="datatable-cell">
                                                    <span style="width: 130px;">
                                                        <select class="form-control finalidade_input form-control-sm border-primary" style="width: 130px; font-size: 11px;">
                                                            <option value="revenda">Revenda</option>
                                                            <option value="uso_consumo_sem_credito">Uso/Consumo (S/ Crédito)</option>
                                                            <option value="uso_consumo_com_credito">Uso/Consumo (C/ Crédito)</option>
                                                            <option value="combustivel">Combustível</option>
                                                            <option value="imobilizado">Imobilizado</option>
                                                        </select>
                                                    </span>
                                                </td>

                                                <td class="datatable-cell"><span class="ncm" style="width: 80px;">{{$i['NCM']}}</span></td>

                                                <td class="datatable-cell">
                                                    <span style="width: 80px;">
                                                        <input type="text" class="form-control form-control-sm cfop_entrada_input" data-codigo="{{$i['codigo']}}" name="cfop_entrada[{{$i['codigo']}}]" value="{{$i['CFOP']}}">
                                                        <input type="hidden" class="cfop" value="{{$i['CFOP']}}">
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span style="width: 80px;">
                                                        <input type="text" class="form-control form-control-sm cst_icms_input" name="cst_icms_entrada[{{$i['codigo']}}]" value="{{$i['CST_ICMS']}}">
                                                        <input type="hidden" class="vbc_icms" value="{{$i['vbc_icms'] ?? 0}}">
                                                    </span>
                                                </td>
                                                <td class="datatable-cell">
                                                    <span style="width: 80px;">
                                                        <input type="text" class="form-control form-control-sm cst_pis_input mb-1" title="CST PIS" name="cst_pis_entrada[{{$i['codigo']}}]" value="{{$i['CST_PIS']}}">
                                                        <input type="text" class="form-control form-control-sm cst_cofins_input" title="CST COFINS" name="cst_cofins_entrada[{{$i['codigo']}}]" value="{{$i['CST_COFINS']}}">
                                                    </span>
                                                </td>

                                                <td class="datatable-cell">
                                                    <div style="width: 120px; font-size: 11px;" class="d-flex flex-column gap-1">
                                                        <span class="label label-light-dark label-inline font-weight-bolder mb-1" title="CST">
                                                            CST: {{ !empty($i['cst_ibs_cbs']) ? $i['cst_ibs_cbs'] : 'S/N' }}
                                                        </span>
                                                        <span class="label label-light-info label-inline font-weight-bolder mb-1" title="Alíquota e Valor IBS">
                                                            IBS: {{ number_format($i['aliq_ibs'] ?? 0, 2, ',', '.') }}% | R$ {{ number_format($i['valor_ibs'] ?? 0, 2, ',', '.') }}
                                                        </span>
                                                        <span class="label label-light-success label-inline font-weight-bolder" title="Alíquota e Valor CBS">
                                                            CBS: {{ number_format($i['aliq_cbs'] ?? 0, 2, ',', '.') }}% | R$ {{ number_format($i['valor_cbs'] ?? 0, 2, ',', '.') }}
                                                        </span>
                                                    </div>
                                                    <input type="hidden" class="cst_ibs_cbs" value="{{$i['cst_ibs_cbs'] ?? ''}}">
                                                    <input type="hidden" class="bc_ibs_cbs" value="{{$i['bc_ibs_cbs'] ?? 0}}">
                                                    <input type="hidden" class="aliq_ibs" value="{{$i['aliq_ibs'] ?? 0}}">
                                                    <input type="hidden" class="aliq_cbs" value="{{$i['aliq_cbs'] ?? 0}}">
                                                    <input type="hidden" class="valor_ibs" value="{{$i['valor_ibs'] ?? 0}}">
                                                    <input type="hidden" class="valor_cbs" value="{{$i['valor_cbs'] ?? 0}}">
                                                </td>

                                                <td class="datatable-cell">
                                                    <span class="valor" style="width: 80px;">{{$i['vUnCom']}}</span>
                                                    <input type="hidden" class="unidade" value="{{$i['uCom']}}">
                                                </td>
                                                <td class="datatable-cell"><span class="quantidade" style="width: 60px;">{{$i['qCom']}}</span></td>

                                                <th class="cod" id="th_prod_id_{{$loop->index}}" style="display: none">{{$i['produtoId']}}</th>
                                                <th style="display: none" class="conv_estoque" id="th_prod_conv_unit_{{$loop->index}}">{{$i['conversao_unitaria']}}</th>

                                                <td class="datatable-cell quantidade"><span style="width: 80px;" class="font-weight-bold text-success">R$ {{number_format((float) $i['qCom'] * (float) $i['vUnCom'], 2, ',', '.')}}</span></td>

                                                <td class="datatable-cell" style="overflow: visible !important;">
                                                  <span style="width: 80px; display: block;">
                                                      <a id="th_acao1_{{$loop->index}}"
                                                         @if($i['produtoNovo']) style="display: block" @else style="display: none" @endif
                                                         onclick="cadProd('{{$i['codigo']}}','{{$i['xProd']}}','{{$i['codBarras']}}','{{$i['NCM']}}','{{$i['CFOP']}}','{{$i['uCom']}}','{{$i['vUnCom']}}', '{{$i['qCom']}}', '{{$i['vUnCom']}}', '{{$infos['nNf']}}','{{$i['CEST']}}', '{{$loop->index}}')"
                                                         href="javascript:;" class="btn btn-sm btn-clean btn-icon mr-2">
                                                          <span class="svg-icon svg-icon-success">
                                                              <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24">
                                                                  <rect fill="#000000" x="4" y="11" width="16" height="2" rx="1" />
                                                                  <rect fill="#000000" opacity="0.3" transform="rotate(-90 12 12)" x="4" y="11" width="16" height="2" rx="1" />
                                                              </svg>
                                                          </span>
                                                      </a>

                                                      @if(!$i['produtoNovo'])
                                                      <a title="Vincular Produto" onclick="linkProduto('{{$i['codigo']}}')" href="javascript:;" class="btn btn-sm btn-clean btn-icon mr-2">
                                                          <i class="la la-link text-info icon-lg"></i>
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

					<div class="col-xl-12">
                        <div class="card card-custom gutter-b">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold text-dark">Condição de Pagamento / Rateio por Veículo</h3>
                            </div>
                            <div class="card-body">
                                <div class="row mb-6 bg-light p-4 rounded">
                                    <div class="form-group col-lg-3">
                                        <label class="font-weight-bold">Estrutura de Faturamento</label>
                                        <select id="tipo_condicao" class="custom-select form-control">
                                            <option value="xml">Manter Duplicatas do XML</option>
                                            <option value="vista">Forçar À Vista (Gera 1 Parcela Hoje)</option>
                                            <option value="rateio">Ratear Parcela Única por Veículos</option>
                                            <option value="prazo">Montar Parcelamento Manual</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-6 id-div-rateio" style="display:none;">
                                        <label class="font-weight-bold text-success">Selecione os Veículos para dividir o valor</label>
                                        <select id="veiculos_rateio" class="form-control select2-custom" multiple="multiple" style="width: 100%">
                                            @foreach($veiculos as $v)
                                                <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-2 div-gerador" style="display:none;">
                                        <label>Qtd Parcelas</label>
                                        <input type="number" id="qtd_parcelas_manual" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="form-group col-lg-2 div-gerador" style="display:none;">
                                        <label>&nbsp;</label>
                                        <button type="button" id="btn_gerar_parcelas" class="btn btn-primary btn-block">Gerar Parcelas</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="tabela-fatura">
                                        <thead>
                                            <tr class="bg-secondary text-dark">
                                                <th width="100">Parcela</th>
                                                <th width="150">Vencimento</th>
                                                <th width="180">Valor (R$)</th>
                                                <th width="200">Forma Pagamento</th>
                                                <th>Veículo Vinculado</th>
                                                <th width="80" class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fatura as $key => $f)
                                            <tr>
                                                <td>
                                                    <input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="{{ $f['numero'] }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="fatura_venc[]" class="form-control date-input text-center" value="{{ $f['vencimento'] }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="fatura_val[]" class="form-control money text-right font-weight-bold text-success" value="{{ $f['valor_parcela'] }}">
                                                </td>
                                                <td>
                                                    <select name="forma_pagamento[]" class="custom-select form-control">
                                                        <option value="boleto">Boleto</option>
                                                        <option value="pix">Pix</option>
                                                        <option value="transferencia">Transferência</option>
                                                        <option value="adiantamento">Adiantamento Fornecedor</option>
                                                        <option value="dinheiro">Dinheiro (Caixa)</option>
                                                        <option value="cartao_combustivel">Cartão Combustível</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="fatura_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                                        <option value="">Usar veículo geral da nota</option>
                                                        @foreach($veiculos as $v)
                                                            <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-clean btn-icon btn-danger btn-remover-fat" title="Remover Parcela"><i class="la la-trash"></i></button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-lg-12 text-right">
                                        <button type="button" id="btn-adicionar-linha-fatura" class="btn btn-sm btn-light-primary font-weight-bold">
                                            <i class="la la-plus"></i> Adicionar Nova Parcela
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="fatura" id="fatura_json_input" value="{{json_encode($fatura)}}">
                            </div>
                        </div>
					</div>

					<div class="col-xl-12">
                        <div class="card card-custom gutter-b p-6">
                            <div class="row">
                                <div class="col-lg-7 border-right">
                                    <h4 class="mb-5 font-weight-bold text-dark">Configurações Finais</h4>
                                    <div class="row">
                                        <div class="form-group col-xl-6">
                                            <label>Categoria (Compra / Financeiro) <strong class="text-danger">*</strong></label>
                                            <select class="custom-select form-control" name="categoria_id" id="categoria_id" required>
                                                <option value="">-- Selecione --</option>
                                                @foreach($categoriasConta as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="categoria_conta_id" id="categoria_conta_id_hidden">
                                        </div>

                                        <div class="form-group col-xl-6">
                                            <label>Veículo Geral (Opcional)</label>
                                            <select class="custom-select form-control" name="veiculo_id" id="veiculo_geral">
                                                <option value="">-- Nenhum --</option>
                                                @foreach($veiculos as $v)
                                                    <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->modelo }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                    <div class="form-group col-xl-12 mt-3">
                                            <label class="font-weight-bold text-info" title="Usado na baixa automática">Conta Bancária para Baixa Imediata <i class="la la-info-circle"></i></label>
                                            <select name="conta_empresa_id" id="conta_empresa_id" class="form-control custom-select border-info">
                                                <option value="">-- Nenhuma (A Prazo / Manual) --</option>
                                                @if(isset($contasEmpresa))
                                                    @foreach($contasEmpresa as $conta)
                                                        <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>

                               <div class="col-xl-12">
                                  <div class="card card-custom gutter-b">
                                      <div class="card-header">
                                          <h3 class="card-title font-weight-bold text-dark">Informações Complementares da Nota Fiscal</h3>
                                      </div>
                                      <div class="card-body">
                                          <textarea class="form-control bg-light text-dark font-weight-bold" rows="4" readonly>{{ $infos['infCpl'] ?? 'Nenhuma informação complementar informada nesta nota.' }}</textarea>
                                      </div>
                                  </div>
                              </div>

                                <div class="col-lg-5 pl-8">
                                    <h4 class="mb-4">Total de Produtos: <strong class="text-primary float-right">R$ {{ moeda((float)$infos['vProd']) }}</strong></h4>
                                    <h5 class="mb-4">Desconto: <strong class="text-danger float-right">R$ {{ moeda((float)$infos['vDesc']) }}</strong></h5>
                                    <h3>Valor Líquido NFe: <strong class="text-success float-right">R$ {{ moeda((float)$infos['vNF']) }}</strong></h3>
                                </div>
                            </div>

                            @if($contaSemRegistro > 0)
                            <div class="alert alert-custom alert-light-danger mt-6 mb-0" role="alert">
                                <div class="alert-icon"><i class="flaticon-warning"></i></div>
                                <div class="alert-text font-weight-bold">* Para salvar como compra é preciso ter todos os produtos cadastrados no sistema (Use o botão verde + nos itens da tabela).</div>
                            </div>
                            @endif

                            @if($dfe->compra_id > 0)
                            <div class="alert alert-custom alert-light-info mt-6 mb-0" role="alert">
                                <div class="alert-icon"><i class="flaticon-info"></i></div>
                                <div class="alert-text font-weight-bold">* Este documento já está salvo em compras!</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-xl-12 mb-8">
                        <div class="row">
                            <div class="col-xl-2 col-sm-6 mb-2">
                                <a href="/dfe" class="btn btn-light-danger font-weight-bold w-100">
                                    <i class="la la-arrow-left"></i> Voltar
                                </a>
                            </div>
                            <div class="col-xl-2 col-sm-6 mb-2">
                                <a href="/dfe/downloadXml/{{$infos['chave']}}" class="btn btn-light-info font-weight-bold w-100">
                                    <i class="la la-file"></i> Baixar XML
                                </a>
                            </div>
                            <div class="col-xl-4 offset-xl-4 col-sm-12">
                                @if($dfe->compra_id == 0)
                                    <button @if($contaSemRegistro > 0) disabled @endif style="width: 100%" type="submit" class="btn btn-success font-weight-bold">
                                        <i class="la la-check"></i> Salvar como Compra
                                    </button>
                                @endif
                            </div>
                        </div>
					</div>
				</form>

                @if($dfe->venda_id == 0)
                <div class="col-xl-12 text-right pb-10">
                    <a href="/dfe/gerar-venda/{{$dfe->id}}" @if($contaSemRegistro > 0) disabled @endif class="btn btn-info font-weight-bold">
                        <i class="la la-file"></i> Gerar venda PDV
                    </a>
                </div>
                @else
                <div class="col-xl-12 text-right pb-10">
                    <a href="/nfce/detalhes/{{$dfe->venda_id}}" @if($contaSemRegistro > 0) disabled @endif class="btn btn-dark font-weight-bold">
                        <i class="la la-file"></i> Visualizar venda PDV
                    </a>
                </div>
                @endif

			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Adicionar produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
			</div>
			<div class="modal-body">
				<div class="wizard wizard-3" id="kt_wizard_v4" data-wizard-state="between" data-wizard-clickable="true">
					<div class="wizard-nav">
						<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
								<div class="wizard-label">
									<h3 class="wizard-title"><span>IDENTIFICAÇÃO</span></h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
								<div class="wizard-label">
									<h3 class="wizard-title"><span>ALÍQUOTAS</span></h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
						</div>
					</div>

					<div class="card-body">
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
							<form class="form fv-plugins-bootstrap fv-plugins-framework form-prod" id="kt_form">
	<div class="pb-5" data-wizard-type="step-content">
		<div class="row">
			<div class="form-group validated col-sm-10 col-lg-10">
				<label class="col-form-label">Nome do Produto</label>
				<div class="input-group">
					<input id="nome" type="text" class="form-control" name="nome" value="">
					<div class="input-group-append">
						<button onclick="linkProduto()" class="btn btn-info" type="button"><i class="la la-search"></i></button>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">NCM</label>
				<div class=""><input id="ncm" type="text" class="form-control" name="ncm" value=""></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-3">
				<label class="col-form-label">CEST</label>
				<div class=""><input type="text" id="CEST" class="form-control" name="CEST"></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">CFOP</label>
				<div class=""><input id="cfop" type="text" class="form-control" name="cfop" value=""></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">Referência</label>
				<div class=""><input id="referencia" type="text" class="form-control" name="referencia" value=""></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Conversão unitária</label>
				<div class=""><input id="conv_estoque" type="text" class="form-control" name="conversao_unitaria" value=""></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Quantidade</label>
				<div class=""><input id="quantidade" type="text" class="form-control" name="quantidade" value=""></div>
			</div>

			<input type="hidden" id="_token" name="_token" value="{{ csrf_token() }}">
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Valor de compra</label>
				<div class=""><input id="valor" type="text" class="form-control" name="valorCompra" value=""></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">% lucro*</label>
				<div class=""><input type="text" id="percentual_lucro" class="form-control money" name="percentual_lucro" value="{{$config->percentual_lucro_padrao ?? '0,00' }}"></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Valor de Venda</label>
				<div class=""><input id="valor_venda" type="text" class="form-control" name="valorVenda" value=""></div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Unidade de compra</label>
				<select class="custom-select form-control" id="unidade_compra" name="unidadeCompra">
					@if(isset($unidadesDeMedida))
					@foreach($unidadesDeMedida as $u) <option value="{{$u}}">{{$u}}</option> @endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Unidade de venda</label>
				<select class="custom-select form-control" id="unidade_venda" name="unidadeVenda">
					@if(isset($unidadesDeMedida))
					@foreach($unidadesDeMedida as $u) <option value="{{$u}}">{{$u}}</option> @endforeach
					@endif
				</select>
			</div>

			<div class="form-group validated col-sm-3 col-lg-4">
				<label class="col-form-label">Código de barras</label>
				<div class="input-group-prepend">
					<input id="codBarras" type="text" class="form-control" name="codBarras" value="">
					<span class="input-group-text btn-info btn" onclick="gerarCode()"><i class="la la-barcode"></i></span>
				</div>
			</div>
			<div class="form-group validated col-sm-3 col-lg-3">
				<label class="col-form-label">Estoque minimo</label>
				<div class=""><input type="text" id="estoque_minimo" class="form-control" name="estoque_minimo" value="0"></div>
			</div>

			<div class="form-group validated col-sm-4 col-lg-4">
				<label class="col-form-label">Categoria</label>
				<select class="custom-select form-control" id="categoria_id" name="categoria_id">
					@if(isset($categorias))
					@foreach($categorias as $cat) <option value="{{$cat->id}}">{{$cat->nome}}</option> @endforeach
					@endif
				</select>
			</div>

			<div class="form-group validated col-sm-6 col-lg-3">
				<label class="col-form-label">Gerenciar estoque</label>
				<div class="col-6">
					<span class="switch switch-outline switch-primary">
						<label><input value="true" @if(isset($config) && $config->gerenciar_estoque_produto == 1) checked @endif type="checkbox" id="gerenciar_estoque" name="gerenciar_estoque"><span></span></label>
					</span>
				</div>
			</div>

			<div class="form-group validated col-sm-6 col-lg-2">
				<label class="col-form-label">Inativo</label>
				<div class="col-6">
					<span class="switch switch-outline switch-danger">
						<label><input value="true" type="checkbox" id="inativo" name="inativo"><span></span></label>
					</span>
				</div>
			</div>

			<hr>
			<div class="form-group validated col-12"><h3>Derivado Petróleo</h3></div>
			<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
				<label class="col-form-label">ANP</label>
				<select class="custom-select form-control" id="anp" name="anp">
					<option value="">--</option>
					@if(isset($anps))
					@foreach($anps as $key => $a) <option value="{{$key}}">[{{$key}}] - {{$a}}</option> @endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">%GLP</label>
				<input type="text" id="perc_glp" name="perc_glp" class="form-control trib" value="0">
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">%GNn</label>
				<input type="text" id="perc_gnn" name="perc_gnn" class="form-control trib" value="0">
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">%GNi</label>
				<input type="text" id="perc_gni" name="perc_gni" class="form-control trib" value="0">
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">Valor de partida</label>
				<input type="text" id="valor_partida" name="valor_partida" class="form-control money" value="0">
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">Un. tributável</label>
				<input type="text" id="unidade_tributavel" name="unidade_tributavel" class="form-control" data-mask="AAAA">
			</div>
			<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
				<label class="col-form-label">Qtd. tributável</label>
				<input type="text" id="quantidade_tributavel" name="quantidade_tributavel" class="form-control" data-mask="00000,00" data-mask-reverse="true" value="0">
			</div>

			<hr>
			<div class="form-group validated col-12"><h3>Dados de dimensão e peso do produto (Opcional)</h3></div>
			<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
				<label class="col-form-label">Largura (cm)</label><input type="text" id="largura" name="largura" class="form-control" value="0">
			</div>
			<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
				<label class="col-form-label">Altura (cm)</label><input type="text" id="altura" name="altura" class="form-control" value="0">
			</div>
			<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
				<label class="col-form-label">Comprimento (cm)</label><input type="text" id="comprimento" name="comprimento" class="form-control" value="0">
			</div>
			<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
				<label class="col-form-label">Peso liquido</label><input type="text" id="peso_liquido" name="peso_liquido" class="form-control" value="0">
			</div>
			<div class="form-group validated col-lg-2 col-md-4 col-sm-4">
				<label class="col-form-label">Peso bruto</label><input type="text" id="peso_bruto" name="peso_bruto" class="form-control" value="0">
			</div>
		</div>
	</div>

	<div class="pb-5" data-wizard-type="step-content">
		<div class="row">
			<div class="form-group validated col-sm-6 col-lg-12">
				<label class="col-form-label">CST/CSOSN</label>
				<select class="custom-select form-control" id="CST_CSOSN" name="CST_CSOSN">
					@if(isset($listaCSTCSOSN))
					@foreach($listaCSTCSOSN as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-sm-6 col-lg-6">
				<label class="col-form-label">CST PIS</label>
				<select class="custom-select form-control" id="CST_PIS" name="CST_PIS">
					@if(isset($listaCST_PIS_COFINS))
					@foreach($listaCST_PIS_COFINS as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-sm-6 col-lg-6">
				<label class="col-form-label">CST COFINS</label>
				<select class="custom-select form-control" id="CST_COFINS" name="CST_COFINS">
					@if(isset($listaCST_PIS_COFINS))
					@foreach($listaCST_PIS_COFINS as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-sm-6 col-lg-6">
				<label class="col-form-label">CST IPI</label>
				<select class="custom-select form-control" id="CST_IPI" name="CST_IPI">
					@if(isset($listaCST_IPI))
					@foreach($listaCST_IPI as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
					@endif
				</select>
			</div>
			<div class="form-group validated col-6">
				<label class="col-form-label">Código de enquandramento de IPI *</label>
				<select class="custom-select form-control" id="cenq_ipi" name="cenq_ipi">
					@foreach(App\Models\Produto::listaCenqIPI() as $key => $c)
					<option value="{{$key}}" @if($key == '999') selected @endif >{{$c}}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%ICMS</label><div class=""><input id="perc_icms" type="text" class="form-control trib" name="perc_icms" value="0"></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%PIS</label><div class=""><input id="perc_pis" type="text" class="form-control trib" name="perc_pis" value="0"></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%COFINS</label><div class=""><input id="perc_cofins" type="text" class="form-control trib" name="perc_cofins" value="0"></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%IPI</label><div class=""><input id="perc_ipi" type="text" class="form-control trib" name="perc_ipi" value="0"></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%ISS</label><div class=""><input id="perc_iss" type="text" class="form-control trib" name="perc_iss" value="0"></div>
			</div>
			<div class="form-group validated col-sm-2 col-lg-2">
				<label class="col-form-label">%Redução BC</label><div class=""><input id="pRedBC" type="text" class="form-control trib" name="pRedBC" value="0"></div>
			</div>

			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%ICMS ST *</label><input type="text" id="pICMSST" class="form-control trib" name="pICMSST" value="0">
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">Cod. benefício</label><input type="text" id="cBenef" class="form-control" name="cBenef" >
			</div>
			<div class="form-group validated col-sm-6 col-lg-4">
				<label class="col-form-label">Origem</label>
				<div class="">
					<select name="origem" id="origem" class="custom-select">
						@foreach(App\Models\Produto::origens() as $key => $o)
						<option value="{{$key}}">{{$key}} - {{$o}}</option>
						@endforeach
					</select>
				</div>
			</div>

			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%ICMS interestadual</label><input type="text" id="perc_icms_interestadual" class="form-control trib" name="perc_icms_interestadual" value="0">
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%ICMS interno</label><input type="text" id="perc_icms_interno" class="form-control trib" name="perc_icms_interno" value="0">
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%FCP interestadual</label><div class=""><input type="text" id="perc_fcp_interestadual" class="form-control trib" name="perc_fcp_interestadual" value="0"></div>
			</div>

			<div class="form-group validated col-sm-4 col-lg-3">
				<label class="col-form-label">CFOP entrada interno</label><input type="text" id="CFOP_entrada_estadual" class="form-control" name="CFOP_entrada_estadual">
			</div>
			<div class="form-group validated col-sm-4 col-lg-3">
				<label class="col-form-label">CFOP entrada externo</label><input type="text" id="CFOP_entrada_inter_estadual" class="form-control" name="CFOP_entrada_inter_estadual">
			</div>

			<div class="form-group validated col-sm-6 col-lg-4">
				<label class="col-form-label">Modalidade Det.</label>
				<div class="">
					<select name="modBC" id="modBC" class="custom-select">
						@foreach(App\Models\Produto::modalidadesDeterminacao() as $key => $o)
						<option value="{{$key}}">{{$key}} - {{$o}}</option>
						@endforeach
					</select>
				</div>
			</div>

			<div class="form-group validated col-sm-6 col-lg-4">
				<label class="col-form-label">Modalidade Det. ST</label>
				<div class="">
					<select id="modBCST" name="modBCST" class="custom-select">
						@foreach(App\Models\Produto::modalidadesDeterminacaoST() as $key => $o)
						<option value="{{$key}}">{{$key}} - {{$o}}</option>
						@endforeach
					</select>
				</div>
			</div>

			<div class="form-group validated col-lg-12 col-md-10 col-sm-10">
				<label class="col-form-label">CST/CSOSN entrada</label>
				<select class="custom-select form-control" id="CST_CSOSN_entrada" name="CST_CSOSN_entrada">
					@if(isset($listaCSTCSOSN))
					@foreach($listaCSTCSOSN as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
					@endif
				</select>
			</div>

			<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
				<label class="col-form-label">CST PIS entrada</label>
				<select class="custom-select form-control" id="CST_PIS_entrada" name="CST_PIS_entrada">
					@foreach(App\Models\Produto::listaCST_PIS_COFINS_Entrada() as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
				<label class="col-form-label">CST COFINS entrada</label>
				<select class="custom-select form-control" id="CST_COFINS_entrada" name="CST_COFINS_entrada">
					@foreach(App\Models\Produto::listaCST_PIS_COFINS_Entrada() as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group validated col-lg-6 col-md-10 col-sm-10">
				<label class="col-form-label">CST IPI entrada</label>
				<select class="custom-select form-control" id="CST_IPI_entrada" name="CST_IPI_entrada">
					@foreach(App\Models\Produto::listaCST_IPI_Entrada() as $key => $c)
					<option value="{{$key}}">{{$key}} - {{$c}}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%Custo Frete</label>
				<input type="text" id="perc_frete" class="form-control trib" name="perc_frete" value="0">
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%Outras Despesas</label>
				<input type="text" id="perc_outros" class="form-control trib" name="perc_outros" value="0">
			</div>
			<div class="form-group validated col-sm-3 col-lg-2">
				<label class="col-form-label">%MLV</label>
				<input type="text" id="perc_mlv" class="form-control trib" name="perc_mlv" value="0">
			</div>

		</div>
	</div>
</form>
						</div>
					</div>
				</div>	
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="salvar" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Editar produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label">Nome do Produto</label>
						<div class=""><input id="nomeEdit" type="text" class="form-control" name="nomeEdit" value=""></div>
					</div>
				</div>
				<div class="row">
					<div class="form-group validated col-sm-6 col-lg-6">
						<label class="col-form-label">Conversão unitária para estoque</label>
						<div class=""><input id="conv_estoqueEdit" type="text" class="form-control" name="conv_estoqueEdit" value=""></div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="salvarEdit" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-link" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Atribuir ao produto</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">x</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<label class="col-form-label" id="">Produto</label><br>
						<input placeholder="Digite para buscar o produto" type="search" id="produto-search" class="form-control">
						<div class="search-prod" style="display: none"></div>
					</div>
				</div>
				<div class="row">
					<div class="form-group validated col-sm-3 col-lg-3 col-12">
						<label class="col-form-label">Quantidade</label>
						<div class=""><input id="estoque" type="text" class="form-control" name="estoque" value=""></div>
					</div>
					<div class="form-group validated col-sm-3 col-lg-3 col-12">
						<label class="col-form-label">Valor de venda</label>
						<div class=""><input id="valor_venda2" type="text" class="form-control money" name="valor_venda2" value=""></div>
					</div>
					<div class="form-group validated col-sm-3 col-lg-3 col-12">
						<label class="col-form-label">Valor de compra</label>
						<div class=""><input id="valor_compra2" type="text" class="form-control money" name="valor_compra2" value=""></div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="salvarLink" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        
        // --- 1. TRAVA DE DUPLICIDADE ---
        $('#form-salvar-compra').submit(function() {
            let btn = $(this).find('button[type="submit"]');
            btn.attr('disabled', 'disabled');
            btn.addClass('spinner spinner-white spinner-right');
        });

        // --- 3. INICIALIZAÇÃO DE MÁSCARAS ---
        if($('.money').length > 0) { $('.money').mask('#.##0,00', {reverse: true}); }
        if($('.date-input').length > 0) { $('.date-input').mask('00/00/0000'); }

        // --- 4. LÓGICA DE UNIFICAÇÃO DA CATEGORIA (Backend pega por 'categoria_id') ---
        $('#categoria_id').change(function() {
            $('#categoria_conta_id_hidden').val($(this).val());
        });

        // --- 5. LÓGICA DE CONDIÇÃO FINANCEIRA E RATEIO POR VEÍCULO ---
        $('#veiculo_geral').change(function() {
            let idVeiculo = $(this).val();
            $('.select-veiculo-parcela').val(idVeiculo);
            atualizarFaturaJson();
        });

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

                let totalNF = "{{ number_format((double)$infos['vNF'], 2, ',', '.') }}";
                let hoje = "{{ date('d/m/Y') }}";

                $('#tabela-fatura tbody').html(`
                    <tr>
                        <td><input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="001"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control money text-right font-weight-bold text-success" value="${totalNF}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select form-control">
                                <option value="boleto">Boleto</option>
                                <option value="pix">Pix</option>
                                <option value="transferencia">Transferência</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="cartao_combustivel">Cartão Combustível</option>
                            </select>
                        </td>
                        <td>
                            <select name="fatura_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `);
                $('.money').mask('#.##0,00', {reverse: true});
                $('.date-input').mask('00/00/0000');
                $('#tabela-fatura tbody .select-veiculo-parcela').val($('#veiculo_geral').val());
                atualizarFaturaJson();
            } else {
                location.reload(); // Recarrega para voltar o XML ao normal se desistir
            }
        });

        $('#veiculos_rateio').change(function() { executarRateioVeiculos(); });

        function executarRateioVeiculos() {
            let veiculosSelecionados = $('#veiculos_rateio').val();
            if (!veiculosSelecionados || veiculosSelecionados.length === 0) {
                $('#tabela-fatura tbody').html('<tr><td colspan="5" class="text-center text-danger">Selecione pelo menos um veículo no campo acima!</td></tr>');
                return;
            }

            let totalNF = parseFloat("{{ $infos['vNF'] }}");
            let qtdVeiculos = veiculosSelecionados.length;
            let valorFatiado = (totalNF / qtdVeiculos).toFixed(2);
            let valorFormatado = parseFloat(valorFatiado).toLocaleString('pt-br', {minimumFractionDigits: 2});
            let hoje = "{{ date('d/m/Y') }}";
            let html = '';

            veiculosSelecionados.forEach(function(veiculoId, index) {
                let numeroParcela = String(index + 1).padStart(3, '0');
                html += `
                    <tr>
                        <td><input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="${numeroParcela}"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select form-control">
                                <option value="boleto">Boleto</option>
                                <option value="pix">Pix</option>
                                <option value="transferencia">Transferência</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="cartao_combustivel">Cartão Combustível</option>
                            </select>
                        </td>
                        <td>
                            <select name="fatura_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}" ${veiculoId == "{{ $v->id }}" ? 'selected' : ''}>{{ $v->placa }} - {{ $v->modelo }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `;
            });

            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            atualizarFaturaJson();
        }

        $('#btn_gerar_parcelas').click(function() {
            let qtd = parseInt($('#qtd_parcelas_manual').val()) || 1;
            let totalNF = parseFloat("{{ $infos['vNF'] }}");
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
                        <td><input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="${String(i).padStart(3, '0')}"></td>
                        <td><input type="text" name="fatura_venc[]" class="form-control date-input text-center" value="${dataStr}"></td>
                        <td><input type="text" name="fatura_val[]" class="form-control money text-right font-weight-bold text-success" value="${valorFormatado}"></td>
                        <td>
                            <select name="forma_pagamento[]" class="custom-select form-control">
                                <option value="boleto">Boleto</option>
                                <option value="pix">Pix</option>
                                <option value="transferencia">Transferência</option>
                                <option value="adiantamento">Adiantamento Fornecedor</option>
                                <option value="dinheiro">Dinheiro (Caixa)</option>
                                <option value="cartao_combustivel">Cartão Combustível</option>
                            </select>
                        </td>
                        <td>
                            <select name="fatura_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                                <option value="">Usar veículo geral da nota</option>
                                @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                        </td>
                    </tr>
                `;
            }
            $('#tabela-fatura tbody').html(html);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            $('.select-veiculo-parcela').val($('#veiculo_geral').val());
            atualizarFaturaJson();
        });

        $('#btn-adicionar-linha-fatura').click(function() {
            let numLinhas = $('#tabela-fatura tbody tr').length + 1;
            let numFormatado = String(numLinhas).padStart(3, '0');
            let hoje = "{{ date('d/m/Y') }}";

            let novaLinha = `
                <tr>
                    <td><input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="${numFormatado}"></td>
                    <td><input type="text" name="fatura_venc[]" class="form-control date-input text-center" value="${hoje}"></td>
                    <td><input type="text" name="fatura_val[]" class="form-control money text-right font-weight-bold text-success" value="0,00"></td>
                    <td>
                        <select name="forma_pagamento[]" class="custom-select form-control">
                            <option value="boleto">Boleto</option>
                            <option value="pix">Pix</option>
                            <option value="transferencia">Transferência</option>
                            <option value="adiantamento">Adiantamento Fornecedor</option>
                            <option value="dinheiro">Dinheiro (Caixa)</option>
                            <option value="cartao_combustivel">Cartão Combustível</option>
                        </select>
                    </td>
                    <td>
                        <select name="fatura_veiculo[]" class="custom-select form-control select-veiculo-parcela">
                            <option value="">Usar veículo geral da nota</option>
                            @foreach($veiculos as $v) <option value="{{ $v->id }}">{{ $v->placa }}</option> @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-fat"><i class="la la-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#tabela-fatura tbody').append(novaLinha);
            $('.money').mask('#.##0,00', {reverse: true});
            $('.date-input').mask('00/00/0000');
            $('#tabela-fatura tbody tr:last .select-veiculo-parcela').val($('#veiculo_geral').val());
            atualizarFaturaJson();
        });

        $(document).on('click', '.btn-remover-fat', function() {
            if(confirm("Deseja remover esta parcela do financeiro?")) {
                $(this).closest('tr').remove();
                atualizarFaturaJson();
            }
        });

        $(document).on('blur', 'input[name="fatura_venc[]"], input[name="fatura_val[]"], input[name="fatura_num[]"]', function() {
            atualizarFaturaJson();
        });

        $(document).on('change', 'select[name="fatura_veiculo[]"]', function() {
            atualizarFaturaJson();
        });

        function atualizarFaturaJson() {
            let faturas = [];
            $('#tabela-fatura tbody tr').each(function() {
                let num = $(this).find('input[name="fatura_num[]"]').val();
                let venc = $(this).find('input[name="fatura_venc[]"]').val();
                let val = $(this).find('input[name="fatura_val[]"]').val();
                let forma = $(this).find('select[name="forma_pagamento[]"]').val();
                let veiculo = $(this).find('select[name="fatura_veiculo[]"]').val();
                
                if(num && venc && val) {
                    faturas.push({
                        numero: num,
                        vencimento: venc,
                        valor_parcela: val,
                        forma_pagamento: forma,
                        veiculo_id: veiculo
                    });
                }
            });
            $('#fatura_json_input').val(JSON.stringify(faturas));
        }

        // --- REGRA FISCAL INTELIGENTE NA FINALIDADE ---
        $(document).on('change', '.finalidade_input', function() {
            let tr = $(this).closest('tr');
            let finalidade = $(this).val();
            let cfopOriginal = tr.find('.cfop').val();
            let isInterestadual = (cfopOriginal.startsWith('6'));

            let cfopSugerido = '';
            let cstSugerido = '';
            let pisCofinsSugerido = '';

            if (finalidade === 'uso_consumo_sem_credito') {
                cfopSugerido = isInterestadual ? '2556' : '1556';
                cstSugerido = '090';
                pisCofinsSugerido = '70'; // Sem crédito PIS/COFINS

            } else if (finalidade === 'uso_consumo_com_credito') {
                cfopSugerido = isInterestadual ? '2556' : '1556';
                cstSugerido = '090';
                pisCofinsSugerido = '50'; // Com crédito PIS/COFINS

            } else if (finalidade === 'combustivel') {
                cfopSugerido = isInterestadual ? '2653' : '1653';
                cstSugerido = '061'; // Monofásico
                pisCofinsSugerido = '50'; // Com crédito

            } else if (finalidade === 'imobilizado') {
                cfopSugerido = isInterestadual ? '2551' : '1551';
                cstSugerido = '090';
                pisCofinsSugerido = '70';

            } else {
                // Lógica Padrão de Revenda
                if (cfopOriginal === '5102') cfopSugerido = '1102';
                else if (cfopOriginal === '6102') cfopSugerido = '2102';
                else cfopSugerido = isInterestadual ? '2' + cfopOriginal.substring(1) : '1' + cfopOriginal.substring(1);

                cstSugerido = tr.find('.vbc_icms').val() > 0 ? '000' : '090';
                pisCofinsSugerido = '01';
            }

            // Aplica os valores instantaneamente na tela
            tr.find('.cfop_entrada_input').val(cfopSugerido);
            tr.find('.cst_icms_input').val(cstSugerido);
            tr.find('.cst_pis_input').val(pisCofinsSugerido);
            tr.find('.cst_cofins_input').val(pisCofinsSugerido);
        });

        // DETECTA COMBUSTÍVEL AUTOMATICAMENTE AO ABRIR O XML
        function aplicarInteligenciaCombustivel() {
            $('#kt_datatable tbody tr').each(function() {
                let tr = $(this);
                let nomeProduto = tr.find('.nome').text().toUpperCase();
                let cfopXML = tr.find('.cfop').val();
                let ncmXML = tr.find('.ncm').text().replace(/\./g, '');

                let ehCombustivel = nomeProduto.includes('DIESEL') ||
                                    nomeProduto.includes('ARLA') ||
                                    nomeProduto.includes('GASOLINA') ||
                                    ['5656', '6656', '5653', '6653', '5652', '6652'].includes(cfopXML) ||
                                    ncmXML === '27101921';

                if (ehCombustivel) {
                    tr.find('.finalidade_input').val('combustivel').trigger('change');
                    tr.css('background-color', '#e8f5e9'); // Fundo verde claro para destacar o combustível
                }
            });
        }

        aplicarInteligenciaCombustivel();

    });
</script>
@endsection
