@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover { cursor: pointer }
	.search-prod {
		position: absolute; top: 0; margin-top: 80px; left: 10; width: 100%;
		max-height: 200px; overflow: auto; z-index: 9999; border: 1px solid #eeeeee;
		border-radius: 4px; background-color: #fff; box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
	}
	.search-prod label:hover { cursor: pointer; }
	.search-prod label { margin-left: 10px; width: 100%; margin-top: 7px; font-size: 14px; }
	.delete-parcela:hover { cursor: pointer; }
</style>

<div class="row" id="anime" style="display: none">
	<div class="col s8 offset-s2">
		<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay></lottie-player>
	</div>
</div>

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div id="content" style="display: block">
		<div class="container-fluid @if(env('ANIMACAO')) animate__animated @endif animate__fadeIn">

			<input type="hidden" name="id" value="{{{ isset($cliente) ? $cliente->id : 0 }}}">
            @csrf

            <div class="card card-custom gutter-b shadow-sm border-0 mt-6">
                <div class="card-body p-6">
                    <div class="row align-items-center mb-6">
                        <div class="col-md-8">
                            <h2 class="font-weight-bolder text-dark mb-0">Importando XML: <span class="text-primary">#{{$dadosNf['nNf']}}</span></h2>
                            <p class="text-muted font-weight-bold font-size-lg mt-1">Chave: {{$dadosNf['chave']}}</p>
                        </div>
                        <div class="col-md-4 text-right">
                            <span class="label label-xl label-light-success label-inline font-weight-bold py-4 px-6">
                                Emissão: {{ \Carbon\Carbon::parse($dadosNf['data_emissao'])->format('d/m/Y H:i')}}
                            </span>
                        </div>
                    </div>

                    @if(count($dadosAtualizados) > 0)
                        <div class="alert alert-custom alert-light-success mb-5" role="alert">
                            <div class="alert-icon"><i class="flaticon2-check-mark"></i></div>
                            <div class="alert-text">
                                <h6 class="font-weight-bold mb-1">Dados atualizados do fornecedor:</h6>
                                <ul class="mb-0 pl-4">
                                    @foreach($dadosAtualizados as $d) <li>{{$d}}</li> @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="row bg-light-secondary p-5 rounded">
                        <div class="col-md-6 border-right">
                            <h5 class="text-dark font-weight-bolder mb-4"><i class="flaticon2-delivery-truck text-primary mr-2"></i> Fornecedor</h5>
                            <p class="mb-1">Razão Social: <strong>{{$dadosEmitente['razaoSocial']}}</strong></p>
                            <p class="mb-1">Nome Fantasia: <strong>{{$dadosEmitente['nomeFantasia']}}</strong></p>
                            <p class="mb-1">CNPJ: <strong>{{$dadosEmitente['cnpj']}}</strong> | IE: <strong>{{$dadosEmitente['ie']}}</strong></p>
                        </div>
                        <div class="col-md-6 pl-6">
                            <h5 class="text-dark font-weight-bolder mb-4"><i class="flaticon2-map text-success mr-2"></i> Endereço</h5>
                            <p class="mb-1">{{$dadosEmitente['logradouro']}}, {{$dadosEmitente['numero']}} - {{$dadosEmitente['bairro']}}</p>
                            <p class="mb-1">{{$dadosEmitente['cidade']}} | CEP: {{$dadosEmitente['cep']}}</p>
                            <p class="mb-0">Fone: <strong>{{$dadosEmitente['fone']}}</strong></p>
                        </div>
                    </div>

                    @if(isset($saldo_credito) && $saldo_credito > 0)
                    <div class="alert alert-custom alert-light-warning mt-5 mb-0" role="alert">
                        <div class="alert-icon"><i class="flaticon-warning"></i></div>
                        <div class="alert-text w-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="font-weight-bolder mb-1">Crédito de Adiantamento Disponível: R$ {{ number_format($saldo_credito, 2, ',', '.') }}</h6>
                                    <span class="text-muted">Deseja aproveitar este crédito nesta importação?</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="switch switch-sm switch-outline switch-icon switch-warning mr-4">
                                        <label>
                                            <input type="checkbox" id="usar_credito">
                                            <span></span>
                                        </label>
                                    </span>
                                    <div class="div-valor-credito" style="display: none; width: 150px;">
                                        <input type="text" class="form-control form-control-sm" id="valor_credito_usar" value="{{ $saldo_credito }}" placeholder="Valor">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <input type="hidden" id="pathXml" value="{{$pathXml}}">
                    <input type="hidden" id="idFornecedor" value="{{$idFornecedor}}">
                    <input type="hidden" id="nNf" value="{{$dadosNf['nNf']}}">
                    <input type="hidden" id="data_emissao" value="{{$dadosNf['data_emissao']}}">
                    <input type="hidden" id="vDesc" value="{{$dadosNf['vDesc']}}">
                    <input type="hidden" id="prodSemRegistro" value="{{$dadosNf['contSemRegistro']}}">
                    <input type="hidden" id="chave" value="{{$dadosNf['chave']}}">
                    <input type="hidden" id="vbc_icms_total" value="{{$dadosNf['vbc_icms']}}">
                    <input type="hidden" id="v_icms_total" value="{{$dadosNf['v_icms']}}">
                    <input type="hidden" id="v_ipi_total" value="{{$dadosNf['v_ipi']}}">
                    <input type="hidden" id="v_pis_total" value="{{$dadosNf['v_pis']}}">
                    <input type="hidden" id="v_cofins_total" value="{{$dadosNf['v_cofins']}}">
                </div>
            </div>

            <div class="card card-custom gutter-b shadow-sm border-0">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title font-weight-bolder text-dark">Informações Complementares da NF-e</h3>
                </div>
                <div class="card-body pt-2">
                    <textarea class="form-control bg-light text-dark font-weight-bold border-0" rows="3" readonly>{{ $dadosNf['infCpl'] ?? 'Nenhuma informação complementar informada nesta nota.' }}</textarea>
                </div>
            </div>

            <div class="card card-custom gutter-b shadow-sm border-0">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title font-weight-bolder text-dark">
                        Itens da NF-e <span class="text-muted ml-2 font-size-sm">({{sizeof($itens)}} itens encontrados)</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    {!! __view_locais_select() !!}

                    @if($dadosNf['contSemRegistro'] > 0)
                        <div class="alert alert-custom alert-light-danger mt-3 mb-5 py-3" role="alert">
                            <div class="alert-icon"><i class="flaticon-warning"></i></div>
                            <div class="alert-text font-weight-bold">
                                Você possui <strong>{{$dadosNf['contSemRegistro']}}</strong> produto(s) sem registro (marcados em vermelho). É obrigatório cadastrá-los ou vinculá-los antes de salvar.
                            </div>
                        </div>
                    @endif

                    <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                        <table class="datatable-table" style="max-width: 100%; overflow-x: auto;">
                            <thead class="datatable-head bg-light">
                                <tr class="datatable-row" style="left: 0px;">
                                    <th class="datatable-cell"><span style="width: 70px;">#</span></th>
                                    <th class="datatable-cell"><span style="width: 180px;">Produto</span></th>
                                    <th class="datatable-cell"><span style="width: 120px;">Finalidade</span></th>
                                    <th class="datatable-cell"><span style="width: 80px;">NCM</span></th>
                                    <th class="datatable-cell"><span style="width: 70px;">CFOP. Ent</span></th>
                                    <th class="datatable-cell"><span style="width: 70px;">CST ICMS</span></th>
                                    <th class="datatable-cell"><span style="width: 70px;">PIS/COF</span></th>
                                <th class="datatable-cell"><span style="width: 70px;">IBS/CBS</span></th>
                                    <th class="datatable-cell"><span style="width: 90px;">Cod Barra</span></th>
                                    <th class="datatable-cell"><span style="width: 80px;">Valor</span></th>
                                    <th class="datatable-cell"><span style="width: 60px;">Qtd</span></th>
                                    <th class="datatable-cell"><span style="width: 80px;">Subtotal</span></th>
                                    <th class="datatable-cell"><span style="width: 80px;">Ações</span></th>
                                </tr>
                            </thead>
                            <tbody class="datatable-body">
                                @foreach($itens as $i)
                                <tr class="datatable-row" id="tr_{{$i['codigo']}}" style="left: 0px;">
                                    <td class="datatable-cell"><span class="codigo font-weight-bolder" style="width: 70px;">{{$i['codigo']}}</span></td>
                                    <td class="datatable-cell"><span style="width: 180px;" id="n_{{$i['codigo']}}" class="{{$i['produtoNovo'] ? 'text-danger font-weight-bold' : ''}} nome">{{$i['xProd']}}</span></td>

                                    <td class="datatable-cell">
                                        <span style="width: 120px;">
                                            <select class="form-control finalidade_input form-control-sm border-primary" style="width: 120px; font-size: 11px;">
                                                <option value="revenda">Revenda</option>
                                                <option value="uso_consumo_sem_credito">Uso/Consumo (S/ Crédito)</option>
                                                <option value="uso_consumo_com_credito">Uso/Consumo (C/ Crédito)</option>
                                                <option value="imobilizado">Imobilizado</option>
                                            </select>
                                        </span>
                                    </td>

                                    <td class="datatable-cell"><span class="ncm" style="width: 80px;">{{$i['NCM']}}</span></td>

                                    <td class="datatable-cell">
                                        <span style="width: 70px;">
                                            <input class="cfop_entrada_input form-control form-control-sm text-center" style="width: 65px;" type="text" value="{{$i['CFOP_entrada']}}">
                                            <input type="hidden" class="cfop" value="{{$i['CFOP']}}">
                                            <input type="hidden" class="CEST" value="{{$i['CEST']}}">
                                        </span>
                                    </td>
                                    <td class="datatable-cell">
                                        <span style="width: 70px;">
                                            <input class="cst_icms_input form-control form-control-sm text-center" style="width: 65px;" type="text" value="{{$i['cst_icms']}}">
                                            <input type="hidden" class="vbc_icms" value="{{$i['vbc_icms']}}">
                                            <input type="hidden" class="p_icms" value="{{$i['p_icms'] ?? 0}}">
                                            <input type="hidden" class="v_icms" value="{{$i['v_icms']}}">
                                        </span>
                                    </td>
                                    <td class="datatable-cell">
                                        <span style="width: 70px;">
                                            <input class="cst_pis_input form-control form-control-sm mb-1 text-center" style="width: 65px;" type="text" value="{{$i['cst_pis'] ?? ''}}" title="CST PIS" placeholder="PIS">
                                            <input class="cst_cofins_input form-control form-control-sm text-center" style="width: 65px;" type="text" value="{{$i['cst_cofins'] ?? ''}}" title="CST COFINS" placeholder="COF">
                                            <input type="hidden" class="vbc_pis" value="{{$i['vbc_pis'] ?? 0}}">
                                            <input type="hidden" class="p_pis" value="{{$i['p_pis'] ?? 0}}">
                                            <input type="hidden" class="v_pis" value="{{$i['v_pis'] ?? 0}}">
                                            <input type="hidden" class="vbc_cofins" value="{{$i['vbc_cofins'] ?? 0}}">
                                            <input type="hidden" class="p_cofins" value="{{$i['p_cofins'] ?? 0}}">
                                            <input type="hidden" class="v_cofins" value="{{$i['v_cofins'] ?? 0}}">

                                            <input type="hidden" class="cst_ibs_cbs" value="{{$i['cst_ibs_cbs'] ?? ''}}">
                                            <input type="hidden" class="bc_ibs_cbs" value="{{$i['bc_ibs_cbs'] ?? 0}}">
                                            <input type="hidden" class="aliq_ibs" value="{{$i['aliq_ibs'] ?? 0}}">
                                            <input type="hidden" class="aliq_cbs" value="{{$i['aliq_cbs'] ?? 0}}">
                                            <input type="hidden" class="valor_ibs" value="{{$i['valor_ibs'] ?? 0}}">
                                            <input type="hidden" class="valor_cbs" value="{{$i['valor_cbs'] ?? 0}}">
                                            <input type="hidden" class="class_trib_ibs_cbs" value="{{$i['class_trib'] ?? ''}}">
                                        </span>
                                    </td>
                                    <td class="datatable-cell">
                                        <span style="width: 80px; font-size: 11px;" class="text-muted d-block">
                                            <strong class="text-dark">CST:</strong> {{ $i['cst_ibs_cbs'] != '' ? $i['cst_ibs_cbs'] : 'S/N' }}<br>
                                            <strong class="text-dark">IBS:</strong> {{ number_format($i['aliq_ibs'], 2, ',', '.') }}% <br><span class="text-info">R$ {{ number_format($i['valor_ibs'], 2, ',', '.') }}</span><br>
                                            <strong class="text-dark">CBS:</strong> {{ number_format($i['aliq_cbs'], 2, ',', '.') }}% <br><span class="text-info">R$ {{ number_format($i['valor_cbs'], 2, ',', '.') }}</span>
                                        </span>

                                        <!-- Campos Hidden para envio do formulário -->
                                        <input type="hidden" class="cst_ibs_cbs" value="{{$i['cst_ibs_cbs']}}">
                                        <input type="hidden" class="bc_ibs_cbs" value="{{$i['bc_ibs_cbs']}}">
                                        <input type="hidden" class="aliq_ibs" value="{{$i['aliq_ibs']}}">
                                        <input type="hidden" class="aliq_cbs" value="{{$i['aliq_cbs']}}">
                                        <input type="hidden" class="valor_ibs" value="{{$i['valor_ibs']}}">
                                        <input type="hidden" class="valor_cbs" value="{{$i['valor_cbs']}}">
                                        <input type="hidden" class="class_trib_ibs_cbs" value="{{$i['class_trib'] ?? ''}}">
                                    </td>
                                    <td class="datatable-cell"><span class="codBarras text-muted" style="width: 90px;">{{$i['codBarras']}}</span></td>

                                    <td class="datatable-cell">
                                        <span class="valor font-weight-bold" style="width: 80px;">{{number_format((float)$i['vUnCom'], $casasDecimais ?? 2, ',', '.')}}</span>
                                        <input type="hidden" class="unidade" value="{{$i['uCom']}}">
                                    </td>
                                    <td class="datatable-cell"><span id="qtd_aux_{{$i['codigo']}}" class="quantidade" style="width: 60px;">{{$i['qCom']}}</span></td>

                                    <th class="cod" id="th_prod_id_{{$i['codigo']}}" style="display: none">{{$i['produtoId']}}</th>
                                    <th class="valor_venda" id="th_prod_valor_venda_{{$i['codigo']}}" style="display: none">-1</th>
                                    <th class="conv_estoque" id="th_prod_conv_unit_{{$i['codigo']}}" style="display:none">{{$i['conversao_unitaria'] ?? 1}}</th>
                                    <th class="valor_compra" id="th_prod_valor_compra_{{$i['codigo']}}" style="display:none">-1</th>

                                    <td class="datatable-cell quantidade"><span class="text-success font-weight-bolder" style="width: 80px;">R$ {{number_format((float) $i['qCom'] * (float) $i['vUnCom'], $casasDecimais ?? 2, ',', '.')}}</span></td>

                                    <td class="datatable-cell">
                                        <span style="width: 80px;" class="d-flex">
                                            <a id="th_acao1_{{$i['codigo']}}" @if($i['produtoNovo']) style="display: block" @else style="display: none" @endif onclick="cadProd('{{$i['codigo']}}','{{$i['xProd']}}','{{$i['codBarras']}}','{{$i['NCM']}}','{{$i['CFOP']}}','{{$i['uCom']}}','{{$i['vUnCom']}}','{{$i['qCom']}}','{{$i['CFOP_entrada']}}','{{$i['CEST']}}')" href="javascript:;" class="btn btn-sm btn-light-success btn-icon mr-2" title="Cadastrar Produto">
                                                <i class="la la-plus"></i>
                                            </a>
                                            <a id="th_acao2_{{$i['codigo']}}" @if($i['produtoNovo']) style="display: none" @else style="display: block" @endif onclick="editProd('{{$i['codigo']}}')" href="javascript:;" class="btn btn-sm btn-light-info btn-icon mr-2" title="Editar / Vincular">
                                                <i class="la la-edit"></i>
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

            <div class="card card-custom gutter-b shadow-sm border-0">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title font-weight-bolder text-dark">Financeiro e Rateio por Veículo</h3>
                </div>
                <div class="card-body pt-2">
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
                                        <input type="text" name="fatura_num[]" class="form-control text-center font-weight-bold" value="{{ str_pad($f['numero'], 3, '0', STR_PAD_LEFT) }}">
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
                                        <button type="button" class="btn btn-sm btn-clean btn-icon btn-danger btn-remover-fat" title="Remover Parcela">
                                            <i class="la la-trash"></i>
                                        </button>
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
                    <input type="hidden" id="fatura" value="{{json_encode($fatura)}}">
                    <input type="hidden" name="fatura_json" id="fatura_json_input" value="">
                </div>
            </div>

            <input type="hidden" id="total" value="{{$dadosNf['vProd']}}">
            <div class="card card-custom gutter-b shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-xl-3 border-right">
                            <h4 class="text-dark font-weight-bolder mb-1">Total da Nota</h4>
                            <h2 id="valorDaNF" class="text-primary font-weight-boldest">R$ {{ number_format((float)($dadosNf['vNF'] ?? $dadosNf['vProd']), 2, ',', '.') }}</h2>
                        </div>

                        <div class="col-xl-3">
                            <label class="font-weight-bold">Categoria de Conta <strong class="text-danger">*</strong></label>
                            <select class="custom-select form-control" id="categoria_conta_id" name="categoria_conta_id">
                                <option value="">Selecione...</option>
                                @foreach($categoriasDeConta as $c)
                                    <option value="{{$c->id}}">{{$c->nome}}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-xl-2">
                            <label class="font-weight-bold">Veículo Geral</label>
                            <select name="veiculo_id" id="veiculo_geral" class="form-control custom-select">
                                <option value="">Nenhum</option>
                                @foreach($veiculos as $v)
                                    <option value="{{ $v->id }}">{{ $v->placa }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-xl-2">
                            <label class="font-weight-bold" title="Usado na baixa de pagamentos à vista/cartão">Conta Bancária <i class="la la-info-circle"></i></label>
                            <select name="conta_empresa_id" id="conta_empresa_id" class="form-control custom-select border-info">
                                <option value="">-- Nenhuma (A Prazo) --</option>
                                @if(isset($contasEmpresa))
                                    @foreach($contasEmpresa as $conta)
                                        <option value="{{ $conta->id }}">{{ $conta->nome }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-xl-2">
                            <label class="font-weight-bold">Lote (Opcional)</label>
                            <input type="text" class="form-control" id="lote" placeholder="Ex: LOTE-001">
                        </div>

                        <div class="col-xl-12 mt-8 text-right border-top pt-6">
                            <a href="/compraFiscal" class="btn btn-light-danger font-weight-bolder px-8 mr-2"><i class="la la-close"></i> Cancelar</a>
                            <button id="salvarNF" disabled type="button" class="btn btn-success font-weight-bolder px-10">
                                <i class="la la-check"></i> Salvar Importação Completa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>

<input type="hidden" id="subs" value="{{json_encode($subs)}}">

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
                <input id="idEdit" type="hidden" class="form-control" name="idEdit" value="">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label">Nome do Produto</label>
						<div class=""><input id="nomeEdit" type="text" class="form-control" name="nomeEdit" value=""></div>
					</div>
				</div>
				<div class="row">
					<div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label">Conversão unitária para estoque</label>
						<div class=""><input id="conv_estoqueEdit" type="text" class="form-control" name="conv_estoqueEdit" value=""></div>
					</div>
                    <div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label">Valor de venda</label>
						<div class=""><input id="valorVendaEdit" type="text" class="form-control money" name="valorVendaEdit" value=""></div>
					</div>
					<div class="form-group validated col-sm-4 col-lg-4">
						<label class="col-form-label">Valor de compra</label>
						<div class=""><input id="valorCompraEdit" type="text" class="form-control money" name="valorCompraEdit" value=""></div>
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

<script src="/js/compraFiscal.js?v=2.3"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {

        // --- 1. MUDANÇA DINÂMICA DE CST E CFOP BASEADO NA FINALIDADE ---
        window.alertaCfopEstadoExibido = false;

        $(document).on('change', '.finalidade_input', function() {
            let tr = $(this).closest('tr');
            let finalidade = $(this).val();
            let cfopOriginal = tr.find('.cfop').val();

            let isInterestadual = (cfopOriginal.startsWith('6'));
            let ufEmitente = "{{ $dadosEmitente['uf'] ?? '' }}";
            let ufEmpresa = "{{ $config->UF ?? 'BA' }}";

            // Verifica se a nota é de fora, mas o CFOP veio começando com 5
            if (ufEmitente !== ufEmpresa && cfopOriginal.startsWith('5')) {
                isInterestadual = true;

                // Verifica a variável GLOBAL. Se for false, ele avisa e muda pra true.
                if(!window.alertaCfopEstadoExibido) {
                    alert("Atenção: Nota emitida por fornecedor de outro estado ("+ufEmitente+") com CFOP estadual (iniciado em 5). O sistema tratará a entrada como Interestadual (iniciado em 2) conforme regra fiscal.");
                    window.alertaCfopEstadoExibido = true; // Marca que já avisou nesta tela!
                }
            }

            let cfopSugerido = '';
            let cstSugerido = '';
            let pisCofinsSugerido = '70';

            if (finalidade === 'uso_consumo_sem_credito' || finalidade === 'uso_consumo_com_credito') {
                if (['5405', '5403', '6403', '6404'].includes(cfopOriginal)) {
                    cfopSugerido = isInterestadual ? '2407' : '1407';
                    cstSugerido = '060';
                } else {
                    cfopSugerido = isInterestadual ? '2556' : '1556';
                    cstSugerido = '090';
                }
                pisCofinsSugerido = (finalidade === 'uso_consumo_com_credito') ? '50' : '70';

            } else if (finalidade === 'imobilizado') {
                cfopSugerido = isInterestadual ? '2551' : '1551';
                cstSugerido = '090';
                pisCofinsSugerido = '70';

            } else {
                if (cfopOriginal === '5102') cfopSugerido = '1102';
                else if (cfopOriginal === '6102') cfopSugerido = '2102';
                else if (cfopOriginal === '5656' || cfopOriginal === '1656') cfopSugerido = '1653';
                else if (cfopOriginal === '6656' || cfopOriginal === '2656') cfopSugerido = '2653';
                else cfopSugerido = isInterestadual ? '2' + cfopOriginal.substring(1) : '1' + cfopOriginal.substring(1);

                cstSugerido = tr.find('.vbc_icms').val() > 0 ? '000' : '090';
                pisCofinsSugerido = '01';
            }

            tr.find('.cfop_entrada_input').val(cfopSugerido);
            tr.find('.cst_icms_input').val(cstSugerido);
            tr.find('.cst_pis_input').val(pisCofinsSugerido);
            tr.find('.cst_cofins_input').val(pisCofinsSugerido);
        });

        // --- 2. LÓGICA DE CONDIÇÃO FINANCEIRA E RATEIO POR VEÍCULO ---
        if($('.money').length > 0) { $('.money').mask('#.##0,00', {reverse: true}); }
        if($('.date-input').length > 0) { $('.date-input').mask('00/00/0000'); }
        if($('.select2-custom').length > 0) { $('.select2-custom').select2(); }

        $('#veiculo_geral').change(function() {
            let idVeiculo = $(this).val();
            $('.select-veiculo-parcela').val(idVeiculo);
            atualizarFaturaJson();
        });

        // Sincroniza a conta da tela com os itens gerados
        $('#conta_empresa_id').change(function() {
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

                let totalNF = "{{ number_format((double)($dadosNf['vNF'] ?? $dadosNf['vProd']), 2, ',', '.') }}";
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
                alert('Para restaurar as faturas originais, por favor, importe o XML novamente.');
                window.location.href = '/compraFiscal';
            }
        });

        $('#veiculos_rateio').change(function() { executarRateioVeiculos(); });

        function executarRateioVeiculos() {
            let veiculosSelecionados = $('#veiculos_rateio').val();
            if (!veiculosSelecionados || veiculosSelecionados.length === 0) {
                $('#tabela-fatura tbody').html('<tr><td colspan="6" class="text-center text-danger">Selecione pelo menos um veículo!</td></tr>');
                return;
            }

            let totalNF = parseFloat("{{ $dadosNf['vNF'] ?? $dadosNf['vProd'] }}");
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
            let totalNF = parseFloat("{{ $dadosNf['vNF'] ?? $dadosNf['vProd'] }}");
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

      // ==============================================================
      // INTELIGÊNCIA AUTOMÁTICA: COMBUSTÍVEIS E LUBRIFICANTES
      // ==============================================================
      function aplicarInteligenciaCombustivel() {
          $('#kt_datatable tbody tr').each(function() {
              let tr = $(this);
              let nomeProduto = tr.find('.nome').text().toUpperCase();
              let cfopXML = tr.find('.cfop').val();

              // Pega o NCM e tira os pontos (de 2710.19.21 para 27101921) para comparar com segurança
              let ncmXML = tr.find('.ncm').text().replace(/\./g, '');

              // Verifica Nome, CFOP ou o NCM exato do Diesel (27101921)
              let ehCombustivel = nomeProduto.includes('DIESEL') ||
                                  nomeProduto.includes('ARLA') ||
                                  nomeProduto.includes('GASOLINA') ||
                                  ['5656', '6656', '5653', '6653', '5652', '6652'].includes(cfopXML) ||
                                  ncmXML === '27101921';

              if (ehCombustivel) {
                  // 1. Muda a finalidade automaticamente para Uso/Consumo com Crédito
                  tr.find('.finalidade_input').val('uso_consumo_com_credito');

                  // 2. Define se a compra foi dentro (1) ou fora (2) do estado
                  let isInterestadual = cfopXML.startsWith('6');
                  let cfopEntrada = isInterestadual ? '2653' : '1653';

                  // 3. Aplica os valores na tela (CST ICMS 061)
                  tr.find('.cfop_entrada_input').val(cfopEntrada);
                  tr.find('.cst_icms_input').val('061'); // Tributação Monofásica (Combustíveis)
                  tr.find('.cst_pis_input').val('50');   // Direito a crédito
                  tr.find('.cst_cofins_input').val('50'); // Direito a crédito

                  // 4. (Opcional) Deixa a linha com um fundo verde clarinho
                  tr.css('background-color', '#e8f5e9');

                  // Força a atualização do select
                  tr.find('.finalidade_input').trigger('change');
              }
          });
      }

      // Chama a inteligência assim que a tela terminar de carregar os itens!
      aplicarInteligenciaCombustivel();


        $(document).on('click', '.btn-remover-fat', function() {
            if(confirm("Deseja remover esta parcela?")) {
                $(this).closest('tr').remove();
                atualizarFaturaJson();
            }
        });

        $(document).on('blur change', 'input[name="fatura_venc[]"], input[name="fatura_val[]"], select[name="fatura_veiculo[]"], select[name="forma_pagamento[]"]', function() {
            atualizarFaturaJson();
        });

        function atualizarFaturaJson() {
        let faturas = [];
        let contaEmpresaSelecionada = $('#conta_empresa_id').val();

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
                    veiculo_id: veiculo,
                    conta_empresa_id: contaEmpresaSelecionada
                });
            }
        });
        $('#fatura').val(JSON.stringify(faturas));
    }

