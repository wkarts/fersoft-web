@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
@endpush
@section('content')

    <style type="text/css">
        .section-title {
            display: flex;
            align-items: center;
            font-weight: 700;
            color: #3F4254;
            margin-bottom: 1.5rem;
        }
        .section-title i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .switch-box {
            background-color: #F3F6F9;
            border: 1px solid #E4E6EF;
            border-radius: 0.85rem;
            padding: 1.25rem;
            height: 100%;
        }
    </style>

    <div class="document-fiscal-page document-fiscal-page--naturezaOperacao d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="{{{ isset($natureza) ? '/naturezaOperacao/update': '/naturezaOperacao/save' }}}">
                        <input type="hidden" name="id" value="{{{ isset($natureza->id) ? $natureza->id : 0 }}}">

                        <div class="card card-custom shadow-sm mb-8">
                            <div class="card-header py-3">
                                <div class="card-title">
								<span class="card-icon">
									<i class="flaticon2-paper text-primary"></i>
								</span>
                                    <h3 class="card-label">
                                        {{{ isset($natureza) ? "Editar": "Cadastrar" }}} Natureza de Operação
                                    </h3>
                                </div>
                            </div>
                        </div>

                        @csrf

                        <div class="card card-custom shadow-sm mb-8">
                            <div class="card-body">
                                <h4 class="section-title"><i class="flaticon2-pen text-primary"></i> Dados Principais</h4>
                                <div class="row mb-4">
                                    <div class="form-group validated col-sm-12 col-lg-8">
                                        <label class="font-weight-bold">Descrição da Natureza de Operação <span class="text-danger">*</span></label>
                                        <input id="natureza" type="text" class="form-control @if($errors->has('natureza')) is-invalid @endif" name="natureza" value="{{{ isset($natureza) ? $natureza->natureza : old('natureza') }}}" placeholder="Ex: VENDA DE MERCADORIAS">
                                        @if($errors->has('natureza'))
                                            <div class="invalid-feedback">{{ $errors->first('natureza') }}</div>
                                        @endif
                                    </div>
                                    <div class="form-group validated col-sm-12 col-lg-4">
                                        <label class="font-weight-bold">Finalidade <span class="text-danger">*</span></label>
                                        <select name="finNFe" class="custom-select form-control">
                                            @foreach(App\Models\NaturezaOperacao::finalidades() as $key => $f)
                                                <option value="{{$key}}" @isset($natureza) @if($natureza->finNFe == $key) selected @endif @endisset>{{$f}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-8"></div>

                                <h4 class="section-title"><i class="flaticon2-list-2 text-primary"></i> Códigos Fiscais (CFOP)</h4>
                                <div class="row">
                                    <div class="col-lg-6 col-12 mb-4">
                                        <div class="bg-light-primary rounded p-5">
                                            <h5 class="text-primary mb-4">CFOP Operações Estaduais (Dentro do Estado)</h5>
                                            <div class="row">
                                                <div class="form-group validated col-6 mb-0">
                                                    <label class="font-weight-bold">Saída <span class="text-danger">*</span></label>
                                                    <input id="CFOP_saida_estadual" type="text" class="form-control @if($errors->has('CFOP_saida_estadual')) is-invalid @endif" name="CFOP_saida_estadual" value="{{{ isset($natureza) ? $natureza->CFOP_saida_estadual : old('CFOP_saida_estadual') }}}" placeholder="Ex: 5102">
                                                    @if($errors->has('CFOP_saida_estadual')) <div class="invalid-feedback">Campo Obrigatório</div> @endif
                                                </div>
                                                <div class="form-group validated col-6 mb-0">
                                                    <label class="font-weight-bold">Entrada <span class="text-danger">*</span></label>
                                                    <input id="CFOP_entrada_estadual" type="text" class="form-control @if($errors->has('CFOP_entrada_estadual')) is-invalid @endif" name="CFOP_entrada_estadual" value="{{{ isset($natureza) ? $natureza->CFOP_entrada_estadual : old('CFOP_entrada_estadual') }}}" placeholder="Ex: 1102">
                                                    @if($errors->has('CFOP_entrada_estadual')) <div class="invalid-feedback">Campo Obrigatório</div> @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-12 mb-4">
                                        <div class="bg-light-success rounded p-5">
                                            <h5 class="text-success mb-4">CFOP Operações Interestaduais (Fora do Estado)</h5>
                                            <div class="row">
                                                <div class="form-group validated col-6 mb-0">
                                                    <label class="font-weight-bold">Saída <span class="text-danger">*</span></label>
                                                    <input id="CFOP_saida_inter_estadual" type="text" class="form-control @if($errors->has('CFOP_saida_inter_estadual')) is-invalid @endif" name="CFOP_saida_inter_estadual" value="{{{ isset($natureza) ? $natureza->CFOP_saida_inter_estadual : old('CFOP_saida_inter_estadual') }}}" placeholder="Ex: 6102">
                                                    @if($errors->has('CFOP_saida_inter_estadual')) <div class="invalid-feedback">Campo Obrigatório</div> @endif
                                                </div>
                                                <div class="form-group validated col-6 mb-0">
                                                    <label class="font-weight-bold">Entrada <span class="text-danger">*</span></label>
                                                    <input id="CFOP_entrada_inter_estadual" type="text" class="form-control @if($errors->has('CFOP_entrada_inter_estadual')) is-invalid @endif" name="CFOP_entrada_inter_estadual" value="{{{ isset($natureza) ? $natureza->CFOP_entrada_inter_estadual : old('CFOP_entrada_inter_estadual') }}}" placeholder="Ex: 2102">
                                                    @if($errors->has('CFOP_entrada_inter_estadual')) <div class="invalid-feedback">Campo Obrigatório</div> @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-8"></div>

                                <h4 class="section-title"><i class="flaticon2-gear text-primary"></i> Regras de Comportamento e ERP</h4>

                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-4">
                                        <div class="switch-box">
                                            <label class="font-weight-bold d-block mb-3">Sobrescrever CFOP do produto</label>
                                            <span class="switch switch-outline switch-icon switch-primary">
											<label>
												<input type="checkbox" name="sobrescreve_cfop" value="true" @if(isset($natureza) && $natureza->sobrescreve_cfop) checked @endif>
												<span></span>
											</label>
										</span>
                                            <small class="text-muted d-block mt-2">Usa o CFOP desta tela em vez do CFOP do produto.</small>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-4">
                                        <div class="switch-box">
                                            <label class="font-weight-bold d-block mb-3">Não Movimentar Estoque</label>
                                            <span class="switch switch-outline switch-icon switch-danger">
											<label>
												<input type="checkbox" name="nao_movimenta_estoque" value="true" @if(isset($natureza) && $natureza->nao_movimenta_estoque) checked @endif>
												<span></span>
											</label>
										</span>
                                            <small class="text-muted d-block mt-2">Vendas com esta natureza não afetam saldo.</small>
                                        </div>
                                    </div>

                                    <!-- NOVOS CAMPOS AQUI (INFORMAÇÃO CLIENTE E DIFERIMENTO) -->
                                    <div class="col-lg-3 col-md-6 mb-4">
                                        <div class="switch-box">
                                            <label class="font-weight-bold d-block mb-3">Puxar Observação do Cliente</label>
                                            <span class="switch switch-outline switch-icon switch-success">
											<label>
												<input type="checkbox" name="usar_info_cliente" value="true" @if(isset($natureza) && $natureza->usar_info_cliente) checked @endif>
												<span></span>
											</label>
										</span>
                                            <small class="text-muted d-block mt-2">Garante que a "Info NF-e" do cliente apareça.</small>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-4">
                                        <div class="switch-box border-warning">
                                            <label class="font-weight-bold d-block mb-3 text-warning">Aplicar Frase de Diferimento</label>
                                            <span class="switch switch-outline switch-icon switch-warning">
											<label>
												<input type="checkbox" name="usar_diferimento" value="true" @if(isset($natureza) && $natureza->usar_diferimento) checked @endif>
												<span></span>
											</label>
										</span>
                                            <small class="text-muted d-block mt-2">Adiciona a regra e o nº de Diferimento na nota.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="form-group validated col-sm-12 col-lg-6">
                                        <label class="font-weight-bold">Categoria de Conta (Financeiro)</label>
                                        <button type="button" class="btn btn-light-info btn-sm btn-icon ml-2" data-toggle="tooltip" title="Ao salvar a venda, esta categoria é incluída no Contas a Receber."><i class="la la-info"></i></button>
                                        <select name="categoria_conta_id" class="custom-select form-control">
                                            <option value="">-- Não Gerar Título Específico --</option>
                                            @foreach($categoriasDeConta as $c)
                                                <option value="{{$c->id}}" @if(isset($natureza) && $natureza->categoria_conta_id == $c->id) selected @endif>{{$c->nome}}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group validated col-lg-6 col-sm-12">
                                        <label class="font-weight-bold">Sobrescrever CST/CSOSN do Produto</label>
                                        <button type="button" class="btn btn-light-info btn-sm btn-icon ml-2" data-toggle="tooltip" title="Se preenchido, irá ignorar o CST do produto e aplicar este."><i class="la la-info"></i></button>
                                        <select class="custom-select form-control" name="CST_CSOSN">
                                            <option value="">-- Usar a do Cadastro do Produto --</option>
                                            @foreach(App\Models\ConfigNota::listaCST() as $key => $l)
                                                <option value="{{$key}}" @if(isset($natureza) && $key == $natureza->CST_CSOSN) selected @elseif(old('CST_CSOSN') == $key) selected @endif>{{$key}} - {{$l}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="separator separator-dashed my-8"></div>

                                <h4 class="section-title"><i class="flaticon2-percentage text-primary"></i> Tributação IBS/CBS (Sobrescrita)</h4>
                                <div class="row bg-light rounded p-5">
                                    <div class="form-group validated col-lg-3 col-md-6 col-sm-12">
                                        <label class="font-weight-bold">CST IBS/CBS</label>
                                        <select class="custom-select form-control" name="cst_ibs_cbs">
                                            <option value="">-- Do Produto --</option>
                                            @foreach($listaCstIbsCbs as $cst)
                                                <option value="{{ $cst->cst_ibs_cbs }}" @if((isset($natureza) && $natureza->cst_ibs_cbs == $cst->cst_ibs_cbs) || old('cst_ibs_cbs') == $cst->cst_ibs_cbs) selected @endif>
                                                    {{ $cst->cst_ibs_cbs }} - {{ $cst->descricao_cst_ibs_cbs }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group validated col-lg-3 col-md-6 col-sm-12">
                                        <label class="font-weight-bold">Class. Trib. IBS/CBS</label>
                                        <select class="custom-select form-control" name="class_trib_ibs_cbs">
                                            <option value="">-- Do Produto --</option>
                                            @foreach($listaClassTrib as $class)
                                                <option value="{{ $class->cclasstrib }}" @if((isset($natureza) && $natureza->class_trib_ibs_cbs == $class->cclasstrib) || old('class_trib_ibs_cbs') == $class->cclasstrib) selected @endif>
                                                    {{ $class->cclasstrib }} - {{ $class->nome_cclasstrib }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group validated col-lg-3 col-md-6 col-sm-12">
                                        <label class="font-weight-bold">Redução IBS (%)</label>
                                        <input type="text" class="form-control" name="perc_red_ibs" value="{{ isset($natureza) && $natureza->perc_red_ibs > 0 ? number_format($natureza->perc_red_ibs, 2, ',', '.') : old('perc_red_ibs', '0,00') }}">
                                    </div>

                                    <div class="form-group validated col-lg-3 col-md-6 col-sm-12">
                                        <label class="font-weight-bold">Redução CBS (%)</label>
                                        <input type="text" class="form-control" name="perc_red_cbs" value="{{ isset($natureza) && $natureza->perc_red_cbs > 0 ? number_format($natureza->perc_red_cbs, 2, ',', '.') : old('perc_red_cbs', '0,00') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer px-0 mt-4">
                            <div class="row">
                                <div class="col-lg-6"></div>
                                <div class="col-lg-3 col-sm-6 mb-2">
                                    <a class="btn btn-light-danger btn-block font-weight-bolder" href="/naturezaOperacao">
                                        <i class="la la-close"></i> Cancelar
                                    </a>
                                </div>
                                <div class="col-lg-3 col-sm-6">
                                    <button type="submit" class="btn btn-success btn-block font-weight-bolder">
                                        <i class="la la-check"></i> Salvar Cadastro
                                    </button>
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
                $('[data-toggle="tooltip"]').tooltip();
                $('[data-toggle="popover"]').popover();

                // Auto Preenchimento CFOP
                $('#CFOP_saida_estadual').blur(() => {
                    let cfop = $('#CFOP_saida_estadual').val()
                    let temp = cfop.substring(1,4)
                    if(cfop.length == 4){
                        $('#CFOP_saida_inter_estadual').val('6'+temp)
                        $('#CFOP_entrada_inter_estadual').val('2'+temp)
                        $('#CFOP_entrada_estadual').val('1'+temp)
                    }
                });

                // Trava de segurança para CST x Classificação (IBS/CBS)
                $('select[name="cst_ibs_cbs"]').change(function() {
                    let cstSelecionado = $(this).val();
                    let selectClasse = $('select[name="class_trib_ibs_cbs"]');

                    if(!cstSelecionado) {
                        selectClasse.find('option').show();
                        return;
                    }
                    selectClasse.find('option:not(:first)').hide();
                    selectClasse.find('option').each(function() {
                        let valorClasse = $(this).val();
                        if (valorClasse && valorClasse.substring(0, 3) === cstSelecionado) {
                            $(this).show();
                        }
                    });
                    selectClasse.val('');
                });
            });
        </script>
    @endsection
@endsection
