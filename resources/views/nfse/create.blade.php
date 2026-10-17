@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-fiscal-mtr-refinements.css') }}?v={{ filemtime(public_path('css/ui-fiscal-mtr-refinements.css')) }}">
@endpush
@section('content')
    @section('css')
        <style type="text/css">
            .card-modern {
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 18px rgba(0,0,0,0.05);
                background: #ffffff;
                margin-bottom: 1.5rem;
            }

            .card-header-custom {
                background-color: #f8f9fa;
                border-bottom: 1px solid #ebedf2;
                padding: 1.25rem 1.5rem;
                border-top-left-radius: 12px !important;
                border-top-right-radius: 12px !important;
            }

            .section-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: #2b2b36;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .sub-section-title {
                font-size: 0.95rem;
                font-weight: 700;
                color: #3699ff;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-top: 1.2rem;
                margin-bottom: 1rem;
                border-bottom: 1px dashed #ebedf2;
                padding-bottom: 5px;
            }

            .form-label {
                font-weight: 600;
                color: #3f4254;
                font-size: 0.88rem;
                margin-bottom: 0.4rem;
            }

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
                background: rgba(255, 255, 255, 0.8) url("/loading.gif") 50% 50% no-repeat;
            }
        </style>
    @endsection

    <div class="document-fiscal-page document-fiscal-page--nfse ui-op-refine content d-flex flex-column flex-column-fluid" id="kt_content">

        <div class="row" id="anime" style="display: none">
            <div class="col-s8 offset-s2 text-center">
                <lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 250px;" autoplay></lottie-player>
            </div>
        </div>

        <!-- Header com Status e Informações do Ambiente -->
        <div class="card card-modern ui-op-document-head mb-5">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap py-4">
                <div class="d-flex align-items-center me-3">
                    <div class="symbol symbol-45px me-4">
                    <span class="symbol-label bg-light-primary">
                        <i class="flaticon2-document text-primary fs-2x"></i>
                    </span>
                    </div>
                    <div class="d-flex flex-column">
                        <h3 class="text-dark fw-bold my-0 fs-3">
                            {{ isset($item) && !isset($clone) ? 'Editar Nota Fiscal de Serviço (NFS-e)' : 'Nova Nota Fiscal de Serviço (NFS-e)' }}
                        </h3>
                        <span class="text-muted fs-7 fw-bold">Emissão de NFS-e e Integração com Contas a Receber</span>
                    </div>
                </div>

                @if(!empresaComFilial())
                    <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light-secondary text-dark p-3">
                    <i class="fa fa-hashtag text-muted me-1"></i> Última NFS-e: <strong>{{ \App\Models\Nfse::lastNfse() }}</strong>
                </span>
                        @if($config->ambiente == 2)
                            <span class="badge bg-light-warning text-warning p-3">
                        <i class="fa fa-exclamation-triangle me-1"></i> Ambiente: Homologação
                    </span>
                        @else
                            <span class="badge bg-light-success text-success p-3">
                        <i class="fa fa-check-circle me-1"></i> Ambiente: Produção
                    </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Formulário Principal da NFS-e -->
        <form class="form" id="form-servico" method="post" @if(isset($item) && !isset($clone)) action="/nfse/update/{{ $item->id }}" @else action="/nfse/store" @endif>
            @csrf
            @if(isset($item) && !isset($clone))
                @method('put')
            @endif
            <input type="hidden" id="_token" value="{{csrf_token()}}">

            <div class="row ui-op-local-row">
                {!! __view_locais_select() !!}
            </div>

            <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">

                <!-- 1. DADOS DO TOMADOR (CLIENTE) -->
                <div class="card card-modern">
                    <div class="card-header-custom">
                        <h4 class="section-title"><i class="fa fa-user text-primary"></i> 1. Informações do Tomador (Cliente)</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-lg-8 col-md-10 col-12">
                                <label class="form-label">Selecionar Cliente <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select required class="form-control select2-custom" id="cliente" name="cliente">
                                        <option value="">Selecione o cliente cadastrado...</option>
                                        @foreach($clientes as $c)
                                            <option @isset($item) @if($c->id == $item->cliente_id) selected @endif @endisset
                                            @if(old('cliente') == $c->id) selected @endif value="{{$c->id}}">
                                                {{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="novoCliente()" class="btn btn-primary" title="Cadastrar Novo Cliente">
                                        <i class="la la-plus"></i> Novo Cliente
                                    </button>
                                </div>
                                @if($errors->has('cliente'))
                                    <span class="text-danger fs-7">{{ $errors->first('cliente') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="sub-section-title"><i class="fa fa-id-card me-1"></i> Dados Cadastrais</div>

                        <div class="row">
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">CPF / CNPJ <span class="text-danger">*</span></label>
                                <input type="tel" value="{{{ isset($item) ? $item->documento : old('documento') }}}" name="documento" class="form-control cpf_cnpj @if($errors->has('documento')) is-invalid @endif" id="documento" placeholder="00.000.000/0000-00"/>
                                @if($errors->has('documento'))
                                    <div class="invalid-feedback">{{ $errors->first('documento') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-5 col-md-8 col-sm-6 mb-3">
                                <label class="form-label">Razão Social / Nome <span class="text-danger">*</span></label>
                                <input value="{{{ isset($item) ? $item->razao_social : old('razao_social') }}}" required type="text" name="razao_social" class="form-control @if($errors->has('razao_social')) is-invalid @endif" id="razao_social" placeholder="Razão social completa"/>
                                @if($errors->has('razao_social'))
                                    <div class="invalid-feedback">{{ $errors->first('razao_social') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">Inscrição Municipal (IM)</label>
                                <input value="{{{ isset($item) ? $item->im : old('im') }}}" type="tel" name="im" class="form-control" id="im" placeholder="Apenas números"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">Inscrição Estadual (IE)</label>
                                <input value="{{{ isset($item) ? $item->ie : old('ie') }}}" type="tel" name="ie" class="form-control" id="ie" placeholder="Apenas números"/>
                            </div>
                        </div>

                        <div class="sub-section-title"><i class="fa fa-map-marker-alt me-1"></i> Endereço e Contato</div>

                        <div class="row">
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">CEP <span class="text-danger">*</span></label>
                                <input required type="tel" name="cep" class="form-control cep @if($errors->has('cep')) is-invalid @endif" id="cep" value="{{{ isset($item) ? $item->cep : old('cep') }}}" placeholder="00000-000"/>
                                @if($errors->has('cep'))
                                    <div class="invalid-feedback">{{ $errors->first('cep') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-5 col-md-8 col-sm-12 mb-3">
                                <label class="form-label">Rua / Logradouro <span class="text-danger">*</span></label>
                                <input required type="text" name="rua" class="form-control @if($errors->has('rua')) is-invalid @endif" id="rua" value="{{{ isset($item) ? $item->rua : old('rua') }}}" placeholder="Nome da rua/avenida"/>
                                @if($errors->has('rua'))
                                    <div class="invalid-feedback">{{ $errors->first('rua') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Número <span class="text-danger">*</span></label>
                                <input required type="text" name="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" id="numero" value="{{{ isset($item) ? $item->numero : old('numero') }}}" placeholder="Nº"/>
                                @if($errors->has('numero'))
                                    <div class="invalid-feedback">{{ $errors->first('numero') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Bairro <span class="text-danger">*</span></label>
                                <input required type="text" name="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" id="bairro" value="{{{ isset($item) ? $item->bairro : old('bairro') }}}" placeholder="Bairro"/>
                                @if($errors->has('bairro'))
                                    <div class="invalid-feedback">{{ $errors->first('bairro') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Complemento</label>
                                <input type="text" name="complemento" class="form-control" id="complemento" value="{{{ isset($item) ? $item->complemento : old('complemento') }}}" placeholder="Apto, Sala..."/>
                            </div>

                            <div class="form-group col-lg-4 col-md-6 col-sm-12 mb-3">
                                <label class="form-label">Cidade do Tomador <span class="text-danger">*</span></label>
                                <select name="cidade_id" required style="width: 100%" class="form-control select2 select2-custom" id="cidade_id">
                                    @foreach(App\Models\Cidade::all() as $c)
                                        <option @isset($item) @if($c->id == $item->cidade_id) selected @endif @endisset
                                        @if(old('cidade_id') == $c->id) selected @endif value="{{$c->id}}">
                                            {{$c->nome}} ({{$c->uf}}) - IBGE: {{$c->codigo}}
                                        </option>
                                    @endforeach
                                </select>
                                @if($errors->has('cidade_id'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('cidade_id') }}</div>
                                @endif
                            </div>

                            <div class="form-group col-lg-3 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" id="email" value="{{{ isset($item) ? $item->email : old('email') }}}" placeholder="cliente@email.com"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">Telefone / Celular</label>
                                <input type="tel" name="telefone" class="form-control" id="telefone" value="{{{ isset($item) ? $item->telefone : old('telefone') }}}" placeholder="(00) 00000-0000"/>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DETALHES DO SERVIÇO & TRIBUTAÇÃO -->
                <div class="card card-modern">
                    <div class="card-header-custom">
                        <h4 class="section-title"><i class="fa fa-concierge-bell text-primary"></i> 2. Serviço e Tributação Fiscal</h4>
                    </div>
                    <div class="card-body">

                        <div class="sub-section-title"><i class="fa fa-list-alt me-1"></i> Dados do Serviço</div>

                        <div class="row">
                            <div class="form-group col-lg-6 col-12 mb-3">
                                <label class="form-label">Serviço Cadastrado <span class="text-danger">*</span></label>
                                <select required class="form-control select2-custom" style="width: 100% !important;" id="servico_id" name="servico_id">
                                    <option value="">Selecione o serviço...</option>
                                    @foreach($servicos as $s)
                                        <option @isset($item) @if($s->id == $item->servico->servico_id) selected @endif @endisset
                                        @if(old('servico_id') == $s->id) selected @endif value="{{$s->id}}">{{ $s->nome }}</option>
                                    @endforeach
                                </select>
                                @if($errors->has('servico_id'))
                                    <span class="text-danger fs-7">{{ $errors->first('servico_id') }}</span>
                                @endif
                            </div>

                            <div class="form-group col-lg-3 col-md-6 col-12 mb-3">
                                <label class="form-label">Natureza de Operação <span class="text-danger">*</span></label>
                                <select required class="form-control custom-select" name="natureza_operacao" id="natureza_operacao">
                                    <option value="1" @if(isset($item) && $item->natureza_operacao == 1) selected @endif>1 - Tributação no município</option>
                                    <option value="2" @if(isset($item) && $item->natureza_operacao == 2) selected @endif>2 - Tributação fora do município</option>
                                    <option value="3" @if(isset($item) && $item->natureza_operacao == 3) selected @endif>3 - Isenção</option>
                                    <option value="4" @if(isset($item) && $item->natureza_operacao == 4) selected @endif>4 - Imunidade</option>
                                    <option value="5" @if(isset($item) && $item->natureza_operacao == 5) selected @endif>5 - Exigibilidade suspensa (Judicial)</option>
                                    <option value="6" @if(isset($item) && $item->natureza_operacao == 6) selected @endif>6 - Exigibilidade suspensa (Admin.)</option>
                                </select>
                            </div>

                            <div class="form-group col-lg-3 col-md-6 col-12 mb-3">
                                <label class="form-label">Valor do Serviço (R$) <span class="text-danger">*</span></label>
                                <input required type="text" name="valor_servico" class="form-control money @if($errors->has('valor_servico')) is-invalid @endif" id="valor_servico" value="{{{ isset($item) ? moeda($item->servico->valor_servico) : old('valor_servico') }}}" placeholder="0,00"/>
                            </div>

                            <div class="form-group col-12 mb-3">
                                <label class="form-label">Discriminação dos Serviços <span class="text-danger">*</span></label>
                                <textarea required rows="4" name="discriminacao" class="form-control @if($errors->has('discriminacao')) is-invalid @endif" id="discriminacao" placeholder="Descreva os serviços prestados...">{{{ isset($item) ? $item->servico->discriminacao : old('discriminacao') }}}</textarea>
                            </div>
                        </div>

                        <div class="sub-section-title"><i class="fa fa-file-invoice-dollar me-1"></i> Códigos Fiscais e Local da Prestação</div>

                        <div class="row">
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Cód. Serviço (LC 116) <span class="text-danger">*</span></label>
                                <input required type="tel" name="codigo_servico" class="form-control @if($errors->has('codigo_servico')) is-invalid @endif" id="codigo_servico" value="{{{ isset($item) ? ($item->servico->codigo_servico) : old('codigo_servico') }}}" placeholder="Ex: 1405"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Cód. Tributação do Município</label>
                                <input type="tel" name="codigo_tributacao_municipio" class="form-control" id="codigo_tributacao_municipio" value="{{{ isset($item) ? ($item->servico->codigo_tributacao_municipio) : $config->codigo_tributacao_municipio }}}"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Cód. CNAE</label>
                                <input type="tel" name="codigo_cnae" class="form-control" id="codigo_cnae" value="{{{ isset($item) ? ($item->servico->codigo_cnae) : old('codigo_cnae') }}}"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Data da Competência</label>
                                <input value="{{{ isset($item) ? ($item->servico->data_competencia) : old('data_competencia') }}}" type="date" name="data_competencia" class="form-control" id="data_competencia"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">Exigibilidade ISS <span class="text-danger">*</span></label>
                                <select class="form-control custom-select" name="exigibilidade_iss" id="exigibilidade_iss">
                                    @foreach(\App\Models\Nfse::exigibilidades() as $key => $e)
                                        <option @isset($item) @if($item->exigibilidade_iss == $key) selected @endif @endif value="{{$key}}">{{$e}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-lg-2 col-md-3 col-sm-6 mb-3">
                                <label class="form-label">ISS Retido <span class="text-danger">*</span></label>
                                <select class="form-control custom-select" name="iss_retido" id="iss_retido">
                                    <option @isset($item) @if($item->servico->iss_retido == 2) selected @endif @endif value="2">Não</option>
                                    <option @isset($item) @if($item->servico->iss_retido == 1) selected @endif @endif value="1">Sim</option>
                                </select>
                            </div>

                            <div class="form-group col-lg-3 col-md-3 col-sm-6 mb-3">
                                <label class="form-label">Resp. Retenção</label>
                                <select class="form-control custom-select" name="responsavel_retencao_iss" id="responsavel_retencao_iss">
                                    <option @isset($item) @if($item->servico->responsavel_retencao_iss == 1) selected @endif @endif value="1">Tomador</option>
                                    <option @isset($item) @if($item->servico->responsavel_retencao_iss == 2) selected @endif @endif value="2">Intermediário</option>
                                </select>
                            </div>

                            <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">UF da Prestação</label>
                                <select class="custom-select form-control" id="estado_local_prestacao_servico" name="estado_local_prestacao_servico">
                                    @foreach(App\Models\Cidade::estados() as $e)
                                        <option @isset($item) @if($item->estado_local_prestacao_servico == $e) selected @endif @else @if($e == $config->UF) selected @endif @endif value="{{$e}}">{{$e}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Cidade da Prestação integrada com a tabela Cidades -->
                            <div class="form-group col-lg-2 col-md-6 col-sm-6 mb-3">
                                <label class="form-label">Cidade da Prestação</label>
                                <select name="cidade_local_prestacao_servico" id="cidade_local_prestacao_servico" class="form-control select2 select2-custom">
                                    <option value="">Selecione a cidade...</option>
                                    @foreach(App\Models\Cidade::all() as $c)
                                        <option value="{{ $c->codigo }}"
                                                @isset($item)
                                                    @if($item->servico->cidade_local_prestacao_servico == $c->codigo || $item->servico->cidade_local_prestacao_servico == $c->nome) selected @endif
                                                @else
                                                    @if($c->codigo == $config->codMun) selected @endif
                                            @endisset>
                                            {{ $c->nome }} ({{ $c->uf }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="sub-section-title"><i class="fa fa-percent me-1"></i> Retenções e Alíquotas de Impostos</div>

                        <div class="row">
                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Valor Deduções</label>
                                <input type="text" name="valor_deducoes" class="form-control money" id="valor_deducoes" value="{{{ isset($item) ? moeda($item->servico->valor_deducoes) : old('valor_deducoes') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Desc. Incondicional</label>
                                <input type="text" name="desconto_incondicional" class="form-control money" id="desconto_incondicional" value="{{{ isset($item) ? moeda($item->servico->desconto_incondicional) : old('desconto_incondicional') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Desc. Condicional</label>
                                <input type="text" name="desconto_condicional" class="form-control money" id="desconto_condicional" value="{{{ isset($item) ? moeda($item->servico->desconto_condicional) : old('desconto_condicional') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Outras Retenções</label>
                                <input type="text" name="outras_retencoes" class="form-control money" id="outras_retencoes" value="{{{ isset($item) ? moeda($item->servico->outras_retencoes) : old('outras_retencoes') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota ISS (%)</label>
                                <input type="text" name="aliquota_iss" class="form-control money" id="aliquota_iss" value="{{{ isset($item) ? moeda($item->servico->aliquota_iss) : old('aliquota_iss') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota PIS (%)</label>
                                <input type="text" name="aliquota_pis" class="form-control money" id="aliquota_pis" value="{{{ isset($item) ? moeda($item->servico->aliquota_pis) : old('aliquota_pis') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota COFINS (%)</label>
                                <input type="text" name="aliquota_cofins" class="form-control money" id="aliquota_cofins" value="{{{ isset($item) ? moeda($item->servico->aliquota_cofins) : old('aliquota_cofins') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota INSS (%)</label>
                                <input type="text" name="aliquota_inss" class="form-control money" id="aliquota_inss" value="{{{ isset($item) ? moeda($item->servico->aliquota_inss) : old('aliquota_inss') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota IR (%)</label>
                                <input type="text" name="aliquota_ir" class="form-control money" id="aliquota_ir" value="{{{ isset($item) ? moeda($item->servico->aliquota_ir) : old('aliquota_ir') }}}"/>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Alíquota CSLL (%)</label>
                                <input type="text" name="aliquota_csll" class="form-control money" id="aliquota_csll" value="{{{ isset($item) ? moeda($item->servico->aliquota_csll) : old('aliquota_csll') }}}"/>
                            </div>
                        </div>

                        <div class="sub-section-title"><i class="fa fa-users me-1"></i> Intermediário do Serviço</div>

                        <div class="row">
                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3">
                                <label class="form-label">Tipo de Intermediário</label>
                                <select class="form-control custom-select" name="intermediador" id="intermediador">
                                    <option @isset($item) @if($item->intermediador == 'n') selected @endif @endif value="n">Sem Intermediário</option>
                                    <option @isset($item) @if($item->intermediador == 'f') selected @endif @endif value="f">Pessoa Física</option>
                                    <option @isset($item) @if($item->intermediador == 'j') selected @endif @endif value="j">Pessoa Jurídica</option>
                                </select>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3 pfj d-none">
                                <label class="form-label">CPF/CNPJ Intermediário</label>
                                <input type="tel" name="documento_intermediador" class="form-control cpf_cnpj" id="documento_intermediador" value="{{{ isset($item) ? ($item->servico->documento_intermediador) : old('documento_intermediador') }}}"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3 pfj d-none">
                                <label class="form-label">Nome / Razão Social</label>
                                <input type="text" name="nome_intermediador" class="form-control" id="nome_intermediador" value="{{{ isset($item) ? ($item->servico->nome_intermediador) : old('nome_intermediador') }}}"/>
                            </div>

                            <div class="form-group col-lg-3 col-md-4 col-sm-6 mb-3 pfj d-none">
                                <label class="form-label">Inscrição Municipal (IM)</label>
                                <input type="tel" name="im_intermediador" class="form-control" id="im_intermediador" value="{{{ isset($item) ? ($item->servico->im_intermediador) : old('im_intermediador') }}}"/>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 3. CONDIÇÕES DE PAGAMENTO E FINANCEIRO -->
                <div class="card card-modern">
                    <div class="card-header-custom">
                        <h4 class="section-title"><i class="fa fa-dollar-sign text-success"></i> 3. Condições de Pagamento e Contas a Receber</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-lg-3 col-md-4 col-12 mb-3">
                                <label class="form-label">Gerar no Contas a Receber?</label>
                                <select class="form-control custom-select border" name="gerar_contas_receber" id="gerar_contas_receber">
                                    <option value="1">Sim - Gerar Títulos</option>
                                    <option value="0">Não - Apenas Nota Fiscal</option>
                                </select>
                            </div>

                            <!-- Categoria Financeira com borda visível -->
                            <div class="form-group col-lg-3 col-md-4 col-12 mb-3 area-financeiro">
                                <label class="form-label">Categoria Financeira <span class="text-danger">*</span></label>
                                <select required class="form-control custom-select border" name="categoria_conta_id" id="categoria_conta_id" style="width: 100% !important; display: block !important;">
                                    <option value="">Selecione a categoria...</option>
                                    @if(isset($categorias))
                                        @foreach($categorias as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                                <label class="form-label">Tipo de Pagamento</label>
                                <select class="form-control custom-select border" name="tipo_pagamento" id="tipo_pagamento">
                                    @if(isset($tiposPagamento))
                                        @foreach($tiposPagamento as $tp)
                                            <option value="{{$tp}}">{{$tp}}</option>
                                        @endforeach
                                    @else
                                        <option value="Boleto">Boleto</option>
                                        <option value="Pix">Pix</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Transferência">Transferência</option>
                                    @endif
                                </select>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                                <label class="form-label">Qtd. de Parcelas</label>
                                <select class="form-control custom-select border" id="qtd_parcelas" onchange="gerarParcelas()">
                                    <option value="1">1x (À vista / Parcela Única)</option>
                                    <option value="2">2x</option>
                                    <option value="3">3x</option>
                                    <option value="4">4x</option>
                                    <option value="5">5x</option>
                                    <option value="6">6x</option>
                                </select>
                            </div>

                            <div class="form-group col-lg-2 col-md-4 col-12 mb-3 area-financeiro">
                                <label class="form-label">Primeiro Vencimento</label>
                                <input type="date" id="primeira_data" class="form-control border" value="{{ date('Y-m-d') }}" onchange="gerarParcelas()"/>
                            </div>
                        </div>

                        <!-- Tabela Dinâmica de Parcelas -->
                        <div class="area-financeiro">
                            <div class="sub-section-title"><i class="fa fa-calendar-alt me-1"></i> Parcelamento Gerado</div>
                            <table class="table table-bordered table-striped" id="tabela_parcelas">
                                <thead class="bg-light">
                                <tr>
                                    <th style="width: 20%;">Parcela</th>
                                    <th style="width: 40%;">Data de Vencimento</th>
                                    <th style="width: 40%;">Valor (R$)</th>
                                </tr>
                                </thead>
                                <tbody id="container_parcelas">
                                <!-- Preenchido dinamicamente via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Botão de Ação Rodapé -->
                <div class="card card-modern">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <a href="/nfse" class="btn btn-secondary font-weight-bold">
                            <i class="fa fa-arrow-left me-1"></i> Voltar
                        </a>
                        <button type="submit" id="salvar" class="btn btn-success font-weight-bold px-8">
                            <i class="fa fa-save me-1"></i> Salvar NFS-e
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <!-- Modal Cadastrar Novo Cliente -->
    <div class="modal fade document-fiscal-dialog" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white"><i class="fa fa-user-plus me-2"></i> Cadastrar Novo Cliente</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row align-items-end mb-3">
                                <div class="form-group col-sm-8 col-lg-6">
                                    <label class="form-label" id="lbl_cpf_cnpj">CPF / CNPJ</label>
                                    <div class="input-group">
                                        <input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj" placeholder="Digite o CPF ou CNPJ">
                                        <button type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-primary" title="Consultar CNPJ na Receita">
                                            <i class="fa fa-search me-1"></i> Consultar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="sub-section-title"><i class="fa fa-id-card me-1"></i> Identificação</div>

                            <div class="row">
                                <div class="form-group col-sm-10 col-lg-6 mb-3">
                                    <label class="form-label">Razão Social / Nome <span class="text-danger">*</span></label>
                                    <input id="razao_social2" type="text" class="form-control">
                                </div>

                                <div class="form-group col-sm-10 col-lg-6 mb-3">
                                    <label class="form-label">Nome Fantasia</label>
                                    <input id="nome_fantasia2" type="text" class="form-control">
                                </div>

                                <div class="form-group col-sm-3 col-lg-3 mb-3">
                                    <label class="form-label" id="lbl_ie_">Inscrição Estadual (IE)</label>
                                    <input type="text" id="ie_rg" class="form-control">
                                </div>

                                <div class="form-group col-lg-3 col-md-3 col-sm-6 mb-3">
                                    <label class="form-label">Consumidor Final</label>
                                    <select class="custom-select form-control" id="consumidor_final">
                                        <option value="1">SIM</option>
                                        <option value="0">NÃO</option>
                                    </select>
                                </div>

                                <div class="form-group col-lg-3 col-md-3 col-sm-6 mb-3">
                                    <label class="form-label">Contribuinte</label>
                                    <select class="custom-select form-control" id="contribuinte">
                                        <option value="1">SIM</option>
                                        <option value="0">NÃO</option>
                                    </select>
                                </div>

                                <div class="form-group col-sm-3 col-lg-3 mb-3">
                                    <label class="form-label">Limite de Venda (R$)</label>
                                    <input type="text" id="limite_venda" class="form-control money" value="0,00">
                                </div>
                            </div>

                            <div class="sub-section-title"><i class="fa fa-map-marked-alt me-1"></i> Endereço Principal</div>

                            <div class="row">
                                <div class="form-group col-sm-8 col-lg-2 mb-3">
                                    <label class="form-label">CEP</label>
                                    <input id="cep-modal" type="text" class="form-control cep">
                                </div>

                                <div class="form-group col-sm-8 col-lg-6 mb-3">
                                    <label class="form-label">Rua / Logradouro</label>
                                    <input id="rua-modal" type="text" class="form-control">
                                </div>

                                <div class="form-group col-sm-2 col-lg-2 mb-3">
                                    <label class="form-label">Número</label>
                                    <input id="numero-modal" type="text" class="form-control">
                                </div>

                                <div class="form-group col-sm-8 col-lg-2 mb-3">
                                    <label class="form-label">Bairro</label>
                                    <input id="bairro-modal" type="text" class="form-control">
                                </div>

                                <div class="form-group col-lg-4 col-md-6 col-sm-12 mb-3">
                                    <label class="form-label">Cidade</label>
                                    <select style="width: 100%" class="form-control select2" id="kt_select2_5">
                                        @foreach(App\Models\Cidade::all() as $c)
                                            <option value="{{$c->id}}">
                                                {{$c->nome}} ({{$c->uf}})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-8 col-lg-3 mb-3">
                                    <label class="form-label">E-mail</label>
                                    <input id="email-modal" type="email" class="form-control">
                                </div>

                                <div class="form-group col-sm-8 col-lg-2 mb-3">
                                    <label class="form-label">Telefone</label>
                                    <input id="telefone-modal" type="text" class="form-control">
                                </div>

                                <div class="form-group col-sm-8 col-lg-3 mb-3">
                                    <label class="form-label">Celular</label>
                                    <input id="celular" type="text" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancelar</button>
                    <button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold px-6">
                        <i class="fa fa-save me-1"></i> Salvar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-loading loading-class"></div>
@endsection

@section('javascript')
    <script type="text/javascript">
        $('#intermediador').change(() => {
            changeIntermediador()
        })

        function changeIntermediador(){
            let v = $('#intermediador').val()
            if(v == 'n'){
                $('.pfj').addClass('d-none')
            }else{
                $('.pfj').removeClass('d-none')
            }
        }

        $('#cliente').change(() => {
            let cliente = $('#cliente').val()
            if(cliente){
                buscaCliente(cliente)
            }
        })

        function buscaCliente(id){
            $.get(path + 'clientes/findCliente/'+id)
                .done((res) => {
                    $('#documento').val(res.cpf_cnpj)
                    $('#razao_social').val(res.razao_social)
                    $('#cep').val(res.cep)
                    $('#rua').val(res.rua)
                    $('#ie').val(res.ie_rg)
                    $('#numero').val(res.numero)
                    $('#bairro').val(res.bairro)
                    $('#complemento').val(res.complemento)
                    $('#email').val(res.email)
                    $('#telefone').val(res.telefone)
                    $('#cidade_id').val(res.cidade_id).change()
                })
                .fail((err) => {
                    console.log(err)
                })
        }

        $('#servico_id').change(() => {
            let servico = $('#servico_id').val()
            if(servico){
                buscaServico(servico)
            }
        })

        function buscaServico(id){
            $.get(path + 'servicos/find/' + id)
                .done((res) => {
                    console.log("Dados do serviço retornado:", res);

                    // 1. Discriminação e Valor do Serviço
                    $('#discriminacao').val(res.discriminacao || res.nome || '');
                    if (res.valor) {
                        $('#valor_servico').val(res.valor.toString().replace(".", ","));
                    }

                    // 2. Mapeamento flexível do Código do Serviço (LC 116)
                    let codLC116 = res.codigo_servico || res.codigo_lc116 || res.codigo_trib_nac || res.cTribNac || res.codigo_tributacao_nacional || '';
                    $('#codigo_servico').val(codLC116);

                    // 3. Código de Tributação do Município (com fallback para o LC 116)
                    let codTribMun = res.codigo_tributacao_municipio || res.codigo_trib_mun || codLC116;
                    $('#codigo_tributacao_municipio').val(codTribMun);

                    // 4. CNAE, Natureza e Alíquotas
                    $('#codigo_cnae').val(res.codigo_cnae || res.cnae || '');
                    if (res.natureza) {
                        $('#natureza_operacao').val(res.natureza);
                    }

                    $('#aliquota_iss').val(res.aliquota_iss ? res.aliquota_iss.toString().replace(".", ",") : '');
                    $('#aliquota_pis').val(res.aliquota_pis ? res.aliquota_pis.toString().replace(".", ",") : '');
                    $('#aliquota_cofins').val(res.aliquota_cofins ? res.aliquota_cofins.toString().replace(".", ",") : '');
                    $('#aliquota_inss').val(res.aliquota_inss ? res.aliquota_inss.toString().replace(".", ",") : '');

                    // Atualiza a tabela financeira/parcelas se ativo
                    if (typeof gerarParcelas === 'function') {
                        gerarParcelas();
                    }
                })
                .fail((err) => {
                    console.error("Erro ao carregar o serviço:", err);
                });
        }

        function novoCliente(){
            $('#modal-cliente').modal('show')
        }

        function consultaCadastro() {
            let cnpj = $('#cpf_cnpj').val().replace(/[^0-9]/g,'')

            if (cnpj.length == 14){
                $('#btn-consulta-cadastro').addClass('spinner spinner-white spinner-right')
                $.get('https://publica.cnpj.ws/cnpj/' + cnpj)
                    .done((data) => {
                        $('#btn-consulta-cadastro').removeClass('spinner spinner-white spinner-right')
                        if (data!= null) {
                            let ie = ''
                            if (data.estabelecimento.inscricoes_estaduais.length > 0) {
                                ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
                            }
                            $('#ie_rg').val(ie)
                            $('#razao_social2').val(data.razao_social)
                            $('#nome_fantasia2').val(data.estabelecimento.nome_fantasia)
                            $("#rua-modal").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
                            $('#numero-modal').val(data.estabelecimento.numero)
                            $("#bairro-modal").val(data.estabelecimento.bairro);
                            let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
                            $('#cep-modal').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
                            $('#email-modal').val(data.estabelecimento.email)
                            $('#telefone-modal').val(data.estabelecimento.telefone1)

                            findCidadeCodigo(data.estabelecimento.cidade.ibge_id)
                        }
                    })
                    .fail((err) => {
                        $('#btn-consulta-cadastro').removeClass('spinner spinner-white spinner-right')
                        console.log(err)
                        swal("Erro", err.responseJSON ? err.responseJSON.titulo : "Erro ao consultar CNPJ", "error")
                    })
            }else{
                swal("Alerta", "Informe corretamente o CNPJ (14 dígitos)", "warning")
            }
        }

        function findCidadeCodigo(codigo_ibge){
            $.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
                .done((res) => {
                    $('#kt_select2_5').val(res.id).change();
                })
                .fail((err) => {
                    console.log(err)
                })
        }

        function limparCamposCliente(){
            $('#razao_social2').val('')
            $('#nome_fantasia2').val('')
            $('#rua-modal').val('')
            $('#numero-modal').val('')
            $('#bairro-modal').val('')
            $('#cep-modal').val('')
            $('#kt_select2_5').val('1').change();
        }

        function salvarCliente(){
            let js = {
                razao_social: $('#razao_social2').val(),
                nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
                rua: $('#rua-modal').val() ? $('#rua-modal').val() : '',
                numero: $('#numero-modal').val() ? $('#numero-modal').val() : '',
                cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
                ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
                bairro: $('#bairro-modal').val() ? $('#bairro-modal').val() : '',
                cep: $('#cep-modal').val() ? $('#cep-modal').val() : '',
                consumidor_final: $('#consumidor_final').val() ? $('#consumidor_final').val() : '',
                contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
                limite_venda: $('#limite_venda').val() ? $('#limite_venda').val() : '',
                cidade_id: $('#kt_select2_5').val() ? $('#kt_select2_5').val() : 1,
                telefone: $('#telefone-modal').val() ? $('#telefone-modal').val() : '',
                celular: $('#celular-modal').val() ? $('#celular-modal').val() : '',
                email: $('#email-modal').val() ? $('#email-modal').val() : '',
            }

            if(js.razao_social == ''){
                swal("Erro", "Informe a razão social", "warning")
            }else{
                let token = $('#_token').val();
                $.post(path + 'clientes/quickSave', {
                    _token: token,
                    data: js
                })
                    .done((res) =>{
                        limparCamposCliente()
                        $('#cliente').append('<option value="'+res.id+'">'+ res.razao_social+'</option>').change();
                        swal("Sucesso", "Cliente adicionado com sucesso!", 'success')
                            .then(() => {
                                $('#modal-cliente').modal('hide')
                                $('#cliente').val(res.id).change();
                            })
                    })
                    .fail((err) => {
                        console.log(err)
                        swal("Alerta", err.responseJSON || "Erro ao salvar cliente", "warning")
                    })
            }
        }

        // JS Lógica Financeira (Calculador de Parcelas)
        $('#gerar_contas_receber').change(function() {
            if ($(this).val() == '1') {
                $('.area-financeiro').show();
            } else {
                $('.area-financeiro').hide();
            }
        });

        function gerarParcelas() {
            let qtd = parseInt($('#qtd_parcelas').val()) || 1;
            let valorTotalStr = $('#valor_servico').val() || '0';
            let valorTotal = parseFloat(valorTotalStr.replace(/\./g, '').replace(',', '.')) || 0;
            let dataInicial = $('#primeira_data').val();

            let container = $('#container_parcelas');
            container.empty();

            if (valorTotal <= 0 || !dataInicial) return;

            let valorParcela = (valorTotal / qtd).toFixed(2);
            let data = new Date(dataInicial + 'T00:00:00');

            for (let i = 0; i < qtd; i++) {
                let dataFormatada = data.toISOString().split('T')[0];
                let row = `<tr>
                <td><strong>Parcela ${i + 1}/${qtd}</strong></td>
                <td>
                    <input type="date" name="parcelas[${i}][vencimento]" class="form-control" value="${dataFormatada}" required />
                </td>
                <td>
                    <input type="text" name="parcelas[${i}][valor]" class="form-control money" value="${valorParcela.replace('.', ',')}" required />
                </td>
            </tr>`;
                container.append(row);
                data.setMonth(data.getMonth() + 1);
            }
        }

        $(document).ready(function() {
            gerarParcelas();
            $('#valor_servico').on('change blur keyup', function() {
                gerarParcelas();
            });
        });
        @section('javascript')
        <script type="text/javascript">
        $('#intermediador').change(() => {
            changeIntermediador();
        });

        function changeIntermediador(){
            let v = $('#intermediador').val();
            if(v == 'n'){
            $('.pfj').addClass('d-none');
        }else{
            $('.pfj').removeClass('d-none');
        }
        }

        $('#cliente').change(() => {
            let cliente = $('#cliente').val();
            if(cliente){
            buscaCliente(cliente);
        }
        });

        function buscaCliente(id){
            $.get(path + 'clientes/findCliente/' + id)
                .done((res) => {
                    $('#documento').val(res.cpf_cnpj);
                    $('#razao_social').val(res.razao_social);
                    $('#cep').val(res.cep);
                    $('#rua').val(res.rua);
                    $('#ie').val(res.ie_rg);
                    $('#numero').val(res.numero);
                    $('#bairro').val(res.bairro);
                    $('#complemento').val(res.complemento);
                    $('#email').val(res.email);
                    $('#telefone').val(res.telefone);
                    $('#cidade_id').val(res.cidade_id).change();
                });
        }

        $('#servico_id').change(() => {
            let servico = $('#servico_id').val();
            if(servico){
            buscaServico(servico);
        }
        });

        function buscaServico(id){
            $.get(path + 'servicos/find/' + id)
                .done((res) => {
                    console.log("Dados do serviço retornado:", res); // Isto vai ajudar a depurar no console (F12)

                    // 1. Discriminação e Valor
                    $('#discriminacao').val(res.discriminacao || res.nome || '');
                    if (res.valor) {
                        $('#valor_servico').val(res.valor.toString().replace(".", ","));
                    }

                    // 2. CORREÇÃO: Captura do Código do Serviço (Tenta os nomes de colunas mais comuns em ERPs)
                    let codLC116 = res.codigo_servico || res.codigo_lc116 || res.codigo_tributacao_nacional || res.cTribNac || res.codigo_trib_nac || res.codigo || '';
                    $('#codigo_servico').val(codLC116);

                    // 3. CORREÇÃO: Captura do Código Municipal (com fallback)
                    let codTribMun = res.codigo_tributacao_municipio || res.codigo_trib_mun || codLC116;
                    $('#codigo_tributacao_municipio').val(codTribMun);

                    // 4. CNAE e outros
                    $('#codigo_cnae').val(res.codigo_cnae || res.cnae || '');
                    if (res.natureza) $('#natureza_operacao').val(res.natureza);

                    $('#aliquota_iss').val(res.aliquota_iss ? res.aliquota_iss.toString().replace(".", ",") : '');
                    $('#aliquota_pis').val(res.aliquota_pis ? res.aliquota_pis.toString().replace(".", ",") : '');
                    $('#aliquota_cofins').val(res.aliquota_cofins ? res.aliquota_cofins.toString().replace(".", ",") : '');
                    $('#aliquota_inss').val(res.aliquota_inss ? res.aliquota_inss.toString().replace(".", ",") : '');

                    gerarParcelas();
                })
                .fail((err) => {
                    console.error("Erro ao buscar dados do serviço:", err);
                });
        }

        function novoCliente(){
            $('#modal-cliente').modal('show');
        }

        $('#gerar_contas_receber').change(function() {
            if ($(this).val() == '1') {$('.area-financeiro').show();
        } else {
            $('.area-financeiro').hide();
        }
        });

        function gerarParcelas() {
            let qtd = parseInt($('#qtd_parcelas').val()) || 1;
            let valorTotalStr = $('#valor_servico').val() || '0';
            let valorTotal = parseFloat(valorTotalStr.replace(/\./g, '').replace(',', '.')) || 0;
            let dataInicial = $('#primeira_data').val();

            let container = $('#container_parcelas');
            container.empty();

            if (valorTotal <= 0 || !dataInicial) return;

            let valorParcela = (valorTotal / qtd).toFixed(2);
            let data = new Date(dataInicial + 'T00:00:00');

            for (let i = 0; i < qtd; i++) {
            let dataFormatada = data.toISOString().split('T')[0];
            let row = `<tr>
                <td><strong>Parcela ${i + 1}/${qtd}</strong></td>
                <td>
                    <input type="date" name="parcelas[${i}][vencimento]" class="form-control" value="${dataFormatada}" required />
                </td>
                <td>
                    <input type="text" name="parcelas[${i}][valor]" class="form-control money" value="${valorParcela.replace('.', ',')}" required />
                </td>
            </tr>`;
            container.append(row);
            data.setMonth(data.getMonth() + 1);
        }
        }

        $(document).ready(function() {
            gerarParcelas();
            $('#valor_servico').on('change blur keyup', function() {
            gerarParcelas();
        });

            // Submit seguro do formulário
            $('#form-servico').on('submit', function() {
            let btnSalvar = $('#salvar');
            btnSalvar.addClass('spinner spinner-white spinner-right disabled');
        });
        });
    </script>

    @if(!isset($item) || isset($clone))
        <script type="text/javascript" src="/js/nfse_clone.js"></script>
    @endif
@endsection