// A função que realmente salva a nota toda
$('#salvarNF').click(function() {
    $('#salvarNF').addClass('spinner').attr('disabled', 'disabled');
    $('#preloader2').css('display', 'block');

    // 1. Salva Cabeçalho da NF
    let js = {
        fornecedor_id: $('#idFornecedor').val(),
        nNf: $('#nNf').val(),
        data_emissao: $('#data_emissao').val(),
        valor_nf: $('#valorDaNF').html().replace('R$','').replace(/\s/g, '').replace('.','').replace(',','.'),
        lote: $('#lote').val(),
        xml_path: $('#pathXml').val(),
        categoria_conta_id: $('#categoria_conta_id').val(),
        chave: $('#chave').val(),
        filial_id: $('#filial_id').length ? $('#filial_id').val() : -1,
        veiculo_id: $('#veiculo_geral').val(),
        conta_empresa_id: $('#conta_empresa_id').val()
    };

    // Usando .post com .done e .fail para pegar os erros
    $.post(path + 'compraFiscal/salvarNfFiscal', { nf: js, _token: $('#_token').val() })
    .done((data) => {
        if(data.id) {
            // 2. Salva Itens (CORRIGIDO: Pegando APENAS as linhas da tabela de produtos)
            let linhasItens = $('#kt_datatable tbody tr');
            let totalItens = linhasItens.length;
            let itensSalvos = 0;

            linhasItens.each(function() {
                let tr = $(this); // Aquela variável salvadora!

                let item = {
                    compra_id: data.id,
                    produto_id: parseInt(tr.find('.cod').html()),
                    codigo: tr.find('.codigo').html(),
                    xProd: tr.find('.nome').html(),
                    codBarras: tr.find('.codBarras').html(),
                    quantidade: tr.find('.quantidade').eq(0).text() ? tr.find('.quantidade').eq(0).text().replace(',','.') : tr.find('input[name="quantidade[]"]').val(),
                    unidade: tr.find('.unidade').val() || 'UN',
                    valor: tr.find('.valor').html().replace('.','').replace(',','.'),
                    cfop: tr.find('.cfop').val(),
                    cfop_entrada: tr.find('.cfop_entrada_input').val(),
                    cst_icms: tr.find('.cst_icms_input').val(),
                    cst_pis: tr.find('.cst_pis_input').val(),
                    cst_cofins: tr.find('.cst_cofins_input').val(),
                    finalidade: tr.find('.finalidade_input').val(),

                    vbc_icms: tr.find('.vbc_icms').val(),
                    p_icms: tr.find('.p_icms').val(),
                    v_icms: tr.find('.v_icms').val(),
                    vbc_pis: tr.find('.vbc_pis').val(),
                    p_pis: tr.find('.p_pis').val(),
                    v_pis: tr.find('.v_pis').val(),
                    vbc_cofins: tr.find('.vbc_cofins').val(),
                    p_cofins: tr.find('.p_cofins').val(),
                    v_cofins: tr.find('.v_cofins').val(),

                    cst_ibs_cbs: tr.find('.cst_ibs_cbs').val(),
                    bc_ibs_cbs: tr.find('.bc_ibs_cbs').val(),
                    aliq_ibs: tr.find('.aliq_ibs').val(),
                    aliq_cbs: tr.find('.aliq_cbs').val(),
                    valor_ibs: tr.find('.valor_ibs').val(),
                    valor_cbs: tr.find('.valor_cbs').val(),
                    class_trib_ibs_cbs: tr.find('.class_trib_ibs_cbs').val()
                };

                $.post(path + 'compraFiscal/salvarItem', { produto: item, _token: $('#_token').val() })
                .done(() => {
                    itensSalvos++;
                    if(itensSalvos == totalItens) {

                        // 3. Salva Faturas (Contas a Pagar e Baixas)
                        let faturas = JSON.parse($('#fatura').val());
                        let faturasSalvas = 0;

                        if(faturas.length == 0) sucesso();

                        faturas.map((f) => {
                            f.compra_id = data.id;
                            $.post(path + 'compraFiscal/salvarParcela', { parcela: f, _token: $('#_token').val() })
                            .done(() => {
                                faturasSalvas++;
                                if(faturasSalvas == faturas.length) sucesso();
                            })
                            .fail((err) => {
                            // CAPTURA O ERRO REAL QUE O PHP DEVOLVEU
                            let erroDetalhado = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : err.statusText;

                            console.log(err);
                            swal("Erro no Contas a Pagar", "Motivo: " + erroDetalhado, "error");
                            $('#salvarNF').removeClass('spinner').removeAttr('disabled');
                            $('#preloader2').css('display', 'none');
                        });
                        });
                    }
                })
                .fail((err) => {
                    console.log(err);
                    swal("Erro", "A NF foi salva, mas ocorreu um erro ao gravar os Itens.", "error");
                    $('#salvarNF').removeClass('spinner').removeAttr('disabled');
                    $('#preloader2').css('display', 'none');
                });
            });
        }
    })
    .fail((err) => {
        console.log(err);
        swal("Erro", "Ocorreu um erro ao tentar salvar o cabeçalho da Nota Fiscal.", "error");
        $('#salvarNF').removeClass('spinner').removeAttr('disabled');
        $('#preloader2').css('display', 'none');
    });
});

function sucesso(){
    $('#preloader2').css('display', 'none');
    swal("Operação Concluída!", "NF-e, Itens e Contas a Pagar gravados com sucesso.\n(Baixas efetuadas para À Vista, Cartão ou Adiantamento)", "success");
    setTimeout(() => { location.href = path+'compras'; }, 3000);
}

        // INTERCEPTANDO O SALVAR DO compraFiscal.js
        const originalAjax = $.ajax;
        $.ajax = function() {
            if(arguments[0].url && arguments[0].url.includes('/salvarItem')) {
                let dataSent = arguments[0].data;
                let codigoReq = dataSent.produto.codigo;
                let finalidadeSelecionada = $('#tr_' + codigoReq).find('.finalidade_input').val();
                arguments[0].data.produto.finalidade = finalidadeSelecionada;
            }
            return originalAjax.apply(this, arguments);
        };
    });

</script>
@endsection
