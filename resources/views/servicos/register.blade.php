@extends('default.layout')
@section('content')
    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">

            <form method="post" action="{{ isset($servico) ? '/servicos/update': '/servicos/save' }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" value="{{ isset($servico) ? $servico->id : 0 }}">

                <!-- CARD PRINCIPAL -->
                <div class="card card-custom gutter-b shadow-sm border-0">
                    <!-- Cabeçalho Principal -->
                    <div class="card-header border-0 py-5 bg-light-primary rounded-top">
                        <div class="card-title">
                            <h3 class="card-label font-weight-bolder text-primary">
                                <i class="la la-concierge-bell text-primary mr-2" style="font-size: 1.8rem;"></i>
                                {{ isset($servico) ? 'Editar Serviço #' . $servico->id : 'Novo Cadastro de Serviço' }}
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <a href="/servicos" class="btn btn-sm btn-light-primary font-weight-bold">
                                <i class="la la-arrow-left"></i> Voltar para Lista
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-8">

                        <!-- SEÇÃO 1: INFORMAÇÕES BÁSICAS -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-dark font-weight-bold mb-4">
                                    <i class="la la-info-circle text-info mr-1"></i> Informações do Serviço
                                </h5>
                            </div>

                            <!-- Nome -->
                            <div class="form-group validated col-sm-12 col-lg-6">
                                <label class="font-weight-bold">Nome do Serviço <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-solid @if($errors->has('nome')) is-invalid @endif"
                                       name="nome" placeholder="Ex: Manutenção Preventiva de Equipamentos"
                                       value="{{ isset($servico) ? $servico->nome : old('nome') }}" required>
                                @if($errors->has('nome'))
                                    <div class="invalid-feedback">{{ $errors->first('nome') }}</div>
                                @endif
                            </div>

                            <!-- Categoria -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Categoria <span class="text-danger">*</span></label>
                                <select class="custom-select form-control form-control-solid" name="categoria_id">
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}" @isset($servico) @if($cat->id == $servico->categoria_id) selected @endif @endisset>
                                            {{ $cat->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Unidade de Cobrança -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Unidade de Cobrança</label>
                                <select class="custom-select form-control form-control-solid" name="unidade_cobranca">
                                    <option @isset($servico) @if($servico->unidade_cobranca == 'UN') selected @endif @endisset value="UN">UN (Unidade)</option>
                                    <option @isset($servico) @if($servico->unidade_cobranca == 'HR') selected @endif @endisset value="HR">HR (Hora)</option>
                                    <option @isset($servico) @if($servico->unidade_cobranca == 'MIN') selected @endif @endisset value="MIN">MIN (Minuto)</option>
                                </select>
                            </div>

                            <!-- Valor Principal -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Valor (R$) <span class="text-danger">*</span></label>
                                <input type="text" id="valor" class="form-control form-control-solid money @if($errors->has('valor')) is-invalid @endif"
                                       name="valor" placeholder="0,00"
                                       value="{{ isset($servico) ? moeda($servico->valor) : old('valor') }}" required>
                                @if($errors->has('valor'))
                                    <div class="invalid-feedback">{{ $errors->first('valor') }}</div>
                                @endif
                            </div>

                            <!-- Tempo de Serviço -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Tempo (Minutos)</label>
                                <input type="text" id="tempo_servico" class="form-control form-control-solid @if($errors->has('tempo_servico')) is-invalid @endif"
                                       name="tempo_servico" placeholder="Ex: 60"
                                       value="{{ isset($servico) ? $servico->tempo_servico : old('tempo_servico') }}">
                                @if($errors->has('tempo_servico'))
                                    <div class="invalid-feedback">{{ $errors->first('tempo_servico') }}</div>
                                @endif
                            </div>

                            <!-- Comissão -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Comissão (%)</label>
                                <input type="text" id="comissao" class="form-control form-control-solid money @if($errors->has('comissao')) is-invalid @endif"
                                       name="comissao" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->comissao : old('comissao') }}">
                                @if($errors->has('comissao'))
                                    <div class="invalid-feedback">{{ $errors->first('comissao') }}</div>
                                @endif
                            </div>

                            <!-- Tolerância -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Tolerância (Min)</label>
                                <input type="text" id="tempo_tolerancia" class="form-control form-control-solid money @if($errors->has('tempo_tolerancia')) is-invalid @endif"
                                       name="tempo_tolerancia" placeholder="0"
                                       value="{{ isset($servico) ? $servico->tempo_tolerancia : old('tempo_tolerancia') }}">
                            </div>

                            <!-- Tempo Adicional -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Tempo Adicional (Min)</label>
                                <input type="text" id="tempo_adicional" class="form-control form-control-solid @if($errors->has('tempo_adicional')) is-invalid @endif"
                                       name="tempo_adicional" placeholder="Ex: 30"
                                       value="{{ isset($servico) ? $servico->tempo_adicional : old('tempo_adicional') }}">
                            </div>

                            <!-- Valor Adicional -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Valor Adicional (R$)</label>
                                <input type="text" id="valor_adicional" class="form-control form-control-solid money @if($errors->has('valor_adicional')) is-invalid @endif"
                                       name="valor_adicional" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->valor_adicional : old('valor_adicional') }}">
                            </div>
                        </div>

                        <div class="separator separator-dashed my-6"></div>

                        <!-- SEÇÃO 2: FISCAL NFS-E & REFORMA TRIBUTÁRIA -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-dark font-weight-bold mb-4">
                                    <i class="la la-file-invoice text-warning mr-1"></i> Tributação NFS-e Nacional & Reforma Tributária
                                </h5>
                            </div>

                            <!-- Código LC 116 / Código do Serviço -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Código Trib. Nacional (LC 116)</label>
                                <input type="text" id="codigo_tributacao_nacional" class="form-control form-control-solid @if($errors->has('codigo_tributacao_nacional')) is-invalid @endif"
                                       name="codigo_tributacao_nacional" placeholder="Ex: 070201 ou 07.02"
                                       value="{{ isset($servico) ? ($servico->codigo_tributacao_nacional ?? $servico->codigo_servico) : old('codigo_tributacao_nacional') }}">
                                <small class="form-text text-muted">Item segundo a LC 116/2003</small>
                            </div>

                            <!-- Código NBS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Código NBS</label>
                                <input type="text" id="codigo_nbs" class="form-control form-control-solid @if($errors->has('codigo_nbs')) is-invalid @endif"
                                       name="codigo_nbs" placeholder="Ex: 1.0101.10.00"
                                       value="{{ isset($servico) ? $servico->codigo_nbs : old('codigo_nbs') }}">
                                <small class="form-text text-muted">Classificação oficial NBS</small>
                            </div>

                            <!-- Select Dinâmico: CST IBS / CBS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">CST IBS / CBS</label>
                                <select class="custom-select form-control form-control-solid" name="cst_ibscbs">
                                    <option value="">Selecione...</option>
                                    @foreach($cstIbsCbs as $cst)
                                        <option value="{{ $cst->cst_ibs_cbs }}"
                                                @isset($servico) @if(($servico->cst_ibscbs ?? '') == $cst->cst_ibs_cbs) selected @endif @endisset>
                                            {{ $cst->cst_ibs_cbs }} - {{ $cst->descricao_cst_ibs_cbs }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Adicionar junto aos campos fiscais na Seção 2 do servicos/register.blade.php -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Exige Código de Obra / ART?</label>
                                <select class="custom-select form-control form-control-solid" name="exige_obra">
                                    <option value="0" @isset($servico) @if(($servico->exige_obra ?? 0) == 0) selected @endif @endisset>Não</option>
                                    <option value="1" @isset($servico) @if(($servico->exige_obra ?? 0) == 1) selected @endif @endisset>Sim (Construção Civil)</option>
                                </select>
                                <small class="form-text text-muted">Obriga informar obra ao emitir NFS-e</small>
                            </div>

                            <!-- Select Dinâmico: Classificação Tributária IBS / CBS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Classificação Tributária IBS/CBS</label>
                                <select class="custom-select form-control form-control-solid" name="codigo_class_trib">
                                    <option value="">Selecione...</option>
                                    @foreach($classTribIbsCbs as $class)
                                        <option value="{{ $class->cclasstrib }}"
                                                @isset($servico) @if(($servico->codigo_class_trib ?? '') == $class->cclasstrib) selected @endif @endisset>
                                            {{ $class->cclasstrib }} - {{ $class->nome_cclasstrib ?? $class->descricao_cclasstrib }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- % ISS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">% ISS</label>
                                <input type="text" id="aliquota_iss" class="form-control form-control-solid @if($errors->has('aliquota_iss')) is-invalid @endif"
                                       name="aliquota_iss" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->aliquota_iss : old('aliquota_iss') }}">
                            </div>

                            <!-- Retenção de ISS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">Retenção de ISS</label>
                                <select class="custom-select form-control form-control-solid" name="iss_retido">
                                    <option value="2" @isset($servico) @if(($servico->iss_retido ?? 2) == 2) selected @endif @endisset>Não Retido (Prestador Recolhe)</option>
                                    <option value="1" @isset($servico) @if(($servico->iss_retido ?? 2) == 1) selected @endif @endisset>Retido na Fonte (Tomador Retém)</option>
                                </select>
                            </div>

                            <!-- % IBS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">% IBS</label>
                                <input type="text" id="aliquota_ibs" class="form-control form-control-solid"
                                       data-mask="000,00" data-mask-reverse="true" name="aliquota_ibs" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->aliquota_ibs : old('aliquota_ibs') }}">
                            </div>

                            <!-- % CBS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">% CBS</label>
                                <input type="text" id="aliquota_cbs" class="form-control form-control-solid"
                                       data-mask="000,00" data-mask-reverse="true" name="aliquota_cbs" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->aliquota_cbs : old('aliquota_cbs') }}">
                            </div>

                            <!-- % INSS -->
                            <div class="form-group validated col-sm-6 col-lg-3">
                                <label class="font-weight-bold">% INSS</label>
                                <input type="text" id="aliquota_inss" class="form-control form-control-solid @if($errors->has('aliquota_inss')) is-invalid @endif"
                                       data-mask="000,00" data-mask-reverse="true" name="aliquota_inss" placeholder="0,00"
                                       value="{{ isset($servico) ? $servico->aliquota_inss : old('aliquota_inss') }}">
                            </div>
                        </div>
                        <!-- Rodapé com Botões -->
                        <div class="card-footer bg-light-secondary d-flex justify-content-end py-4">
                            <a class="btn btn-light-danger font-weight-bold mr-3" href="/servicos">
                                <i class="la la-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success font-weight-bold">
                                <i class="la la-check"></i> {{ isset($servico) ? 'Atualizar Serviço' : 'Salvar Serviço' }}
                            </button>
                        </div>
                    </div>
            </form>

        </div>
    </div>
@endsection
