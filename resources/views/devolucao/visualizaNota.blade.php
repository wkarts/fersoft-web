@extends('default.layout')

@section('css')
    <style type="text/css">
        body.loading .modal-loading {
            display: block;
        }
        .modal-loading {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: rgba(255, 255, 255, 0.8)
            url("/loading.gif") 50% 50% no-repeat;
        }
    </style>
@endsection

@section('content')

    <div class="row" id="anime" style="display: none">
        <div class="col s8 offset-s2">
            <lottie-player src="/anime/success.json" background="transparent" speed="0.8" style="width: 100%; height: 300px;"    autoplay >
            </lottie-player>
        </div>
    </div>

    <div class="@if(env('ANIMACAO')) animate__animated @endif animate__bounce" id="content" style="display: block">
        <div class=" d-flex flex-column flex-column-fluid" id="kt_content">
            <div class="card card-custom gutter-b example example-compact">
                <div class="col-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                    <div class="col-lg-12">
                        <!--begin::Portlet-->

                        <input type="hidden" name="id" value="{{{ isset($cliente) ? $cliente->id : 0 }}}">
                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Importando XML</h3>
                                <button type="button" class="btn btn-light-primary font-weight-bold btn-sm" data-toggle="modal" data-target="#modal-manual-devolucao">
                                    <i class="la la-book icon-sm"></i> Manual de Uso da Rotina
                                </button>
                            </div>
                        </div>
                        </div>
                        <input type="hidden" value="{{csrf_token()}}" id="_token">

                        <div class="row">

                            <div class="col-xl-12">
                                <div class="row">
                                    <div class="col-xl-12 col-sm-12 col-lg-12">
                                        @if(count($dadosAtualizados) > 0)
                                            <div class="row">
                                                <div class="col-xl-12">
                                                    @foreach($dadosAtualizados as $d)
                                                        <p class="text-danger">>>{{$d}}<<</p>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-xl-12">
                                    <div class="row">
                                        <div class="col-xl-12 col-sm-12 col-lg-12">
                                            <h4 class="center-align">Nota Fiscal: <strong class="text-primary">{{$dadosNf['nNf']}}</strong></h4>
                                            <h4 class="center-align">Chave: <strong class="text-primary">{{$dadosNf['chave']}}</strong></h4>
                                        </div>

                                        {{-- BLOCO CABEÇALHO DINÂMICO --}}
                                        <div class="col-xl-6 col-sm-6 col-lg-6" id="cabecalho-destino"></div>
                                        <div class="col-xl-6 col-sm-6 col-lg-6" id="cabecalho-endereco"></div>
                                        {{-- FIM BLOCO CABEÇALHO DINÂMICO --}}

                                    </div>
                                </div>

                                <input type="hidden" id="xmlEntrada" value="{{$pathXml}}">
                                <input type="hidden" id="idFornecedor" value="{{$idFornecedor}}">
                                <input type="hidden" id="idEmitente"   value="{{ $idEmitente }}">
                                <input type="hidden" id="nNf" value="{{$dadosNf['nNf']}}">
                                <input type="hidden" id="vFrete" value="{{$dadosNf['vFrete']}}">
                                <input type="hidden" id="chave" value="{{$dadosNf['chave']}}">
                                <input type="hidden" id="totalNF" value="{{$dadosNf['vProd']}}">
                                <input type="hidden" id="transportadora" value="{{json_encode($transportadora)}}">

                            </div>
                            <div class="col-xl-12 mt-3">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <h3>Itens da NFe</h3>
                                        <h5>Total de itens: <strong>{{ sizeof($itens) }}</strong></h5>
                                        <div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
                                            <table class="datatable-table" style="max-width: 100%;overflow: scroll" id="tbl">
                                                <thead class="datatable-head">
                                                <tr class="datatable-row" style="left: 0px;">
                                                    <th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Código</span></th>
                                                    <th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
                                                    <th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">NCM</span></th>
                                                    <th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">CBENEF</span></th>
                                                    <th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">CFOP</span></th>
                                                    <th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">CEST</span></th>
                                                    <th data-field="Status" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Cod Barra</span></th>
                                                    <th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Un. Compra</span></th>
                                                    <th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor</span></th>
                                                    <th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Qtd</span></th>
                                                    <th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Subtotal</span></th>
                                                    <th data-field="Actions" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ações</span></th>
                                                </tr>
                                                </thead>
                                                <input type="hidden" id="itens_nf" value="{{json_encode($itens)}}">
                                                <tbody id="tbody" class="datatable-body">
                                                </tbody>
                                            </table>
                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <h4 style="margin-left: 10px; margin-top: 30px; color: #000!important;">Soma dos Itens: <strong id="soma-itens" class="text-danger"></strong></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="row">
                                    <div class="col-xl-12 mt-3">
                                        <h2 style="margin-left: 10px;">Fatura</h2>
                                        <input type="hidden" id="fatura" value="{{json_encode($fatura)}}">
                                        <div class="row">
                                            @foreach($fatura as $f)
                                                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
                                                    <div class="card card-custom gutter-b example example-compact">
                                                        <div class="card-header">
                                                            <div class="card-title">
                                                                <h3 style="width: 230px; font-size: 20px; height: 10px;" class="card-title">R$ {{$f['valor_parcela']}}</h3>
                                                            </div>
                                                            <div class="card-toolbar">
                                                                <div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
                                                                    <a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="kt-widget__info">
                                                                <span class="kt-widget__label">Número:</span>
                                                                <a target="_blank" class="kt-widget__data text-success">{{$f['numero']}}</a>
                                                            </div>
                                                            <div class="kt-widget__info">
                                                                <span class="kt-widget__label">Vencimento:</span>
                                                                <a target="_blank" class="kt-widget__data text-success">{{$f['vencimento']}}</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12">
                                <div class="col-xl-12">
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Destinatário da Devolução</label>
                                                <div class="radio-list">
                                                    <label class="radio radio-primary">
                                                        <input type="radio" name="destinatario_devolucao" value="emitente" checked>
                                                        <span></span> Emitente Original
                                                    </label>
                                                    <label class="radio radio-primary ml-4">
                                                        <input type="radio" name="destinatario_devolucao" value="destinatario">
                                                        <span></span> Destinatário Original
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group validated col-lg-4 col-md-6 col-sm-4">
                                            <label class="col-form-label">Natureza de Operação</label>
                                            <select class="custom-select form-control" id="natureza" name="natureza">
                                                @foreach($naturezas as $n)
                                                    <option value="{{$n->id}}">{{$n->natureza}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Tipo</label>
                                            <select class="custom-select form-control" id="tipo" name="tipo">
                                                <option value="1">Saida</option>
                                                <option value="0">Entrada</option>
                                            </select>
                                        </div>
                                        <div class="form-group validated col-lg-4 col-md-4 col-sm-4">
                                            <label class="col-form-label">Transportadora</label>
                                            <div class="input-group">
                                                <select class="custom-select form-control" id="transportadora_id" name="transportadora_id">
                                                    <option value="0">--</option>
                                                    @foreach($transportadoras as $t)
                                                        <option
                                                            @if($idTransportadora > 0)
                                                                @if($idTransportadora == $t->id)
                                                                    selected
                                                            @endif
                                                            @endif
                                                            value="{{$t->id}}"
                                                        >{{$t->razao_social}}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" onclick="novaTransportadora()" class="btn btn-warning btn-sm">
                                                    <i class="la la-plus-circle icon-add"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="form-group validated col-12">
                                            <h2>Frete</h2>
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Valor do frete</label>
                                            <input class="form-control money" type="text" value="{{$dadosNf['vFrete']}}" id="valor_frete" name="valor_frete">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label" id="">Tipo</label>
                                            <select class="custom-select form-control" id="tipo_frete" name="tipo_frete">
                                                <option @if($tipoFrete == 0) selected @endif value="0">0 - Emitente</option>
                                                <option @if($tipoFrete == 1) selected @endif  value="1">1 - Destinatário</option>
                                                <option @if($tipoFrete == 2) selected @endif  value="2">2 - Terceiros</option>
                                                <option @if($tipoFrete == 9) selected @endif  value="9">9 - Sem Frete</option>
                                            </select>
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Placa</label>
                                            <input class="form-control" data-mask="AAA-AAAA" type="text" value="{{ $transportadora != null ? $transportadora['veiculo_placa'] : '' }}" id="placa" name="placa">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">UF</label>
                                            <select class="custom-select form-control" id="uf_placa">
                                                @foreach(App\Models\Cidade::estados() as $u)
                                                    <option @if($transportadora != null && $transportadora['veiculo_uf'] == $u) selected @endif value="{{$u}}">{{$u}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Quantidade</label>
                                            <input class="form-control" type="text" value="@if($transportadora != null) {{$transportadora['frete_quantidade']}} @endif" id="qtd" name="quantidade">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Espécie</label>
                                            <input class="form-control" type="text" value="@if($transportadora != null) {{$transportadora['frete_especie']}} @endif" id="especie" name="especie">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Peso bruto</label>
                                            <input class="form-control" type="text" value="@if($transportadora != null) {{$transportadora['frete_peso_bruto']}} @endif" id="peso_bruto" name="peso_bruto">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Peso liquído</label>
                                            <input class="form-control" type="text" value="@if($transportadora != null) {{$transportadora['frete_peso_liquido']}} @endif" id="peso_liquido" name="peso_liquido">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Outras despesas</label>
                                            <input class="form-control money" type="text" value="@if($transportadora != null) {{$transportadora['despesa_acessorias']}} @endif" id="valor_outros" name="valor_outros">
                                        </div>
                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-2">
                                            <label class="col-form-label">Desconto</label>
                                            <input class="form-control money" type="text" value="{{$dadosNf['vDesc']}}" id="vDesc" name="vDesc">
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row col-12">
                                    <div class="form-group validated col-lg-6 col-md-8 col-sm-12">
                                        <label class="col-form-label">Motivo</label>
                                        <textarea class="form-control" id="motivo" placeholder="Motivo" rows="3"></textarea>
                                    </div>
                                    <div class="form-group validated col-lg-6 col-md-8 col-sm-12">
                                        <label class="col-form-label">Observação</label>
                                        <textarea class="form-control" id="obs" placeholder="Observação" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="row">
                                    <div class="col-xl-12">
                                        <div class="col-xl-6">
                                            <h4 style="color: #000!important;">Valor Integral da nota: <strong id="valorDaNF" class="text-danger">R$ {{number_format((float)$dadosNf['vProd'], 2, ',', '.')}}</strong></h4>
                                        </div>
                                        <div class="col-xl-3">
                                        </div>
                                        <div class="col-xl-3">
                                            <button id="salvar-devolucao" style="width: 100%" type="button" class="btn btn-success mt-1">
                                                <i class="la la-check"></i>
                                                <span class="">Salvar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-loading loading-class"></div>

    <div class="modal fade" id="modal2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="data"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">
                    <input id="idEdit" type="hidden" value="">
                    <input id="randDelete" type="hidden" value="">

                    <div class="row">
                        <div class="form-group validated col-sm-12 col-lg-12 col-12">
                            <label class="col-form-label" id="">Nome do Item</label>
                            <input type="text" placeholder="Nome" id="nomeEdit" name="nomeEdit" class="form-control" value="">
                        </div>

                        <div class="form-group validated col-sm-4 col-lg-3 col-6">
                            <label class="col-form-label" id="">Quantidade</label>
                            <input type="text" placeholder="Valor" id="quantidadeEdit" name="quantidadeEdit" class="form-control qCom2" value="">
                        </div>

                        <div class="form-group validated col-sm-4 col-lg-3 col-6">
                            <label class="col-form-label" id="">Valor unitário</label>
                            <input type="text" placeholder="Valor" id="valorEdit" name="valorEdit" class="form-control valor_pizza" value="">
                        </div>

                        <div class="form-group validated col-sm-4 col-lg-3 col-6">
                            <label class="col-form-label" id="">Valor do frete</label>
                            <input type="text" placeholder="Valor do frete" id="valorFreteEdit" name="valorFreteEdit" class="form-control money" value="">
                        </div>

                        <div class="form-group validated col-sm-4 col-lg-3 col-6">
                            <label class="col-form-label" id="">% rededução BC</label>
                            <input type="text" placeholder="% rededução BC" id="pRedBC" name="pRedBC" class="form-control" value="">
                        </div>

                        <div class="form-group validated col-sm-4 col-lg-3 col-6">
                            <label class="col-form-label" id="">Cod. benefício</label>
                            <input type="text" placeholder="Cod. benefício" id="cBenef" name="cBenef" class="form-control" value="">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group validated col-lg-8 col-md-10 col-sm-8">
                            <label class="col-form-label">CST/CSOSN</label>

                            <select class="custom-select form-control" id="CST_CSOSN" name="CST_CSOSN">
                                @foreach(App\Models\Produto::listaCSTCSOSN() as $key => $c)
                                    <option value="{{$key}}">{{$key}} - {{$c}}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        <div class="form-group validated col-sm-6 col-lg-4 col-4">
                            <label class="col-form-label" id="">%ICMS</label>
                            <input type="text" placeholder="" id="icms" name="icms" class="form-control " value="">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group validated col-lg-8 col-md-10 col-sm-8">
                            <label class="col-form-label">CST/PIS</label>

                            <select class="custom-select form-control" id="CST_PIS" name="CST_CSOSN">
                                @foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
                                    <option value="{{$key}}">{{$key}} - {{$c}}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                        <div class="form-group validated col-sm-6 col-lg-4 col-4">
                            <label class="col-form-label" id="">%PIS</label>
                            <input type="text" placeholder="" id="pis" name="pis" class="form-control " value="">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group validated col-lg-8 col-md-10 col-sm-8">
                            <label class="col-form-label">CST/COFINS</label>

                            <select class="custom-select form-control" id="CST_COFINS" name="CST_COFINS">
                                @foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
                                    <option value="{{$key}}">{{$key}} - {{$c}}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                        <div class="form-group validated col-sm-6 col-lg-4 col-4">
                            <label class="col-form-label" id="">%COFINS</label>
                            <input type="text" placeholder="" id="cofins" name="cofins" class="form-control " value="">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group validated col-lg-8 col-md-10 col-sm-8">
                            <label class="col-form-label">CST/IPI</label>

                            <select class="custom-select form-control" id="CST_IPI" name="CST_IPI">
                                @foreach(App\Models\Produto::listaCST_IPI() as $key => $c)
                                    <option value="{{$key}}">{{$key}} - {{$c}}
                                    </option>
                                @endforeach
                            </select>

                        </div>
                        <div class="form-group validated col-sm-6 col-lg-4 col-4">
                            <label class="col-form-label" id="">%IPI</label>
                            <input type="text" placeholder="" id="ipi" name="ipi" class="form-control " value="">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
                    <button type="button" id="salvarEdit" class="btn btn-success font-weight-bold">OK</button>
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
                                <!-- <div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<label class="col-form-label">UF</label>

								<select class="custom-select form-control" id="sigla_uf3" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c)
                                    <option value="{{$c}}">{{$c}}
                                    </option>
@endforeach
                                </select>

                            </div> -->
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

    {{-- CABEÇALHO DINÂMICO - ATUALIZAÇÃO EM JS --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        window.dadosEmitente = @json($dadosEmitente);
        window.dadosDestinatario = @json($dadosDestinatario ?? []);
        window.cidade = @json(['nome' => $cidade->nome, 'uf' => $cidade->uf]);
        window.idFornecedor = @json($idFornecedor);
        window.idEmitente = @json($idEmitente);

        function renderCabecalho(dest) {
            var dados = dest === 'emitente' ? window.dadosEmitente : window.dadosDestinatario;
            var cidade = window.cidade.nome + ' (' + window.cidade.uf + ')';

            var html1 = '';
            html1 += '<h5>' + (dest === 'emitente' ? 'Fornecedor:' : 'Destinatário:') + ' <strong>' + (dados.razaoSocial || '') + '</strong></h5>';
            html1 += '<h5>Nome Fantasia: <strong>' + (dados.nomeFantasia || '') + '</strong></h5>';
            html1 += '<h5>CNPJ/CPF: <strong>' + (dados.cnpj || dados.cpf || '') + '</strong></h5>';
            html1 += '<h5>IE: <strong>' + (dados.ie || '') + '</strong></h5>';
            html1 += '<h5>Cidade: <strong>' + cidade + '</strong></h5>';
            document.getElementById('cabecalho-destino').innerHTML = html1;

            var html2 = '';
            html2 += '<h5>Logradouro: <strong>' + (dados.logradouro || '') + '</strong></h5>';
            html2 += '<h5>Numero: <strong>' + (dados.numero || '') + '</strong></h5>';
            html2 += '<h5>Bairro: <strong>' + (dados.bairro || '') + '</strong></h5>';
            html2 += '<h5>CEP: <strong>' + (dados.cep || '') + '</strong></h5>';
            html2 += '<h5>Fone: <strong>' + (dados.fone || '') + '</strong></h5>';
            document.getElementById('cabecalho-endereco').innerHTML = html2;

            document.getElementById('idFornecedor').value = (dest === 'emitente') ? window.idEmitente : window.idFornecedor;
        }

        document.addEventListener('DOMContentLoaded', function() {
            var radios = document.getElementsByName('destinatario_devolucao');
            var checked = Array.from(radios).find(r => r.checked) || { value: 'emitente' };
            renderCabecalho(checked.value);
            radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    renderCabecalho(this.value);
                });
            });

            document.getElementById('salvar-devolucao').addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.add('loading');

                var destino = document.querySelector('input[name="destinatario_devolucao"]:checked').value;
                var cabecalho = (destino === 'emitente' ? window.dadosEmitente : window.dadosDestinatario);

                // Garante que todo item tenha o campo 'parcial'
                let itens = JSON.parse(document.getElementById('itens_nf').value || '[]');
                itens = itens.map(item => {
                    if (typeof item.parcial === 'undefined') {
                        item.parcial = 0;
                    }
                    return item;
                });

                var fornecedor_id = document.getElementById('idFornecedor').value;
                var emitente_id = document.getElementById('idEmitente').value;

                var data = {
                    natureza: document.getElementById('natureza').value,
                    tipo: document.getElementById('tipo').value,
                    valor_integral: document.getElementById('totalNF').value,
                    vFrete: document.getElementById('vFrete').value,
                    vDesc: document.getElementById('vDesc').value,
                    nNf: document.getElementById('nNf').value,
                    xmlEntrada: document.getElementById('xmlEntrada').value,
                    idEmitente: emitente_id,
                    fornecedorId: fornecedor_id,
                    fornecedor_id: fornecedor_id,
                    transportadora_id: document.getElementById('transportadora_id').value,
                    tipoFrete: document.getElementById('tipo_frete').value,
                    placa: document.getElementById('placa').value,
                    ufPlaca: document.getElementById('uf_placa').value,
                    qtd: document.getElementById('qtd').value,
                    especie: document.getElementById('especie').value,
                    pBruto: document.getElementById('peso_bruto').value,
                    pLiquido: document.getElementById('peso_liquido').value,
                    vOutros: document.getElementById('valor_outros').value,
                    obs: document.getElementById('obs').value,
                    motivo: document.getElementById('motivo').value,
                    devolucao_parcial: document.getElementById('devolucao_parcial') ? document.getElementById('devolucao_parcial').checked : false,
                    valor_devolvido: document.getElementById('valor_devolvido') ? document.getElementById('valor_devolvido').value : '',
                    itens: itens,
                    transportadora: JSON.parse(document.getElementById('transportadora').value || '{}'),
                    // Cabeçalho dinâmico
                    cabecalho_nome: (cabecalho.razaoSocial || ''),
                    cabecalho_nome_fantasia: (cabecalho.nomeFantasia || ''),
                    cabecalho_cnpj_cpf: (cabecalho.cnpj || cabecalho.cpf || ''),
                    cabecalho_ie: (cabecalho.ie || ''),
                    cabecalho_logradouro: (cabecalho.logradouro || ''),
                    cabecalho_numero: (cabecalho.numero || ''),
                    cabecalho_bairro: (cabecalho.bairro || ''),
                    cabecalho_cep: (cabecalho.cep || ''),
                    cabecalho_fone: (cabecalho.fone || '')
                };

                axios.post('/devolucao/salvar', {
                    destinatario_devolucao: destino,
                    dadosEmitente: window.dadosEmitente,
                    dadosDestinatario: window.dadosDestinatario,
                    idEmitente: emitente_id,
                    idFornecedor: fornecedor_id,
                    data: data,
                }, {
                    headers: { 'X-CSRF-TOKEN': document.getElementById('_token').value }
                })
                    .then(resp => {
                        document.body.classList.remove('loading');
                        //alert('Devolução salva com sucesso!');
                        //window.location.reload();
                    })
                    .catch(err => {
                        document.body.classList.remove('loading');
                        let msg = 'Erro ao salvar devolução!';
                        if(err.response && err.response.data) {
                            msg += '\n' + (typeof err.response.data === 'string' ? err.response.data : JSON.stringify(err.response.data));
                        }
                        //alert(msg);
                    });
            });
        });
    </script>


@endsection
