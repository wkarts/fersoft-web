@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="/locacao/salvar">
                        @csrf
                        <input type="hidden" name="id" value="{{{ isset($locacao) ? $locacao->id : 0 }}}">

                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">
                                <h3 class="card-title">
                                    @isset($locacao) Editar @else Nova @endif Operação de Locação / Coleta
                                </h3>
                            </div>

                            <div class="card-body">
                                <!-- FINALIDADE DA OPERAÇÃO -->
                                <div class="row bg-light p-4 rounded mb-4">
                                    <div class="col-12">
                                        <label class="font-weight-bold d-block mb-2">Finalidade do Movimento:</label>
                                        <div class="radio-inline">
                                            <label class="radio radio-success mr-4">
                                                <input type="radio" name="finalidade" value="locacao_cliente" id="fin_cliente" @if(!isset($locacao) || $locacao->finalidade == 'locacao_cliente') checked @endif onclick="alternarFinalidade()"/>
                                                <span></span> 🔵 Locação Comercial (Cliente) - Gera Faturamento
                                            </label>
                                            <label class="radio radio-primary">
                                                <input type="radio" name="finalidade" value="coleta_fornecedor" id="fin_fornecedor" @if(isset($locacao) && $locacao->finalidade == 'coleta_fornecedor') checked @endif onclick="alternarFinalidade()"/>
                                                <span></span> 🟢 Caçamba em Fornecedor (Compra de Sucata) - Sem Faturamento
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- SELEÇÃO DE CLIENTE / FORNECEDOR / MATERIAL PREVISTO -->
                                <div class="row">
                                    <div class="form-group validated col-lg-3 col-md-4 col-sm-6">
                                        <label class="col-form-label font-weight-bold">Tipo de Item</label>
                                        <select class="form-control custom-select" name="tipo" id="tipo_locacao">
                                            <option value="cacamba" @if((isset($locacao) && $locacao->tipo == 'cacamba') || old('tipo') == 'cacamba') selected @endif>Caçamba / Container</option>
                                            <option value="equipamento" @if((isset($locacao) && $locacao->tipo == 'equipamento') || old('tipo') == 'equipamento') selected @endif>Equipamento / Máquina</option>
                                        </select>
                                    </div>

                                    <!-- CAMPO CLIENTE -->
                                    <div class="form-group validated col-lg-9 col-md-8 col-sm-6" id="box_cliente">
                                        <label class="col-form-label font-weight-bold">Cliente</label>
                                        <select class="form-control select2" style="width: 100%" id="kt_select2_cliente" name="cliente_id">
                                            <option value="">Selecione o cliente</option>
                                            @foreach($clientes as $c)
                                                <option @if(isset($locacao) && $locacao->cliente_id == $c->id) selected @endif value="{{$c->id}}">{{$c->razao_social}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- CAMPO FORNECEDOR -->
                                    <div class="form-group validated col-lg-5 col-md-5 col-sm-6 d-none" id="box_fornecedor">
                                        <label class="col-form-label font-weight-bold">Fornecedor (Sucateiro/Parceiro)</label>
                                        <select class="form-control select2" style="width: 100%" id="kt_select2_fornecedor" name="fornecedor_id">
                                            <option value="">Selecione o fornecedor</option>
                                            @foreach($fornecedores as $f)
                                                <option @if(isset($locacao) && $locacao->fornecedor_id == $f->id) selected @endif value="{{$f->id}}">{{$f->razao_social}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- CAMPO MATERIAL PREVISTO (FORNECEDOR) -->
                                    <div class="form-group validated col-lg-4 col-md-3 col-sm-6 d-none" id="box_material_previsto">
                                        <label class="col-form-label font-weight-bold">Material Previsto na Caçamba</label>
                                        <input type="text" class="form-control" name="material_previsto" placeholder="Ex: Alumínio, Perfil, Cobre, Misto..." value="{{{ isset($locacao) ? $locacao->material_previsto : old('material_previsto') }}}">
                                    </div>
                                </div>

                                <!-- DATAS E REGRAS DE CÁLCULO FINANCEIRO -->
                                <div class="row">
                                    <div class="form-group validated col-sm-6 col-lg-3">
                                        <label class="col-form-label font-weight-bold">Data Início / Entrega</label>
                                        <input type="text" data-mask="00/00/0000" id="kt_datepicker_1" class="form-control" name="inicio" value="{{{ isset($locacao) ? \Carbon\Carbon::parse($locacao->inicio)->format('d/m/Y') : (old('inicio') ?? date('d/m/Y')) }}}">
                                    </div>

                                    <div class="form-group validated col-sm-6 col-lg-3">
                                        <label class="col-form-label font-weight-bold">Data Fim Prevista</label>
                                        <input type="text" data-mask="00/00/0000" id="kt_datepicker_2" class="form-control" name="fim" value="{{{ (isset($locacao) && $locacao->fim != '1969-12-31') ? \Carbon\Carbon::parse($locacao->fim)->format('d/m/Y') : old('fim') }}}">
                                    </div>

                                    <div class="form-group validated col-sm-6 col-lg-3 box_financeiro_campo">
                                        <label class="col-form-label font-weight-bold">Tipo de Cálculo</label>
                                        <select class="form-control custom-select" name="tipo_calculo" id="tipo_calculo">
                                            <option value="fechado" @if(isset($locacao) && $locacao->tipo_calculo == 'fechado') selected @endif>Valor Fechado / Total</option>
                                            <option value="dia" @if(isset($locacao) && $locacao->tipo_calculo == 'dia') selected @endif>Por Dia (Diária)</option>
                                            <option value="mes" @if(isset($locacao) && $locacao->tipo_calculo == 'mes') selected @endif>Por Mês (Mensalidade)</option>
                                        </select>
                                    </div>

                                    <div class="form-group validated col-sm-6 col-lg-3 box_financeiro_campo">
                                        <label class="col-form-label font-weight-bold">Valor do Frete (R$)</label>
                                        <input type="text" class="form-control money" name="valor_frete" value="{{{ isset($locacao) ? number_format($locacao->valor_frete, 2, ',', '.') : '0,00' }}}">
                                    </div>
                                </div>

                                <!-- BLOCO CONDICOES DE FATURAMENTO -->
                                <div id="box_faturamento" class="bg-light-success p-4 rounded mb-4">
                                    <h5 class="text-success font-weight-bold mb-3"><i class="la la-money"></i> Condições de Faturamento (Contas a Receber)</h5>
                                    <div class="row">
                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">Gerar Faturamento?</label>
                                            <select class="form-control custom-select" name="faturado">
                                                <option value="1" @if(!isset($locacao) || $locacao->faturado == 1) selected @endif>SIM - Gerar Contas a Receber</option>
                                                <option value="0" @if(isset($locacao) && $locacao->faturado == 0) selected @endif>NÃO - Isento / Cortesia</option>
                                            </select>
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">1º Vencimento</label>
                                            <input type="text" data-mask="00/00/0000" class="form-control date-out" name="primeiro_vencimento" value="{{{ isset($locacao) && $locacao->primeiro_vencimento ? \Carbon\Carbon::parse($locacao->primeiro_vencimento)->format('d/m/Y') : date('d/m/Y') }}}">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">Nº de Parcelas</label>
                                            <input type="number" class="form-control" name="quantidade_parcelas" value="{{{ isset($locacao) ? $locacao->quantidade_parcelas : 1 }}}" min="1">
                                        </div>

                                        <div class="form-group col-lg-3 col-md-6">
                                            <label class="font-weight-bold">Forma de Pagamento</label>
                                            <select class="form-control custom-select" name="forma_pagamento">
                                                <option value="boleto">Boleto Bancário</option>
                                                <option value="pix">PIX / Transferência</option>
                                                <option value="dinheiro">Dinheiro / À Vista</option>
                                                <option value="cartao">Cartão de Crédito</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- ENDEREÇO DA OBRA EM LINHA EXCLUSIVA -->
                                <div class="row my-4">
                                    <div class="col-12"><hr></div>
                                    <div class="col-12">
                                        <h5 class="text-primary font-weight-bold"><i class="la la-map-marker text-primary"></i> Endereço da Obra / Local de Entrega ou Coleta</h5>
                                    </div>
                                </div>

                                <!-- CAMPOS DE ENDEREÇO E CEP CORRIGIDOS -->
                                <div class="row">
                                    <div class="form-group col-lg-3 col-md-4 col-sm-6">
                                        <label class="font-weight-bold">CEP</label>
                                        <div class="input-group">
                                            <input type="text" data-mask="00000-000" id="cep_entrega" class="form-control" name="cep_entrega" value="{{{ isset($locacao) ? $locacao->cep_entrega : old('cep_entrega') }}}">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary" onclick="buscarCepEntrega()"><i class="la la-search"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group col-lg-5 col-md-8 col-sm-12">
                                        <label class="font-weight-bold">Rua / Logradouro</label>
                                        <input type="text" id="rua_entrega" class="form-control" name="rua_entrega" value="{{{ isset($locacao) ? $locacao->rua_entrega : old('rua_entrega') }}}">
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 col-sm-6">
                                        <label class="font-weight-bold">Número</label>
                                        <input type="text" id="numero_entrega" class="form-control" name="numero_entrega" value="{{{ isset($locacao) ? $locacao->numero_entrega : old('numero_entrega') }}}">
                                    </div>

                                    <div class="form-group col-lg-2 col-md-4 col-sm-6">
                                        <label class="font-weight-bold">Bairro</label>
                                        <input type="text" id="bairro_entrega" class="form-control" name="bairro_entrega" value="{{{ isset($locacao) ? $locacao->bairro_entrega : old('bairro_entrega') }}}">
                                    </div>

                                    <div class="form-group col-lg-4 col-md-6 col-sm-6">
                                        <label class="font-weight-bold">Cidade de Entrega</label>
                                        <select class="form-control select2" style="width: 100%" id="kt_select2_cidade_entrega" name="cidade_id_entrega">
                                            <option value="">Selecione a cidade</option>
                                            @foreach(App\Models\Cidade::all() as $c)
                                                <option @if(isset($locacao) && $locacao->cidade_id_entrega == $c->id) selected @endif value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group col-lg-8 col-md-12 col-sm-12">
                                        <label class="font-weight-bold">Ponto de Referência / Instruções</label>
                                        <input type="text" class="form-control" name="referencia_entrega" value="{{{ isset($locacao) ? $locacao->referencia_entrega : old('referencia_entrega') }}}">
                                    </div>

                                    <div class="form-group col-12">
                                        <label class="font-weight-bold">Observação Geral</label>
                                        <input type="text" id="observacao" class="form-control" name="observacao" value="{{{ isset($locacao) ? $locacao->observacao : old('observacao') }}}">
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                        <a class="btn btn-danger btn-block font-weight-bold" href="/locacao"><i class="la la-close"></i> Cancelar</a>
                                    </div>
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <button type="submit" class="btn btn-success btn-block font-weight-bold"><i class="la la-check"></i> Salvar e Continuar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @section('javascript')
        <script type="text/javascript">
            $(document).ready(function() {
                $("#kt_select2_cliente").select2();
                $("#kt_select2_fornecedor").select2();
                $("#kt_select2_cidade_entrega").select2();
                alternarFinalidade();

                // PUXAR ENDEREÇO DO CLIENTE
                $('#kt_select2_cliente').change(function() {
                    let clienteId = $(this).val();
                    if(clienteId > 0 && $('#fin_cliente').is(':checked')) {
                        $.get(path + 'clientes/find/' + clienteId).done((res) => {
                            if(res) {
                                $('#rua_entrega').val(res.rua || '');
                                $('#numero_entrega').val(res.numero || '');
                                $('#bairro_entrega').val(res.bairro || '');
                                $('#cep_entrega').val(res.cep || '');
                                if(res.cidade_id) $('#kt_select2_cidade_entrega').val(res.cidade_id).trigger('change');
                            }
                        });
                    }
                });

                // PUXAR ENDEREÇO DO FORNECEDOR
                $('#kt_select2_fornecedor').change(function() {
                    let fornecedorId = $(this).val();
                    if(fornecedorId > 0 && $('#fin_fornecedor').is(':checked')) {
                        $.get(path + 'fornecedores/find/' + fornecedorId).done((res) => {
                            if(res) {
                                $('#rua_entrega').val(res.rua || '');
                                $('#numero_entrega').val(res.numero || '');
                                $('#bairro_entrega').val(res.bairro || '');
                                $('#cep_entrega').val(res.cep || '');
                                if(res.cidade_id) $('#kt_select2_cidade_entrega').val(res.cidade_id).trigger('change');
                            }
                        });
                    }
                });
            });

            function alternarFinalidade() {
                if($('#fin_cliente').is(':checked')) {
                    $('#box_cliente').removeClass('d-none');
                    $('#box_fornecedor').addClass('d-none');
                    $('#box_material_previsto').addClass('d-none');
                    $('#box_faturamento').removeClass('d-none');
                    $('.box_financeiro_campo').removeClass('d-none');
                } else {
                    $('#box_cliente').addClass('d-none');
                    $('#box_fornecedor').removeClass('d-none');
                    $('#box_material_previsto').removeClass('d-none');
                    $('#box_faturamento').addClass('d-none');
                    $('.box_financeiro_campo').addClass('d-none');
                }
            }

            function buscarCepEntrega() {
                let cep = $('#cep_entrega').val().replace(/\D/g, '');
                if(cep.length === 8) {
                    $.get('https://viacep.com.br/ws/' + cep + '/json/').done((res) => {
                        if(!res.erro) {
                            $('#rua_entrega').val(res.logradouro);
                            $('#bairro_entrega').val(res.bairro);
                            if(res.ibge) {
                                $.get(path + 'cidades/findIbge/' + res.ibge).done((cid) => {
                                    if(cid) $('#kt_select2_cidade_entrega').val(cid.id).trigger('change');
                                });
                            }
                        }
                    });
                }
            }
        </script>
    @endsection
@endsection
