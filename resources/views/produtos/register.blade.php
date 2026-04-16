@extends('default.layout')
@section('content')
    <style type="text/css">
        .modal-body-grade{
            height: 70vh;
            overflow-y: auto;
        }
        /* Apenas para garantir que o “cartão” ocupe a largura completa */
        #prateleira_detalhes .card {
            border: 1px solid #e2e5ec;
            box-shadow: none;
        }
        #prateleira_detalhes .card-header {
            background-color: #f5f8fa;
        }
    </style>
    <div class=" d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="card card-custom gutter-b example example-compact">
            <div class=" @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                <div class="col-lg-12">
                    <br>
                    <form method="post" action="{{{ isset($produto) ? '/produtos/update': '/produtos/save' }}}" enctype="multipart/form-data">


                        <input type="hidden" name="id" value="{{{ isset($produto) ? $produto->id : 0 }}}">
                        <div class="card card-custom gutter-b example example-compact">
                            <div class="card-header">

                                <h3 class="card-title">{{isset($produto) ? 'Editar' : 'Novo'}} Produto @isset($tipo_grade) - Grade @endif</h3>
                            </div>

                        </div>
                        <input type="hidden" value="{{csrf_token()}}" id="_token" name="_token">

                        <div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
                            <!--begin: Wizard Nav-->

                            <div class="wizard-nav">

                                <div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
                                    <!--begin::Wizard Step 1 Nav-->
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
                                    <!--end::Wizard Step 1 Nav-->
                                    <!--begin::Wizard Step 2 Nav-->
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
                                    {{-- Passo 3: CONTROLE DE PESAGENS --}}
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                                        <div class="wizard-label">
                                            <h3 class="wizard-title">
                                                <span>CONTROLE DE PESAGENS</span>
                                            </h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>

                                    {{-- Passo 4: ENDEREÇO DE ESTOQUE --}}
                                    <div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
                                        <div class="wizard-label">
                                            <h3 class="wizard-title">
                                                <span>PRATELEIRA</span>
                                            </h3>
                                            <div class="wizard-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

                                    <!--begin: Wizard Form-->
                                    <form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
                                        <!--begin: Wizard Step 1-->
                                        <p class="kt-widget__data text-danger">Campos com (*) obrigatório</p>

                                        <div class="pb-5" data-wizard-type="step-content">
                                            <div class="row">

                                                <div class="col-xl-12">
                                                    <div class="row">

                                                        <div class="form-group validated col-sm-9 col-lg-9">
                                                            <label class="col-form-label">Nome <strong class="text-danger">*</strong></label>
                                                            <div class="">
                                                                <input autofocus type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($produto) ? $produto->nome : old('nome') }}}">
                                                                @if($errors->has('nome'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('nome') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @isset($produto)
                                                            {!! __view_locais_edit($produto->locais, 'Disponibilidade') !!}
                                                        @else
                                                            {!! __view_locais('Disponibilidade') !!}
                                                        @endif

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Referência</label>
                                                            <div class="">
                                                                <input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="{{{ isset($produto) ? $produto->referencia : old('referencia') }}}">
                                                                @if($errors->has('referencia'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('referencia') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Valor de Compra <strong class="text-danger">*</strong></label>
                                                            <div class="">
                                                                <input type="tel" id="valor_compra" class="form-control @if($errors->has('valor_compra')) is-invalid @endif money" name="valor_compra" value="{{{ isset($produto) ? number_format($produto->valor_compra, $casasDecimais, ',', '') : old('valor_compra') }}}">
                                                                @if($errors->has('valor_compra'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('valor_compra') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">% lucro <strong class="text-danger">*</strong></label>
                                                            <div class="input-group">
                                                                <input type="text" id="percentual_lucro" class="form-control @if($errors->has('percentual_lucro')) is-invalid @endif money" name="percentual_lucro" value="{{{ isset($produto) ? $produto->percentual_lucro : $config->percentual_lucro_padrao }}}">
                                                                @if($errors->has('percentual_lucro'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('percentual_lucro') }}
                                                                    </div>
                                                                @endif

                                                                <div class="input-group-prepend">
																<span class="input-group-text btn-light btn" onclick="calcCusto()">
																	<i class="la la-calculator"></i>
																</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Valor de Venda <strong class="text-danger">*</strong></label>
                                                            <div class="">

                                                                <input type="tel" id="valor_venda" class="form-control @if($errors->has('valor_venda')) is-invalid @endif money" name="valor_venda" value="{{{ isset($produto) ? number_format($produto->valor_venda, $casasDecimais, ',', '') : old('valor_venda') }}}">
                                                                @if($errors->has('valor_venda'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('valor_venda') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if(is_adm())
                                                            @if(isset($produto))
                                                                <div class="form-group validated col-sm-3 col-lg-2 estoque-matriz">
                                                                    <label class="col-form-label">Estoque Atual</label>
                                                                    <div class="">
                                                                        <input data-mask="000000000,000" data-mask-reverse="true" type="text" id="estoque" class="form-control @if($errors->has('estoque')) is-invalid @endif" name="estoque"
                                                                               value="@if($produto->estoque)@if($produto->unidade_venda == 'UN' || $produto->unidade_venda == 'UNID'){{number_format($produto->estoque->quantidade,0, '', '')}}@else{{$produto->estoque->quantidade}}@endif @else 0 @endif">
                                                                        @if($errors->has('estoque'))
                                                                            <div class="invalid-feedback">
                                                                                {{ $errors->first('estoque') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="form-group validated col-sm-3 col-lg-2 estoque-matriz">
                                                                    <label class="col-form-label">Iniciar com Estoque</label>
                                                                    <div class="">
                                                                        <input data-mask="000000000,00" data-mask-reverse="true" type="text" id="estoque" class="form-control @if($errors->has('estoque')) is-invalid @endif" name="estoque"
                                                                               value="">
                                                                        @if($errors->has('estoque'))
                                                                            <div class="invalid-feedback">
                                                                                {{ $errors->first('estoque') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Peso</label>
                                                            <div class="">
                                                                <input data-mask="0000000,000" data-mask-reverse="true" type="tel" value="{{{ isset($produto->peso) ? $produto->peso : old('peso') }}}" id="peso" class="form-control @if($errors->has('peso')) is-invalid @endif" name="peso"
                                                                       value="">
                                                                @if($errors->has('peso'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('peso') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-4 col-lg-3">
                                                            <label class="col-form-label">Código de Barras EAN13</label>
                                                            <div class="input-group">
                                                                <input id="codBarras" type="text" class="form-control @if($errors->has('codBarras')) is-invalid @endif" name="codBarras" value="{{{ isset($produto->codBarras) ? $produto->codBarras : old('codBarras') }}}">
                                                                <div class="input-group-prepend">
																<span class="input-group-text btn-info btn" onclick="gerarCode()">
																	<i class="la la-barcode"></i>
																</span>
                                                                </div>
                                                                @if($errors->has('codBarras'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('codBarras') }}
                                                                    </div>
                                                                @endif

                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-6 col-lg-3">
                                                            <label class="col-form-label text-left col-lg-12 col-sm-12">Reajuste valor automatico</label>
                                                            <div class="col-6">
															<span class="switch switch-outline switch-danger">
																<label>
																	<input value="true" @if(isset($produto) && $produto->reajuste_automatico) checked @endif type="checkbox" name="reajuste_automatico" id="reajuste_automatico">
																	<span></span>
																</label>
															</span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-6 col-lg-3">
                                                            <label class="col-form-label text-left col-lg-12 col-sm-12">Gerenciar estoque</label>
                                                            <div class="col-6">
															<span class="switch switch-outline switch-primary">
																<label>
																	<input value="true" @if(isset($produto) && $produto->gerenciar_estoque) checked @elseif($config->gerenciar_estoque_produto == 1 && !isset($produto)) checked @endif type="checkbox" name="gerenciar_estoque" id="gerenciar_estoque">
																	<span></span>
																</label>
															</span>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-6 col-lg-2">
                                                            <label class="col-form-label text-left col-lg-12 col-sm-12">Inativo</label>
                                                            <div class="col-6">
															<span class="switch switch-outline switch-danger">
																<label>
																	<input value="true" @if(isset($produto) && $produto->inativo) checked @endif type="checkbox" name="inativo" id="inativo">
																	<span></span>
																</label>
															</span>
                                                            </div>
                                                        </div>

                                                        @if(!isset($tipo_grade))
                                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                                <label class="col-form-label text-left col-lg-12 col-sm-12">Tipo grade</label>
                                                                <div class="col-6">
															<span class="switch switch-outline switch-info">
																<label>
																	<input @if(old('grade')) checked @endif class="grade" type="checkbox" name="grade">
																	<span></span>
																</label>
															</span>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="form-group validated col-lg-3 col-md-6 col-sm-10">
                                                            <label class="col-form-label">Categoria <strong class="text-danger">*</strong></label>
                                                            <div class="input-group">

                                                                <select id="categoria" class="form-control custom-select @if($errors->has('categoria_id')) is-invalid @endif" name="categoria_id">
                                                                    <option value="">--</option>
                                                                    @foreach($categorias as $cat)
                                                                        <option value="{{$cat->id}}" @if(isset($produto)) @if($cat->id == $produto->categoria_id)
                                                                            selected=""
                                                                                @endif
                                                                                @else
                                                                                    @if($cat->id == old('categoria_id'))
                                                                                        selected
                                                                            @endif
                                                                            @endif >{{$cat->nome}}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="input-group-prepend">
																<span class="input-group-text btn-info btn" onclick="novaCategoria()">
																	<i class="la la-plus"></i>
																</span>
                                                                </div>
                                                                @if($errors->has('categoria_id'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('categoria_id') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-lg-3 col-md-6 col-sm-10">
                                                            <label class="col-form-label">Sub Categoria</label>
                                                            <div class="input-group">

                                                                <select id="sub_categoria_id" class="form-control custom-select" name="sub_categoria_id">
                                                                    <option value="">--</option>
                                                                </select>

                                                                <div class="input-group-prepend">
																<span class="input-group-text btn-warning btn" onclick="novaSubCategoria()">
																	<i class="la la-plus"></i>
																</span>
                                                                </div>

                                                                @if($errors->has('sub_categoria_id'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('sub_categoria_id') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-lg-3 col-md-6 col-sm-10">
                                                            <label class="col-form-label">Marca</label>
                                                            <div class="input-group">

                                                                <select id="marca" class="form-control custom-select" name="marca_id">
                                                                    <option value="">--</option>
                                                                    @foreach($marcas as $cat)
                                                                        <option value="{{$cat->id}}" @isset($produto) @if($cat->id == $produto->marca_id)
                                                                            selected=""
                                                                            @endif
                                                                            @endisset >{{$cat->nome}}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <div class="input-group-prepend">
																<span class="input-group-text btn-danger btn" onclick="novaMarca()">
																	<i class="la la-plus"></i>
																</span>
                                                                </div>
                                                                @if($errors->has('marca_id'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('marca_id') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Estoque minimo</label>
                                                            <div class="">
                                                                <input type="text" id="estoque_minimo" class="form-control @if($errors->has('estoque_minimo')) is-invalid @endif" name="estoque_minimo" value="{{{ isset($produto) ? $produto->estoque_minimo : old('estoque_minimo') }}}">
                                                                @if($errors->has('estoque_minimo'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('estoque_minimo') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-4 col-lg-3">
                                                            <label class="col-form-label">Limite maximo desconto %</label>
                                                            <div class="">
                                                                <input type="text" id="limite_maximo_desconto" class="form-control @if($errors->has('limite_maximo_desconto')) is-invalid @endif" name="limite_maximo_desconto" value="{{{ isset($produto) ? $produto->limite_maximo_desconto : old('limite_maximo_desconto') }}}">
                                                                @if($errors->has('limite_maximo_desconto'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('limite_maximo_desconto') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>


                                                        <div class="form-group validated col-sm-3 col-lg-3">
                                                            <label class="col-form-label">Alerta de Venc. (Dias)</label>
                                                            <div class="">
                                                                <input type="text" id="alerta_vencimento" class="form-control @if($errors->has('alerta_vencimento')) is-invalid @endif" name="alerta_vencimento" value="{{{ isset($produto) ? $produto->alerta_vencimento : old('alerta_vencimento') }}}">
                                                                @if($errors->has('alerta_vencimento'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('alerta_vencimento') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>


                                                        <div class="form-group validated col-lg-2 col-md-6 col-sm-10">
                                                            <label class="col-form-label">Unidade de compra <strong class="text-danger">*</strong></label>

                                                            <select class="custom-select form-control" id="unidade_compra" name="unidade_compra">
                                                                @foreach($unidadesDeMedida as $u)
                                                                    <option @if(isset($produto)) @if($u==$produto->unidade_compra)
                                                                        selected
                                                                            @endif
                                                                            @else
                                                                                @if($u == 'UN')
                                                                                    selected
                                                                            @endif
                                                                            @endif value="{{$u}}">{{$u}}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2" id="conversao" style="display: none">
                                                            <label class="col-form-label">Conversão Unitária</label>
                                                            <div class="">
                                                                <input type="text" id="alerta_vencimento" class="form-control @if($errors->has('conversao_unitaria')) is-invalid @endif" name="conversao_unitaria" value="{{{ isset($produto->conversao_unitaria) ? $produto->conversao_unitaria : old('conversao_unitaria') }}}">
                                                                @if($errors->has('conversao_unitaria'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('conversao_unitaria') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="form-group validated col-lg-2 col-md-6 col-sm-10">
                                                            <label class="col-form-label">Unidade de venda <strong class="text-danger">*</strong></label>

                                                            <select class="custom-select form-control" id="unidade_venda" name="unidade_venda">
                                                                @foreach($unidadesDeMedida as $u)
                                                                    <option @if(isset($produto)) @if($u==$produto->unidade_venda)
                                                                        selected
                                                                            @endif
                                                                            @else
                                                                                @if($u == 'UN')
                                                                                    selected
                                                                            @endif
                                                                            @endif value="{{$u}}">{{$u}}
                                                                    </option>
                                                                @endforeach
                                                            </select>

                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Un. tributável</label>

                                                            <select class="custom-select form-control" id="unidade_tributavel" name="unidade_tributavel">
                                                                <option value="">--</option>
                                                                @foreach($unidadesDeMedida as $u)
                                                                    <option @if(isset($produto)) @if($u == $produto->unidade_tributavel)
                                                                        selected
                                                                            @endif
                                                                            @else
                                                                            @endif value="{{$u}}">{{$u}}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Qtd. tributável</label>

                                                            <input type="text" id="quantidade_tributavel" class="form-control @if($errors->has('quantidade_tributavel')) is-invalid @endif" data-mask="00000,00" data-mask-reverse="true" name="quantidade_tributavel"
                                                                   value="{{{ isset($produto->quantidade_tributavel) ? $produto->quantidade_tributavel : old('quantidade_tributavel') }}}">
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">NCM <strong class="text-danger">*</strong></label>
                                                            <div class="">
                                                                <input type="text" id="ncm" class="form-control @if($errors->has('NCM')) is-invalid @endif" name="NCM" value="{{{ isset($produto->NCM) ? $produto->NCM : $tributacao->ncm_padrao }}}">
                                                                @if($errors->has('NCM'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('NCM') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">CEST</label>
                                                            <div class="">
                                                                <input type="text" id="cest" class="form-control @if($errors->has('CEST')) is-invalid @endif" name="CEST" value="{{{ isset($produto->CEST) ? $produto->CEST : old('CEST') }}}">
                                                                @if($errors->has('CEST'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('CEST') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
			
														<div class="row">
															<div class="form-group col-lg-3 col-md-4 col-sm-6">
																<label class="col-form-label">Tipo do Item (SPED)</label>
																<select class="custom-select form-control" name="tipo_item">
																	<option value="00" {{ (isset($produto) && $produto->tipo_item == '00') || old('tipo_item') == '00' ? 'selected' : '' }}>00 - Mercadoria para Revenda</option>
																	<option value="01" {{ (isset($produto) && $produto->tipo_item == '01') || old('tipo_item') == '01' ? 'selected' : '' }}>01 - Matéria-Prima</option>
																	<option value="02" {{ (isset($produto) && $produto->tipo_item == '02') || old('tipo_item') == '02' ? 'selected' : '' }}>02 - Embalagem</option>
																	<option value="03" {{ (isset($produto) && $produto->tipo_item == '03') || old('tipo_item') == '03' ? 'selected' : '' }}>03 - Produto em Processo</option>
																	<option value="04" {{ (isset($produto) && $produto->tipo_item == '04') || old('tipo_item') == '04' ? 'selected' : '' }}>04 - Produto Acabado</option>
																	<option value="05" {{ (isset($produto) && $produto->tipo_item == '05') || old('tipo_item') == '05' ? 'selected' : '' }}>05 - Subproduto</option>
																	<option value="06" {{ (isset($produto) && $produto->tipo_item == '06') || old('tipo_item') == '06' ? 'selected' : '' }}>06 - Produto Intermediário</option>
																	<option value="07" {{ (isset($produto) && $produto->tipo_item == '07') || old('tipo_item') == '07' ? 'selected' : '' }}>07 - Material de Uso e Consumo</option>
																	<option value="08" {{ (isset($produto) && $produto->tipo_item == '08') || old('tipo_item') == '08' ? 'selected' : '' }}>08 - Ativo Imobilizado</option>
																	<option value="09" {{ (isset($produto) && $produto->tipo_item == '09') || old('tipo_item') == '09' ? 'selected' : '' }}>09 - Serviços</option>
																	<option value="10" {{ (isset($produto) && $produto->tipo_item == '10') || old('tipo_item') == '10' ? 'selected' : '' }}>10 - Outros insumos</option>
																	<option value="99" {{ (isset($produto) && $produto->tipo_item == '99') || old('tipo_item') == '99' ? 'selected' : '' }}>99 - Outras</option>
																</select>
															</div>

															<div class="form-group col-lg-3 col-md-4 col-sm-6">
																<label class="col-form-label">Número CA</label>
																<input type="text" name="ca_numero" class="form-control" value="{{{ isset($produto) ? $produto->ca_numero : old('ca_numero') }}}">
															</div>

															<div class="form-group col-lg-6 col-md-4 col-sm-12">
																<label class="col-form-label">Fabricante</label>
																<input type="text" name="fabricante" class="form-control" value="{{{ isset($produto) ? $produto->fabricante : old('fabricante') }}}">
															</div>
														</div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Ref. balança</label>
                                                            <div class="">
                                                                <input type="text" id="referencia_balanca" class="form-control @if($errors->has('referencia_balanca')) is-invalid @endif" name="referencia_balanca" value="{{{ isset($produto->referencia_balanca) ? $produto->referencia_balanca : old('referencia_balanca') }}}">
                                                                @if($errors->has('referencia_balanca'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('referencia_balanca') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">% Comissão</label>
                                                            <div class="">
                                                                <input type="text" id="perc_comissao" class="form-control @if($errors->has('perc_comissao')) is-invalid @endif" name="perc_comissao" value="{{{ isset($produto->perc_comissao) ? $produto->perc_comissao : old('perc_comissao') }}}">
                                                                @if($errors->has('perc_comissao'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('perc_comissao') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Valor Comissão</label>
                                                            <div class="">
                                                                <input type="text" id="valor_comissao" class="form-control @if($errors->has('valor_comissao')) is-invalid @endif money" name="valor_comissao" value="{{{ isset($produto->valor_comissao) ? $produto->valor_comissao : old('valor_comissao') }}}">
                                                                @if($errors->has('valor_comissao'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('valor_comissao') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-3">
                                                            <label class="col-form-label">Tipo dimensão</label>
                                                            <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Para setar dimensão do produto na venda pedido"><i class="la la-info"></i></button>
                                                            <div class="">
                                                                <select id="tipo_dimensao" class="form-control custom-select" name="tipo_dimensao">
                                                                    <option value="">--</option>
                                                                    <option @isset($produto) @if($produto->tipo_dimensao == 'area') selected @endif @endif value="area">Area</option>
                                                                    <option @isset($produto) @if($produto->tipo_dimensao == 'dimensao') selected @endif @endif value="dimensao">Dimensão</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-2">
                                                            <label class="col-form-label">Custo assessor</label>
                                                            <div class="">
                                                                <input type="text" id="custo_assessor" class="form-control @if($errors->has('custo_assessor')) is-invalid @endif money" name="custo_assessor" value="{{{ isset($produto) ? number_format($produto->custo_assessor, $casasDecimais) : old('custo_assessor') }}}">
                                                                @if($errors->has('custo_assessor'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('custo_assessor') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-3">
                                                            <label class="col-form-label">Envia controle de pedidos</label>
                                                            <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se marcado com sim, este item quanto adicionado em um pedido será listado na tela de controle de pedidos"><i class="la la-info"></i></button>
                                                            <div class="">
                                                                <select id="envia_controle_pedidos" class="form-control custom-select" name="envia_controle_pedidos">
                                                                    <option @isset($produto) @if($produto->envia_controle_pedidos == 0) selected @endif @endif value="0">Não</option>
                                                                    <option @isset($produto) @if($produto->envia_controle_pedidos == 1) selected @endif @endif value="1">Sim</option>

                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-sm-3 col-lg-3">
                                                            <label class="col-form-label">Tipo serviço</label>
                                                            <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se marcado com sim, será listado na tela de finalizar OS completa"><i class="la la-info"></i></button>
                                                            <div class="">
                                                                <select id="tipo_servico" class="form-control custom-select" name="tipo_servico">
                                                                    <option @isset($produto) @if($produto->tipo_servico == 0) selected @endif @endif value="0">Não</option>
                                                                    <option @isset($produto) @if($produto->tipo_servico == 1) selected @endif @endif value="1">Sim</option>

                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-lg-3 col-md-6 col-sm-6">
                                                            <label class="col-form-label">Tela de Pedido (opcional)</label>
                                                            <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se selecionado alguma opção, o item aparecerá para tela selecionada | Cadastro em menu pedidos - tela de pedidos"><i class="la la-info"></i></button>
                                                            <select class="custom-select form-control" id="tela_pedido_id" name="tela_pedido_id">
                                                                <option value="0">--</option>
                                                                @foreach($telas as $t)
                                                                    <option value="{{$t->id}}" @isset($produto) @if($t->id == $produto->tela_pedido_id) selected @endif @endisset> {{$t->nome}}
                                                                    </option>
                                                                @endforeach
                                                            </select>

                                                        </div>

                                                        <div style="display: none;" class="form-group validated col-sm-3 col-lg-2 div_acres_perca">
                                                            <label class="col-form-label">Acréscimo perca</label>
                                                            <div class="">
                                                                <input type="text" id="acrescimo_perca" class="form-control @if($errors->has('acrescimo_perca')) is-invalid @endif" name="acrescimo_perca" value="{{{ isset($produto->acrescimo_perca) ? $produto->acrescimo_perca : old('acrescimo_perca') }}}">
                                                                @if($errors->has('acrescimo_perca'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('acrescimo_perca') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        @if(!isset($produto))
                                                            <div class="form-group validated col-lg-3">
                                                                <h3>Delivery
                                                                    <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se marcado produto ficará disponível no delivery"><i class="la la-info"></i></button>
                                                                </h3>

                                                                <span class="switch switch-outline switch-danger">
															<label>
																<input id="delivery" @if(isset($produto->delivery) && $produto->delivery) checked @endisset value="true" name="delivery" class="red-text" type="checkbox">
																<span></span>
															</label>
														</span>
                                                            </div>
                                                        @endif

                                                        <div class="col-12"></div>
                                                        <div class="form-group validated col-12 col-md-3">
                                                            <label class="col-xl-12 col-lg-12 col-form-label text-left">Imagem</label>
                                                            <div class="col-lg-12 col-xl-12">

                                                                <div class="image-input image-input-outline" id="kt_image_1">
                                                                    <div class="image-input-wrapper"
                                                                         @if(!isset($produto) || $produto->imagem == '') style="background-image: url(/imgs/no_image.png)" @else
                                                                             style="background-image: url(/imgs_produtos/{{$produto->imagem}})"
                                                                        @endif></div>
                                                                    <label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
                                                                        <i class="fa fa-pencil icon-sm text-muted"></i>
                                                                        <input type="file" name="file" accept=".png, .jpg, .jpeg">
                                                                        <input type="hidden" name="profile_avatar_remove">
                                                                    </label>
                                                                    <span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
																	<i class="fa fa-close icon-xs text-muted"></i>
																</span>
                                                                </div>

                                                                <span class="form-text text-muted">.png, .jpg, .jpeg</span>
                                                                @if($errors->has('file'))
                                                                    <div class="invalid-feedback">
                                                                        {{ $errors->first('file') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="col-12 col-md-9">

                                                            <div class="form-group validated">
                                                                <label class="col-form-label">Observação</label>
                                                                <textarea rows="5" class="form-control" name="observacao" id="observacao">@isset($produto){{$produto->observacao}}@else{{old('observacao')}}@endif</textarea>
                                                            </div>
                                                        </div>

                                                        <div class="form-group validated col-lg-12 col-md-12 col-sm-12">
                                                            <h3>Composto
                                                                <button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Produzido no estabelecimento composto de outros produtos já cadastrados, deverá ser criado uma composição/receita para redução de estoque."><i class="la la-info"></i></button>
                                                            </h3>

                                                            <span class="switch switch-outline switch-success">
															<label>
																<input id="composto" @if(isset($produto->composto) && $produto->composto) checked @endisset value="true" name="composto" class="red-text" type="checkbox">
																<span></span>
															</label>

														</span>
                                                        </div>

                                                        <div class="col-12 div_info_composto">
                                                            <div class="row">
                                                                <div class="form-group validated col-12">
                                                                    <label class="col-form-label">Informação tecnica</label>
                                                                    <textarea class="form-control" name="info_tecnica_composto" id="info_tecnica_composto">@isset($produto){{$produto->info_tecnica_composto}}@else{{old('info_tecnica_composto')}}@endif</textarea>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <hr>

                                                        <div class="form-group validated col-12">
                                                            <h3>Derivado Petróleo</h3>
                                                            <span class="switch switch-outline switch-info">
															<label>
																<input @isset($produto) @if($produto->codigo_anp != '') checked @endif @endisset @if(old('der_petroleo')) checked @endif
                                                                id="der_petroleo" name="der_petroleo" value="true" type="checkbox">
																<span></span>
															</label>
														</span>
                                                        </div>
                                                        <div class="col-12 div-petroleo" style="display: none;">
                                                            <div class="row">
                                                                <div class="form-group validated col-lg-6 col-md-10 col-sm-10">
                                                                    <label class="col-form-label">ANP</label>

                                                                    <select class="custom-select form-control" id="anp" name="anp">
                                                                        <option value="">--</option>
                                                                        @foreach($anps as $key => $a)
                                                                            <option value="{{$key}}" @isset($produto->codigo_anp)
                                                                                @if($key == $produto->codigo_anp)
                                                                                    selected=""
                                                                                @endif
                                                                                @endisset >[{{$key}}] - {{$a}}
                                                                            </option>

                                                                        @endforeach
                                                                    </select>
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">%GLP</label>

                                                                    <input type="text" id="perc_glp" class="form-control @if($errors->has('perc_glp')) is-invalid @endif trib" name="perc_glp"
                                                                           value="{{{ isset($produto->perc_glp) ? $produto->perc_glp : old('perc_glp') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">%GNn</label>

                                                                    <input type="text" id="perc_gnn" class="form-control @if($errors->has('perc_gnn')) is-invalid @endif trib" name="perc_gnn"
                                                                           value="{{{ isset($produto->perc_gnn) ? $produto->perc_gnn : old('perc_gnn') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">%GNi</label>

                                                                    <input type="text" id="perc_gni" class="form-control @if($errors->has('perc_gni')) is-invalid @endif trib" name="perc_gni"
                                                                           value="{{{ isset($produto->perc_gni) ? $produto->perc_gni : old('perc_gni') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Valor de partida</label>

                                                                    <input type="text" id="valor_partida" class="form-control @if($errors->has('valor_partida')) is-invalid @endif money" name="valor_partida"
                                                                           value="{{{ isset($produto->valor_partida) ? $produto->valor_partida : old('valor_partida') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Alíquota ad rem do imposto retido</label>

                                                                    <input type="text" id="adRemICMSRet" class="form-control @if($errors->has('adRemICMSRet')) is-invalid @endif" data-mask="00,0000" data-mask-reverse="true" name="adRemICMSRet"
                                                                           value="{{{ isset($produto->adRemICMSRet) ? $produto->adRemICMSRet : old('adRemICMSRet') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Indice de mistura do Biodiesel</label>

                                                                    <input type="text" id="pBio" class="form-control @if($errors->has('pBio')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="pBio"
                                                                           value="{{{ isset($produto->pBio) ? $produto->pBio : old('pBio') }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">% de origem</label>

                                                                    <input type="text" id="pOrig" class="form-control @if($errors->has('pOrig')) is-invalid @endif" data-mask="000,00" data-mask-reverse="true" name="pOrig"
                                                                           value="{{{ isset($produto) ? $produto->pOrig : '100,00' }}}">
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-10 col-sm-10">
                                                                    <label class="col-form-label">Indicador de importação</label>

                                                                    <select class="custom-select form-control" id="indImport" name="indImport">
                                                                        <option @isset($produto) @if($produto->indImport == 0) seleceted @endif @endif value="0">Não</option>
                                                                        <option @isset($produto) @if($produto->indImport == 1) seleceted @endif @endif value="1">Sim</option>

                                                                    </select>
                                                                </div>

                                                                <div class="form-group validated col-lg-4 col-md-10 col-sm-10">
                                                                    <label class="col-form-label">UF de origem do produtor ou do importador</label>

                                                                    <select class="custom-select form-control" id="cUFOrig" name="cUFOrig">
                                                                        <option value="">--</option>
                                                                        @foreach(App\Models\Produto::getUFs() as $key => $uf)
                                                                            <option value="{{$key}}" @isset($produto)
                                                                                @if($key == $produto->cUFOrig)
                                                                                    selected
                                                                                @endif
                                                                                @endisset>{{$uf}}
                                                                            </option>

                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <!-- <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
															<label class="col-form-label">Un. tributável</label>

															<input type="text" id="unidade_tributavel" class="form-control @if($errors->has('unidade_tributavel')) is-invalid @endif" data-mask="AAAA" name="unidade_tributavel"
															value="{{{ isset($produto->unidade_tributavel) ? $produto->unidade_tributavel : old('unidade_tributavel') }}}">
														</div> -->

                                                                <!-- <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
															<label class="col-form-label">Qtd. tributável</label>

															<input type="text" id="quantidade_tributavel" class="form-control @if($errors->has('quantidade_tributavel')) is-invalid @endif" data-mask="00000,00" data-mask-reverse="true" name="quantidade_tributavel"
															value="{{{ isset($produto->quantidade_tributavel) ? $produto->quantidade_tributavel : old('quantidade_tributavel') }}}">
														</div> -->
                                                            </div>
                                                        </div>


                                                        <hr>
                                                        <div class="form-group validated col-12">
                                                            <h3>Dados de dimensão e peso do produto (Opcional)</h3>
                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Largura (cm)</label>

                                                            <input type="text" id="largura" class="form-control @if($errors->has('largura')) is-invalid @endif" name="largura"
                                                                   value="{{{ isset($produto->largura) ? $produto->largura : old('largura') }}}">

                                                            @if($errors->has('largura'))
                                                                <div class="invalid-feedback">
                                                                    {{ $errors->first('largura') }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Altura (cm)</label>

                                                            <input type="text" id="altura" class="form-control @if($errors->has('altura')) is-invalid @endif" name="altura"
                                                                   value="{{{ isset($produto->altura) ? $produto->altura : old('altura') }}}">
                                                            @if($errors->has('altura'))
                                                                <div class="invalid-feedback">
                                                                    {{ $errors->first('altura') }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Comprimento (cm)</label>

                                                            <input type="text" id="comprimento" class="form-control @if($errors->has('comprimento')) is-invalid @endif" name="comprimento" value="{{{ isset($produto->comprimento) ? $produto->comprimento : old('comprimento') }}}">
                                                            @if($errors->has('comprimento'))
                                                                <div class="invalid-feedback">
                                                                    {{ $errors->first('comprimento') }}
                                                                </div>
                                                            @endif
                                                        </div>


                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Peso liquido</label>

                                                            <input type="text" id="peso_liquido" class="form-control @if($errors->has('peso_liquido')) is-invalid @endif" name="peso_liquido"
                                                                   value="{{{ isset($produto->peso_liquido) ? $produto->peso_liquido : old('peso_liquido') }}}">
                                                            @if($errors->has('peso_liquido'))
                                                                <div class="invalid-feedback">
                                                                    {{ $errors->first('peso_liquido') }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                            <label class="col-form-label">Peso bruto</label>

                                                            <input type="text" id="peso_bruto" class="form-control @if($errors->has('peso_bruto')) is-invalid @endif" name="peso_bruto"
                                                                   value="{{{ isset($produto->peso_bruto) ? $produto->peso_bruto : old('peso_bruto') }}}">
                                                            @if($errors->has('peso_bruto'))
                                                                <div class="invalid-feedback">
                                                                    {{ $errors->first('peso_bruto') }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        @if(env("ECOMMERCE") == 1)
                                                            <div class="form-group validated col-sm-6 col-lg-3">
                                                                <label class="col-form-label text-left">Ecommerce</label>

                                                                <span class="switch switch-outline switch-danger">
														<label>
															<input @if(old('ecommerce')) checked @endif class="ecommerce" type="checkbox" name="ecommerce" @isset($produto) @if($produto->ecommerce) checked @endif @endif>
															<span></span>
														</label>
													</span>

                                                            </div>
                                                        @endif

                                                        @if(env("LOCACAO") == 1)
                                                            <div class="form-group validated col-sm-6 col-lg-2">
                                                                <label class="col-form-label text-left col-lg-12 col-sm-12">Locação</label>
                                                                <div class="col-6">
														<span class="switch switch-outline switch-info">
															<label>
																<input @if(old('locacao') || (isset($produto) && $produto->valor_locacao > 0)) checked @endif class="locacao" type="checkbox" name="locacao">
																<span></span>
															</label>
														</span>
                                                                </div>
                                                            </div>

                                                            <div class="form-group validated col-sm-4 col-lg-3 div-loc" @if(!isset($produto)) style="display: none;" @endif @if(isset($produto) && $produto->valor_locacao == 0) style="display: none;" @endif>
                                                                <label class="col-form-label">Valor locação</label>
                                                                <div class="">
                                                                    <input type="text" class="form-control @if($errors->has('valor_locacao')) is-invalid @endif money" name="valor_locacao" id="valor_locacao" value="{{{ isset($produto->valor_locacao) ? number_format($produto->valor_locacao, $casasDecimais) : old('valor_locacao') }}}">
                                                                    @if($errors->has('valor_locacao'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('valor_locacao') }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="col-lg-12 col-xl-12">
                                                            <p class="text-danger">*Se atente a preencher todos os dados para utilizar a Api dos correios.</p>
                                                        </div>

                                                        @php
                                                            $ecommerce = isset($produto) ? $produto->ecommerce : null;
                                                        @endphp
                                                        <div class="col-12 div-ecommmerce" style="display: none;">
                                                            <div class="row">
                                                                <div class="form-group validated col-12">
                                                                    <h3>Dados de Ecommerce</h3>
                                                                </div>
                                                                <div class="form-group validated col-lg-4 col-md-4 col-sm-10">
                                                                    <label class="col-form-label ">Categoria</label>

                                                                    <select id="categoria-select" class="custom-select form-control @if($errors->has('categoria_ecommerce_id')) is-invalid @endif" name="categoria_ecommerce_id">
                                                                        <option value="">Selecione a categoria</option>
                                                                        @foreach($categoriasEcommerce as $c)
                                                                            <option
                                                                                @if(old('categoria_ecommerce_id') == $c->id) selected @endif
                                                                            value="{{$c->id}}" @if($ecommerce != null && $ecommerce->categoria_id == $c->id) selected @endif>{{$c->nome}}</option>
                                                                        @endforeach
                                                                    </select>

                                                                    @if($errors->has('categoria_ecommerce_id'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('categoria_ecommerce_id') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-4 col-md-6 col-sm-10">
                                                                    <label class="col-form-label">Sub Categoria</label>
                                                                    <div class="input-group">

                                                                        <select id="sub_categoria_ecommerce_id" class="form-control custom-select" name="sub_categoria_ecommerce_id">
                                                                            <option value="">--</option>
                                                                        </select>

                                                                        @if($errors->has('sub_categoria_ecommerce_id'))
                                                                            <div class="invalid-feedback">
                                                                                {{ $errors->first('sub_categoria_ecommerce_id') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="form-group validated col-sm-4 col-lg-3">
                                                                    <label class="col-form-label">Valor</label>
                                                                    <div class="">
                                                                        <input type="text" class="form-control @if($errors->has('valor_ecommerce')) is-invalid @endif money" name="valor_ecommerce" id="valor_ecommerce" value="{{ $ecommerce != null ? $ecommerce->valor : old('valor_ecommerce') }}">
                                                                        @if($errors->has('valor_ecommerce'))
                                                                            <div class="invalid-feedback">
                                                                                {{ $errors->first('valor_ecommerce') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="col col-sm-3 col-lg-3">
                                                                    <br>
                                                                    <label>Controlar estoque:</label>

                                                                    <div class="switch switch-outline switch-success">
                                                                        <label class="">
                                                                            <input value="true" name="controlar_estoque" class="red-text" @if(old('controlar_estoque')) checked @endif type="checkbox" @if($ecommerce != null && $ecommerce->controlar_estoque) checked @endif>
                                                                            <span class="lever"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <div class="col col-sm-3 col-lg-3">
                                                                    <br>
                                                                    <label>Ativo:</label>

                                                                    <div class="switch switch-outline switch-info">
                                                                        <label class="">
                                                                            <input value="true" name="status" @if(old('status')) checked @endif class="red-text" type="checkbox" @if($ecommerce != null && $ecommerce->status) checked @endif>
                                                                            <span class="lever"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <div class="col col-sm-3 col-lg-3">
                                                                    <br>
                                                                    <label>Destaque:</label>

                                                                    <div class="switch switch-outline switch-primary">
                                                                        <label class="">
                                                                            <input value="true" name="destaque" @if(old('destaque')) checked @endif class="red-text" type="checkbox" @if($ecommerce != null && $ecommerce->destaque) checked @endif>
                                                                            <span class="lever"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group validated col-sm-12 col-lg-12">
                                                                    <label class="col-form-label">Descrição</label>
                                                                    <div class="">

                                                                        <textarea name="descricao" id="descricao" style="width: 100%;height:400px;">{{ $ecommerce != null ? $ecommerce->descricao : old('descricao')}}</textarea>

                                                                        @if($errors->has('descricao'))
                                                                            <div class="invalid-feedback">
                                                                                {{ $errors->first('descricao') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <hr>
                                                        <div class="form-group validated col-12">
                                                            <h3>Lote e Vencimento (Opcional)</h3>

                                                            <span class="switch switch-outline switch-info">
														<label>
															<input @isset($produto) @if($produto->lote != '') checked @endif @endisset @if(old('lote')) checked @endif
                                                            id="lote_venc" value="true" class="red-text" type="checkbox">
															<span></span>
														</label>
													</span>
                                                        </div>

                                                        <div class="div_lote col-12" style="display: none">
                                                            <div class="row">
                                                                <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Lote</label>

                                                                    <input type="text" id="lote" class="form-control @if($errors->has('lote')) is-invalid @endif" name="lote"
                                                                           value="{{{ isset($produto->lote) ? $produto->lote : old('lote') }}}">
                                                                    @if($errors->has('lote'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('lote') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-2 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Vencimento</label>

                                                                    <input type="text" id="kt_datepicker_3" class="form-control @if($errors->has('vencimento')) is-invalid @endif" data-mask="00/00/0000" data-mask-reverse="true" name="vencimento"
                                                                           value="{{{ isset($produto->vencimento) ? $produto->vencimento : old('vencimento') }}}">
                                                                    @if($errors->has('vencimento'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('vencimento') }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <div class="form-group validated col-12">
                                                            <h3>Dados Veiculo (Opcional)</h3>

                                                            <span class="switch switch-outline switch-info">
														<label>
															<input @isset($produto) @if($produto->renavam != '') checked @endif @endisset id="tp_veiculo" value="true" class="red-text" type="checkbox">
															<span></span>
														</label>
													</span>
                                                        </div>

                                                        <div class="div_veiculo col-12" style="display: none">
                                                            <div class="row">
                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Renavam</label>

                                                                    <input type="text" id="renavam" class="form-control @if($errors->has('renavam')) is-invalid @endif" name="renavam"
                                                                           value="{{{ isset($produto->renavam) ? $produto->renavam : old('renavam') }}}">
                                                                    @if($errors->has('renavam'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('renavam') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Placa</label>

                                                                    <input type="text" id="placa" class="form-control @if($errors->has('placa')) is-invalid @endif" name="placa"
                                                                           value="{{{ isset($produto->placa) ? $produto->placa : old('placa') }}}">
                                                                    @if($errors->has('placa'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('placa') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Chassi</label>

                                                                    <input type="text" id="chassi" class="form-control @if($errors->has('chassi')) is-invalid @endif" name="chassi"
                                                                           value="{{{ isset($produto->chassi) ? $produto->chassi : old('chassi') }}}">
                                                                    @if($errors->has('chassi'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('chassi') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Combustível</label>

                                                                    <input type="text" id="combustivel" class="form-control @if($errors->has('combustivel')) is-invalid @endif" name="combustivel"
                                                                           value="{{{ isset($produto->combustivel) ? $produto->combustivel : old('combustivel') }}}">
                                                                    @if($errors->has('combustivel'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('combustivel') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Ano/Modelo</label>

                                                                    <input type="text" id="ano_modelo" class="form-control @if($errors->has('ano_modelo')) is-invalid @endif" name="ano_modelo"
                                                                           value="{{{ isset($produto->ano_modelo) ? $produto->ano_modelo : old('ano_modelo') }}}">
                                                                    @if($errors->has('ano_modelo'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('ano_modelo') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="form-group validated col-lg-3 col-md-4 col-sm-4">
                                                                    <label class="col-form-label">Cor</label>

                                                                    <input type="text" id="cor_veiculo" class="form-control @if($errors->has('cor_veiculo')) is-invalid @endif" name="cor_veiculo"
                                                                           value="{{{ isset($produto->cor_veiculo) ? $produto->cor_veiculo : old('cor_veiculo') }}}">
                                                                    @if($errors->has('cor_veiculo'))
                                                                        <div class="invalid-feedback">
                                                                            {{ $errors->first('cor_veiculo') }}
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                            </div>

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
                                                        <strong class="text-danger">*</strong></label>

                                                    <select class="custom-select form-control" id="CST_CSOSN" name="CST_CSOSN">
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
                                                    <label class="col-form-label">CST PIS <strong class="text-danger">*</strong></label>

                                                    <select class="custom-select form-control" id="CST_CSOSN" name="CST_PIS">
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
                                                    <label class="col-form-label">CST COFINS <strong class="text-danger">*</strong></label>

                                                    <select class="custom-select form-control" id="CST_CSOSN" name="CST_COFINS">
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

                                                <div class="form-group validated col-lg-6 col-md-10 col-sm-10">
                                                    <label class="col-form-label">CST IPI <strong class="text-danger">*</strong></label>

                                                    <select class="custom-select form-control" id="CST_IPI" name="CST_IPI">
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

                                                <div class="form-group validated col-lg-6 col-md-10 col-sm-10">
                                                    <label class="col-form-label">
                                                        @if($tributacao->regime == 1)
                                                            CST Exportação
                                                        @else
                                                            CSOSN Exportação
                                                        @endif</label>

                                                    <select class="custom-select form-control" id="CST_CSOSN_EXP" name="CST_CSOSN_EXP">
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

                                                <div class="form-group validated col-12">
                                                    <label class="col-form-label">Código de enquandramento de IPI <strong class="text-danger">*</strong></label>

                                                    <select class="custom-select form-control" id="cenq_ipi" name="cenq_ipi">
                                                        @foreach(App\Models\Produto::listaCenqIPI() as $key => $c)
                                                            <option value="{{$key}}" @if($config !=null) @if(isset($produto)) @if($key==$produto->cenq_ipi)
                                                                selected
                                                                    @endif
                                                                    @else
                                                                        @if($key == '999')
                                                                            selected
                                                                @endif
                                                                @endif

                                                                @endif
                                                            >{{$c}}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-sm-4 col-lg-2">
                                                    <label class="col-form-label">CFOP saida interno <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="CFOP_saida_estadual" class="form-control @if($errors->has('CFOP_saida_estadual')) is-invalid @endif" name="CFOP_saida_estadual"
                                                               value="{{{ isset($produto->CFOP_saida_estadual) ? $produto->CFOP_saida_estadual : $natureza->CFOP_saida_estadual }}}">
                                                        @if($errors->has('CFOP_saida_estadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('CFOP_saida_estadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-4 col-lg-2">
                                                    <label class="col-form-label">CFOP saida externo <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="CFOP_saida_inter_estadual" class="form-control @if($errors->has('CFOP_saida_inter_estadual')) is-invalid @endif" name="CFOP_saida_inter_estadual"
                                                               value="{{{ isset($produto->CFOP_saida_inter_estadual) ? $produto->CFOP_saida_inter_estadual : $natureza->CFOP_saida_inter_estadual }}}">
                                                        @if($errors->has('CFOP_saida_inter_estadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('CFOP_saida_inter_estadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%ICMS <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="perc_icms" class="form-control trib @if($errors->has('perc_icms')) is-invalid @endif" name="perc_icms"
                                                               value="{{{ isset($produto->perc_icms) ? $produto->perc_icms : $tributacao->icms }}}">
                                                        @if($errors->has('perc_icms'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_icms') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%PIS <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="perc_pis" class="form-control trib @if($errors->has('perc_pis')) is-invalid @endif" name="perc_pis"
                                                               value="{{{ isset($produto->perc_pis) ? $produto->perc_pis : $tributacao->pis }}}">
                                                        @if($errors->has('perc_pis'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_pis') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%COFINS <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="perc_cofins" class="form-control trib @if($errors->has('perc_cofins')) is-invalid @endif" name="perc_cofins"
                                                               value="{{{ isset($produto->perc_cofins) ? $produto->perc_cofins : $tributacao->cofins }}}">
                                                        @if($errors->has('perc_cofins'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_cofins') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%IPI <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="perc_ipi" class="form-control trib @if($errors->has('perc_ipi')) is-invalid @endif" name="perc_ipi"
                                                               value="{{{ isset($produto->perc_ipi) ? $produto->perc_ipi : $tributacao->ipi }}}">
                                                        @if($errors->has('perc_ipi'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_ipi') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%ISS<strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="perc_iss" class="form-control trib @if($errors->has('perc_iss')) is-invalid @endif" name="perc_iss"
                                                               value="{{{ isset($produto->perc_iss) ? $produto->perc_iss : 0.00 }}}">
                                                        @if($errors->has('perc_iss'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_iss') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%Redução BC <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="pRedBC" class="form-control @if($errors->has('pRedBC')) is-invalid @endif perc" name="pRedBC"
                                                               value="{{{ isset($produto->pRedBC) ? $produto->pRedBC : 0.00 }}}">
                                                        @if($errors->has('pRedBC'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('pRedBC') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%ICMS ST <strong class="text-danger">*</strong></label>
                                                    <div class="">
                                                        <input type="text" id="pICMSST" class="form-control @if($errors->has('pICMSST')) is-invalid @endif" name="pICMSST"
                                                               value="{{{ isset($produto->pICMSST) ? $produto->pICMSST : 0.00 }}}">
                                                        @if($errors->has('pICMSST'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('pICMSST') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">Cod. benefício</label>
                                                    <div class="">
                                                        <input type="text" id="cBenef" class="form-control @if($errors->has('cBenef')) is-invalid @endif" name="cBenef"
                                                               value="{{{ isset($produto->cBenef) ? $produto->cBenef : $config->cBenef_padrao }}}">
                                                        @if($errors->has('cBenef'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('cBenef') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-6 col-lg-4">
                                                    <label class="col-form-label">Origem</label>
                                                    <div class="">
                                                        <select name="origem" class="custom-select">
                                                            @foreach(App\Models\Produto::origens() as $key => $o)
                                                                <option value="{{$key}}"
                                                                        @if(isset($produto)) @if($key == $produto->origem)
                                                                            selected=""
                                                                        @endif
                                                                        @else
                                                                            @if($key == old('origem'))
                                                                                selected
                                                                    @endif
                                                                    @endif
                                                                >{{$key}} - {{$o}}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($errors->has('origem'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('origem') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%ICMS interestadual</label>
                                                    <div class="">
                                                        <input type="text" id="" class="form-control @if($errors->has('perc_icms_interestadual')) is-invalid @endif trib" name="perc_icms_interestadual"
                                                               value="{{{ isset($produto->perc_icms_interestadual) ? $produto->perc_icms_interestadual : old('perc_icms_interestadual') }}}">
                                                        @if($errors->has('perc_icms_interestadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_icms_interestadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%ICMS interno</label>
                                                    <div class="">
                                                        <input type="text" id="perc_icms_interno" class="form-control @if($errors->has('perc_icms_interno')) is-invalid @endif trib" name="perc_icms_interno"
                                                               value="{{{ isset($produto->perc_icms_interno) ? $produto->perc_icms_interno : old('perc_icms_interno') }}}">
                                                        @if($errors->has('perc_icms_interno'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_icms_interno') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%FCP interestadual</label>
                                                    <div class="">
                                                        <input type="text" id="perc_fcp_interestadual" class="form-control @if($errors->has('perc_fcp_interestadual')) is-invalid @endif trib" name="perc_fcp_interestadual"
                                                               value="{{{ isset($produto->perc_fcp_interestadual) ? $produto->perc_fcp_interestadual : old('perc_fcp_interestadual') }}}">
                                                        @if($errors->has('perc_fcp_interestadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_fcp_interestadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-4 col-lg-2">
                                                    <label class="col-form-label">CFOP entrada interno</label>
                                                    <div class="">
                                                        <input type="text" id="CFOP_entrada_estadual" class="form-control @if($errors->has('CFOP_entrada_estadual')) is-invalid @endif" name="CFOP_entrada_estadual"
                                                               value="{{{ isset($produto->CFOP_entrada_estadual) ? $produto->CFOP_entrada_estadual : $natureza->CFOP_entrada_estadual }}}">
                                                        @if($errors->has('CFOP_entrada_estadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('CFOP_entrada_estadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="form-group validated col-sm-4 col-lg-2">
                                                    <label class="col-form-label">CFOP entrada externo</label>
                                                    <div class="">
                                                        <input type="text" id="CFOP_entrada_inter_estadual" class="form-control @if($errors->has('CFOP_entrada_inter_estadual')) is-invalid @endif" name="CFOP_entrada_inter_estadual"
                                                               value="{{{ isset($produto->CFOP_entrada_inter_estadual) ? $produto->CFOP_entrada_inter_estadual : $natureza->CFOP_entrada_inter_estadual }}}">
                                                        @if($errors->has('CFOP_entrada_inter_estadual'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('CFOP_entrada_inter_estadual') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-6 col-lg-4">
                                                    <label class="col-form-label">Modalidade Det.</label>
                                                    <div class="">
                                                        <select name="modBC" class="custom-select">
                                                            @foreach(App\Models\Produto::modalidadesDeterminacao() as $key => $o)
                                                                <option value="{{$key}}"
                                                                        @if(isset($produto)) @if($key == $produto->modBC)
                                                                            selected=""
                                                                        @endif
                                                                        @else
                                                                            @if($key == old('modBC'))
                                                                                selected
                                                                    @endif
                                                                    @endif
                                                                >{{$key}} - {{$o}}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($errors->has('modBC'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('modBC') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-6 col-lg-4">
                                                    <label class="col-form-label">Modalidade Det. ST</label>
                                                    <div class="">
                                                        <select name="modBCST" class="custom-select">
                                                            @foreach(App\Models\Produto::modalidadesDeterminacaoST() as $key => $o)
                                                                <option value="{{$key}}"
                                                                        @if(isset($produto)) @if($key == $produto->modBCST)
                                                                            selected=""
                                                                        @endif
                                                                        @else
                                                                            @if($key == old('modBCST'))
                                                                                selected
                                                                    @endif
                                                                    @endif
                                                                >{{$key}} - {{$o}}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($errors->has('modBCST'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('modBCST') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-lg-12 col-md-10 col-sm-10">
                                                    <label class="col-form-label">
                                                        @if($tributacao->regime == 1)
                                                            CST entrada
                                                        @else
                                                            CSOSN entrada
                                                        @endif <strong class="text-danger">*</strong></label>

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
                                                    <label class="col-form-label">CST PIS entrada <strong class="text-danger">*</strong></label>

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
                                                    <label class="col-form-label">CST COFINS entrada <strong class="text-danger">*</strong></label>

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

                                                <div class="form-group validated col-lg-4 col-md-10 col-sm-10">
                                                    <label class="col-form-label">CST IPI entrada <strong class="text-danger">*</strong></label>

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

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%Custo Frete</label>
                                                    <div class="">
                                                        <input type="text" id="perc_frete" class="form-control @if($errors->has('perc_frete')) is-invalid @endif trib" name="perc_frete"
                                                               value="{{{ isset($produto->perc_frete) ? $produto->perc_frete : old('perc_frete') }}}">
                                                        @if($errors->has('perc_frete'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_frete') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%Outras Despesas</label>
                                                    <div class="">
                                                        <input type="text" id="perc_outros" class="form-control @if($errors->has('perc_outros')) is-invalid @endif trib" name="perc_outros"
                                                               value="{{{ isset($produto->perc_outros) ? $produto->perc_outros : old('perc_outros') }}}">
                                                        @if($errors->has('perc_outros'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_outros') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%MLV</label>
                                                    <div class="">
                                                        <input type="text" id="perc_mlv" class="form-control @if($errors->has('perc_mlv')) is-invalid @endif trib" name="perc_mlv"
                                                               value="{{{ isset($produto->perc_mlv) ? $produto->perc_mlv : old('perc_mlv') }}}">
                                                        @if($errors->has('perc_mlv'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_mlv') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-sm-3 col-lg-2">
                                                    <label class="col-form-label">%MVA</label>
                                                    <div class="">
                                                        <input type="text" id="perc_mva" class="form-control @if($errors->has('perc_mva')) is-invalid @endif trib" name="perc_mva"
                                                               value="{{{ isset($produto->perc_mva) ? $produto->perc_mva : old('perc_mva') }}}">
                                                        @if($errors->has('perc_mva'))
                                                            <div class="invalid-feedback">
                                                                {{ $errors->first('perc_mva') }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-12 col-lg-12">
                                                    <label class="col-form-label">Informação adicional do item</label>
                                                    <div class="">

                                                        <input type="text" class="form-control @if($errors->has('info_adicional_item')) is-invalid @endif" name="info_adicional_item"
                                                               value="{{{ isset($produto->info_adicional_item) ? $produto->info_adicional_item : old('info_adicional_item') }}}">
                                                    </div>
                                                </div>

                                                <div class="row mt-2">
                                                    <div class="col-md-4">
                                                        <label class="form-label">CST IBS/CBS</label>
                                                        <select name="cst_ibs_cbs" id="cst_ibs_cbs" class="form-control">
                                                            @if(isset($produto) && !empty($produto->cst_ibs_cbs))
                                                                <option value="{{ $produto->cst_ibs_cbs }}" selected>
                                                                    {{ $produto->cst_ibs_cbs }}
                                                                </option>
                                                            @endif
                                                        </select>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label class="form-label">Class. Trib. IBS/CBS</label>
                                                        <select name="class_trib_ibs_cbs" id="class_trib_ibs_cbs" class="form-control">
                                                            @if(isset($produto) && !empty($produto->class_trib_ibs_cbs))
                                                                <option value="{{ $produto->class_trib_ibs_cbs }}" selected>
                                                                    {{ $produto->class_trib_ibs_cbs }}
                                                                </option>
                                                            @endif
                                                        </select>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <label class="form-label">Redução IBS (%)</label>
                                                        <input type="text"
                                                               name="reducao_ibs"
                                                               id="reducao_ibs"
                                                               class="form-control"
                                                               value="{{ old('reducao_ibs', $produto->reducao_ibs ?? 0) }}"
                                                               readonly>
                                                    </div>

                                                    <div class="col-md-2">
                                                        <label class="form-label">Redução CBS (%)</label>
                                                        <input type="text"
                                                               name="reducao_cbs"
                                                               id="reducao_cbs"
                                                               class="form-control"
                                                               value="{{ old('reducao_cbs', $produto->reducao_cbs ?? 0) }}"
                                                               readonly>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- ==============================
                                     Conteúdo da etapa “CONTROLE DE PESAGENS”
                                     ============================== --}}
                                <div data-wizard-type="step-content">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <h4>Parâmetros – Controle de Pesagens</h4>
                                        </div>

                                        {{-- Controla Pesagem --}}
                                        <div class="form-group validated col-sm-6 col-lg-3">
                                            <label class="col-form-label text-left col-lg-12 col-sm-12">
                                                Controla Pesagem
                                            </label>
                                            <div class="col-6">
                                                <span class="switch switch-outline switch-info">
                                                    <label>
                                                        <input
                                                            value="true"
                                                            @if(isset($produto) && $produto->controla_pesagem) checked @endif
                                                            type="checkbox"
                                                            name="controla_pesagem"
                                                            id="controla_pesagem"
                                                        >
                                                        <span></span>
                                                    </label>
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Produto Referenciado (Select2 AJAX) --}}
                                        <div class="form-group validated col-lg-4 col-md-6 col-sm-10">
                                            <label class="col-form-label">Produto Referenciado</label>
                                            <select
                                                id="produto_referenciado_id"
                                                class="form-control"
                                                name="produto_referenciado_id"
                                                style="width: 100%;"
                                            >
                                                @isset($produto)
                                                    @if($produto->produto_referenciado_id)
                                                        <option
                                                            value="{{ $produto->produto_referenciado_id }}"
                                                            selected
                                                        >
                                                            {{ $produto->produtoReferenciado->nome ?? '– sem título –' }}
                                                        </option>
                                                    @endif
                                                @endisset
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                {{-- ============================== --}}

                                {{-- ============================================================== --}}
                                {{-- <<< NOVO PASSO: Endereçamento de Estoque / Prateleira >>>      --}}
                                {{-- data-wizard-type="step-content" indica ao plugin que é um step --}}
                                {{-- ============================================================== --}}
                                <div class="pb-5" data-wizard-type="step-content" data-wizard-state="pending">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <h4>Endereçamento de Estoque / Prateleira</h4>
                                        </div>

                                        {{-- 1) Seletor (Select2) de prateleiras --}}
                                        <div class="form-group col-lg-6 col-md-8 col-sm-12">
                                            <label for="produto_prateleira_id">Selecione a Prateleira</label>
                                            <select id="produto_prateleira_id" name="produto_prateleira_id" class="form-control" style="width: 100%;">
                                                {{-- Se já houver valor “antigo” (editar), exibe a opção selecionada: --}}
                                                @isset($produto)
                                                    @if($produto->produto_prateleira_id)
                                                        <option value="{{ $produto->produto_prateleira_id }}" selected>
                                                            {{ $produto->prateleira->identificacao ?? '– Prateleira selecionada –' }}
                                                        </option>
                                                    @endif
                                                @endisset
                                            </select>
                                        </div>

                                        {{-- 2) Container para exibir dados da prateleira selecionada --}}
                                        <div id="prateleira_detalhes" class="col-12" style="display: none; margin-top: 1rem;">
                                            <div class="card card-custom gutter-b">
                                                <div class="card-header">
                                                    <h5 class="card-title">
                                                        Detalhes da Prateleira
                                                        <small class="text-muted" id="titulo_prateleira"></small>
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        {{-- Identificação --}}
                                                        <div class="col-sm-6">
                                                            <strong>Identificação:</strong>
                                                            <p id="det_identificacao">–</p>
                                                        </div>
                                                        {{-- Descrição --}}
                                                        <div class="col-sm-6">
                                                            <strong>Descrição:</strong>
                                                            <p id="det_descricao">–</p>
                                                        </div>
                                                        {{-- Posição --}}
                                                        <div class="col-sm-4">
                                                            <strong>Posição:</strong>
                                                            <p id="det_posicao">–</p>
                                                        </div>
                                                        {{-- Localização --}}
                                                        <div class="col-sm-4">
                                                            <strong>Localização:</strong>
                                                            <p id="det_localizacao">–</p>
                                                        </div>
                                                        {{-- Observação --}}
                                                        <div class="col-sm-4">
                                                            <strong>Observação:</strong>
                                                            <p id="det_observacao">–</p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        {{-- Data de criação --}}
                                                        <div class="col-sm-6">
                                                            <small><strong>Cadastrado em:</strong> <span id="det_created_at">–</span></small>
                                                        </div>
                                                        {{-- Data de atualização --}}
                                                        <div class="col-sm-6">
                                                            <small><strong>Atualizado em:</strong> <span id="det_updated_at">–</span></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- ============================================================= --}}
                                {{-- <<< FIM DO NOVO PASSO “Endereçamento / Prateleira” >>> --}}
                                {{-- ============================================================= --}}

                                <input type="hidden" id="subs" value="{{json_encode($subs)}}">
                                <input type="hidden" id="subs_ecommerce" value="{{json_encode($subsEcommerce)}}">
                                <input type="hidden" id="divisoes" value="{{json_encode($divisoes)}}" name="">
                                <input type="hidden" id="subDivisoes" value="{{json_encode($subDivisoes)}}" name="">
                                <input type="hidden" id="combinacoes" value="{{old('combinacoes')}}" name="combinacoes">

                                <div class="card-footer">

                                    <div class="row">
                                        <div class="col-xl-2">

                                        </div>
                                        <div class="col-lg-3 col-sm-6 col-md-4">
                                            <a style="width: 100%" class="btn btn-danger" href="/produtos">
                                                <i class="la la-close"></i>
                                                <span class="">Cancelar</span>
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-sm-6 col-md-4">
                                            <button id="btn-salvar" style="width: 100%" type="submit" class="btn btn-success">
                                                <i class="la la-check"></i>
                                                <span class="">Salvar</span>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                    </form>
                </div>
            </div>
            <input type="hidden" id="sub_id" value="@if(isset($produto)) {{$produto->sub_categoria_id}} @else 0 @endif" name="">
            <input type="hidden" id="sub_ecommerce_id" value="@if(isset($produto)) {{$produto->sub_categoria_id}} @else 0 @endif" name="">
            <!-- end nav -->


            </form>
        </div>
    </div>
    </div>
    </div>

    <div class="modal fade" id="modal-grade1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Escolha as combinações</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div style="margin-top: 15px;">
                            <h3>Divisões</h3>
                            <div class="divisoes">

                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div style="margin-top: 5px;">
                            <h3>Subdivisões</h3>
                            <div class="subDivisoes">

                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button style="width: 100%" type="button" onclick="escolhaDivisao()" class="btn btn-success font-weight-bold">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-grade2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preencha os campos das combinações</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body modal-body-grade">
                    <div class="row">
                        <div style="margin-top: 15px;">
                            <div class="combinacoes">

                            </div>
                        </div>
                    </div>


                </div>

                <div class="modal-footer">
                    <button style="width: 100%" type="button" onclick="finalizarGrade()" class="btn btn-success font-weight-bold">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-categoria" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Categoria</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-xl-12">


                            <div class="row">

                                <div class="form-group validated col-12">
                                    <label class="col-form-label" id="lbl_cpf_cnpj">Nome</label>
                                    <div class="">
                                        <input type="text" id="nome_categoria" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
                    <button type="button" onclick="salvarCategoria()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-sub-categoria" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Sub Categoria</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-xl-12">

                            <div class="row">

                                <div class="form-group validated col-12">
                                    <label class="col-form-label" id="lbl_cpf_cnpj">Nome</label>
                                    <div class="">
                                        <input type="text" id="nome_sub_categoria" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
                    <button type="button" onclick="salvarSubCategoria()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-marca" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Marca</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-xl-12">


                            <div class="row">

                                <div class="form-group validated col-12">
                                    <label class="col-form-label" id="lbl_cpf_cnpj">Nome</label>
                                    <div class="">
                                        <input type="text" id="nome_marca" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
                    <button type="button" onclick="salvarMarca()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="modal-custo" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Custo Detalhado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        x
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-xl-12">
                            <div class="row">

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label">Valor de Custo</label>
                                    <div class="">
                                        <input type="text" id="modal_valor_custo" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%ST</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_st" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%IPI</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_ipi" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%MLV</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_mlv" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%PIS</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_pis" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%Custo Frete</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_frete" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">%Outras Despesas</label>
                                    <div class="">
                                        <input type="text" id="modal_perc_outros" class="form-control money">
                                    </div>
                                </div>

                                <div class="form-group validated col-lg-4 col-12">
                                    <label class="col-form-label" id="">Valor de Venda</label>
                                    <div class="">
                                        <input type="text" id="modal_valor_venda" class="form-control money">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
                    <button type="button" onclick="calcCustoAction()" class="btn btn-success font-weight-bold spinner-white spinner-right">Setar Custo</button>
                </div>
            </div>

        </div>
    </div>
    @section('javascript')
        <script type="text/javascript" src="/js/nicEdit-latest.js"></script>
        <script src="{{ asset('js/axios.min.js') }}"></script>
        <script type="text/javascript">

            $('#btn-salvar').click(() => {
                setTimeout(() => {
                    $('#btn-salvar').attr('disabled', 1)
                }, 10)

                setTimeout(() => {
                    $('#btn-salvar').removeAttr('disabled')
                },600)
            })
            $('[data-toggle="popover"]').popover()
            $(function () {

                new nicEditor({fullPanel : true}).panelInstance('info_tecnica_composto',{hasPanel : true});

                setTimeout(() => {
                    tpComposto();
                    $('#valor_venda').focus()
                }, 200)

                let is = $('.ecommerce').is(':checked');
                if(is){
                    $('.div-ecommmerce').css('display', 'block')
                }else{
                    $('.div-ecommmerce').css('display', 'none')
                }

                is = $('#der_petroleo').is(':checked');

                if(is){
                    $('.div-petroleo').css('display', 'block')
                }else{
                    $('.div-petroleo').css('display', 'none')
                }
                tpVeiculo();
                tpLote();
                acrescimoPerca();
                setTimeout(() => {
                    montaSubs()
                    montaSubsEcommerce()
                    let sub_id = $('#sub_id').val()
                    if(sub_id > 0){
                        sub_id = parseInt(sub_id)
                        $('#sub_categoria_id').val(sub_id).change()
                    }

                    // reloadCombinacao()
                }, 300)

            });

            function calcCusto(){
                $('#modal-custo').modal('show')
                let vc = $('#valor_compra').val()
                $('#modal_valor_custo').val(vc)

                $('#modal_perc_frete').val($('#perc_frete').val())
                $('#modal_perc_outros').val($('#perc_outros').val())
                $('#modal_perc_mlv').val($('#perc_mlv').val())
                $('#modal_perc_st').val($('#pICMSST').val())
                $('#modal_perc_ipi').val($('#perc_ipi').val())
                $('#modal_perc_pis').val($('#perc_pis').val())
                $('#modal_valor_venda').val($('#valor_venda').val())
            }

            $('#modal-custo .money').keyup(() => {
                calcular()
            })

            function calcCustoAction(){
                $('#perc_frete').val($('#modal_perc_frete').val())
                $('#perc_outros').val($('#modal_perc_outros').val())
                $('#perc_mlv').val($('#modal_perc_mlv').val())
                $('#pICMSST').val($('#modal_perc_st').val())
                $('#perc_ipi').val($('#modal_perc_ipi').val())
                $('#perc_pis').val($('#modal_perc_pis').val())
                $('#valor_venda').val($('#modal_valor_venda').val())

                $('#modal-custo').modal('hide')
                calcCustoPerc()

            }

            function calcular(){
                let modal_perc_st = $('#modal_perc_st').val() ? $('#modal_perc_st').val() : '0'
                let modal_perc_ipi = $('#modal_perc_ipi').val() ? $('#modal_perc_ipi').val() : '0'
                let modal_perc_mlv = $('#modal_perc_mlv').val() ? $('#modal_perc_mlv').val() : '0'
                let modal_perc_pis = $('#modal_perc_pis').val() ? $('#modal_perc_pis').val() : '0'
                let modal_perc_frete = $('#modal_perc_frete').val() ? $('#modal_perc_frete').val() : '0'
                let modal_perc_outros = $('#modal_perc_outros').val() ? $('#modal_perc_outros').val() : '0'

                modal_perc_st = modal_perc_st.replace(",", ".")
                modal_perc_ipi = modal_perc_ipi.replace(",", ".")
                modal_perc_mlv = modal_perc_mlv.replace(",", ".")
                modal_perc_pis = modal_perc_pis.replace(",", ".")
                modal_perc_frete = modal_perc_frete.replace(",", ".")
                modal_perc_outros = modal_perc_outros.replace(",", ".")

                let valor = $('#valor_compra').val()
                valor = valor.replace(",", ".")
                valor = parseFloat(valor)
                modal_perc_st = parseFloat(modal_perc_st)
                if(modal_perc_st > 0){
                    valor += valor*(modal_perc_st/100)
                }

                modal_perc_ipi = parseFloat(modal_perc_ipi)
                if(modal_perc_ipi > 0){
                    valor += valor*(modal_perc_ipi/100)
                }
                modal_perc_mlv = parseFloat(modal_perc_mlv)
                if(modal_perc_mlv > 0){
                    valor += valor*(modal_perc_mlv/100)
                }
                modal_perc_pis = parseFloat(modal_perc_pis)
                if(modal_perc_pis > 0){
                    valor += valor*(modal_perc_pis/100)
                }
                modal_perc_frete = parseFloat(modal_perc_frete)
                if(modal_perc_frete > 0){
                    valor += valor*(modal_perc_frete/100)
                }
                modal_perc_outros = parseFloat(modal_perc_outros)
                if(modal_perc_outros > 0){
                    valor += valor*(modal_perc_outros/100)
                }

                $('#modal_valor_venda').val(valor.toFixed(2).replace(".", ","))
            }

            function calcCustoPerc(){
                let valorCompra = parseFloat($('#valor_compra').val().replace(',', '.'));
                let valorVenda = parseFloat($('#valor_venda').val().replace(',', '.'));

                if(valorCompra > 0 && valorVenda > 0){
                    let dif = (valorVenda - valorCompra)/valorCompra*100;


                    $('#percentual_lucro').val(dif)
                }else{
                    $('#percentual_lucro').val('0')
                }
            }

            $('.ecommerce').change((target) => {
                let is = $('.ecommerce').is(':checked');
                if(is){
                    $('.div-ecommmerce').css('display', 'block')
                }else{
                    $('.div-ecommmerce').css('display', 'none')

                }
            })

            $('#der_petroleo').change((target) => {
                let is = $('#der_petroleo').is(':checked');
                if(is){
                    $('.div-petroleo').css('display', 'block')
                }else{
                    $('.div-petroleo').css('display', 'none')

                }
            })

            $('.locacao').change((target) => {
                let is = $('.locacao').is(':checked');
                if(is){
                    $('.div-loc').css('display', 'block')
                }else{
                    $('.div-loc').css('display', 'none')

                }
            })

            function novaCategoria(){
                $('#modal-categoria').modal('show')
            }

            function novaSubCategoria(){
                $('#modal-sub-categoria').modal('show')
            }

            function novaMarca(){
                $('#modal-marca').modal('show')

            }

            function salvarCategoria(){
                let nome = $('#nome_categoria').val()
                if(!nome){
                    swal("Erro", "Informe nome", "warning")
                }else{
                    let token = $('#_token').val();
                    $.post(path + 'categorias/quickSave',
                        {
                            _token: token,
                            nome: nome
                        })
                        .done((res) =>{

                            console.log(res)
                            $('#categoria').append('<option value="'+res.id+'">'+
                                res.nome+'</option>').change();
                            $('#categoria').val(res.id).change();
                            swal("Sucesso", "Categoria adicionada!!", 'success')
                                .then(() => {
                                    $('#modal-categoria').modal('hide')
                                })
                        })
                        .fail((err) => {
                            console.log(err)
                            swal("Erro", "Algo deu errado!!", 'error')

                        })
                }
            }

            function salvarSubCategoria(){
                let nome = $('#nome_sub_categoria').val()
                let categoria_id = $('#categoria').val()

                if(!categoria_id){
                    swal("Erro", "Informe a categoria", "warning")
                }else if(!nome){
                    swal("Erro", "Informe nome", "warning")
                }else{
                    let token = $('#_token').val();
                    $.post(path + 'subcategorias/quickSave',
                        {
                            _token: token,
                            nome: nome,
                            categoria_id: categoria_id
                        })
                        .done((res) =>{

                            console.log(res)
                            $('#sub_categoria_id').append('<option value="'+res.id+'">'+
                                res.nome+'</option>').change();
                            $('#sub_categoria_id').val(res.id).change();
                            swal("Sucesso", "Sub Categoria adicionada!!", 'success')
                                .then(() => {
                                    $('#modal-sub-categoria').modal('hide')
                                })
                        })
                        .fail((err) => {
                            console.log(err)
                            swal("Erro", "Algo deu errado!!", 'error')

                        })
                }
            }

            function salvarMarca(){
                let nome = $('#nome_marca').val()
                if(!nome){
                    swal("Erro", "Informe nome", "warning")
                }else{
                    let token = $('#_token').val();
                    $.post(path + 'marcas/quickSave',
                        {
                            _token: token,
                            nome: nome
                        })
                        .done((res) =>{

                            console.log(res)
                            $('#marca').append('<option value="'+res.id+'">'+
                                res.nome+'</option>').change();
                            $('#marca').val(res.id).change();
                            swal("Sucesso", "Marca adicionada!!", 'success')
                                .then(() => {
                                    $('#modal-marca').modal('hide')
                                })
                        })
                        .fail((err) => {
                            console.log(err)
                            swal("Erro", "Algo deu errado!!", 'error')

                        })
                }
            }

            $('#tp_veiculo').change(() => {
                tpVeiculo();
            })

            function tpVeiculo(){
                if($('#tp_veiculo').is(':checked')){
                    $('.div_veiculo').css('display', 'block')
                }else{
                    $('.div_veiculo').css('display', 'none')
                }
            }

            $('#lote_venc').change(() => {
                tpLote();
            })

            function tpLote(){
                if($('#lote_venc').is(':checked')){
                    $('.div_lote').css('display', 'block')
                }else{
                    $('.div_lote').css('display', 'none')
                }
            }

            $('#tipo_dimensao').change(() => {
                acrescimoPerca();
            })

            function acrescimoPerca(){
                if($('#tipo_dimensao').val() != ''){
                    $('.div_acres_perca').css('display', 'block')
                }else{
                    $('.div_acres_perca').css('display', 'none')
                }
            }

            $('#composto').change(() => {
                tpComposto();
            })

            function tpComposto(){
                if($('#composto').is(':checked')){
                    $('.div_info_composto').css('display', 'inline-block')
                    $('.div_info_composto').css('width', '100%')
                }else{
                    $('.div_info_composto').css('display', 'none')
                }
            }

            // ==========================
            // SELECT2 para Produto Referenciado
            // ==========================
            $(document).ready(function () {
                // CSS para o botão “clear” ao lado do Select2
                const clearButtonCSS = `
				<style>
					.clear-button {
						background-color: white;
						color: black;
						border: none;
						font-size: 14px;
						padding: 5px 8px;
						cursor: pointer;
						margin-left: 0px;
						border-radius: 0px;
					}
				</style>
			`;
                $('head').append(clearButtonCSS);

                // Função genérica para inserir botão “✖” ao lado do Select2
                function addClearButton(selectId, clearFunction) {
                    const $selectContainer = $(selectId).next('.select2-container');
                    $selectContainer.after(`
					<button type="button" class="clear-button" data-select="${selectId}">✖</button>
				`);
                    $(document).on('click', `.clear-button[data-select="${selectId}"]`, function () {
                        clearFunction();
                    });
                }

                // Inicializa Select2 em #produto_referenciado_id
                $('#produto_referenciado_id').select2({
                    placeholder: 'Selecione um produto',
                    allowClear: true,
                    ajax: {
                        url: '/produtos/searchProduto',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data.results.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.referencia + ' | ' + item.nome + ' | ' + item.codBarras,
                                        img: item.imagem  // para exibir imagem no templateResult
                                    };
                                })
                            };
                        }
                    },
                    templateResult: function (item) {
                        if (!item.id) {
                            return item.text;
                        }
                        const imgUrl = item.img || '/imgs/no_image.png';
                        return $(`
						<div style="display: flex; align-items: center;">
							<img src="${imgUrl}" alt="Imagem do produto"
								 style="width: 40px; height: 40px; margin-right: 10px; border-radius: 50%;" />
							<div>
								<div><strong>${item.text}</strong></div>
							</div>
						</div>
					`);
                    },
                    templateSelection: function (item) {
                        return item.text || 'Selecione um produto';
                    }
                });

                // Aplica o botão “✖” que limpa o Select2 de produto referenciado
                addClearButton('#produto_referenciado_id', () => {
                    $('#produto_referenciado_id').val(null).trigger('change');
                });
            });
        </script>

        <script type="text/javascript">
            $(document).ready(function() {

                // -------------------------------------------------------
                // 1) Inicializa Select2 em #produto_prateleira_id, com AJAX
                // -------------------------------------------------------
                $('#produto_prateleira_id').select2({
                    placeholder: 'Selecione uma prateleira',
                    allowClear: true,
                    minimumInputLength: 1,
                    ajax: {
                        url: '/produto_prateleiras/search',   // ajuste conforme sua rota real
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term  // termo digitado
                            };
                        },
                        processResults: function(data) {
                            // O servidor deve retornar algo como:
                            // { results: [ { id: 1, text: 'A-1-02-B-03' }, { id: 2, text: 'B-2-01-C-05' }, … ] }
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    templateResult: function(item) {
                        // Caso queira exibir algo mais sofisticado no dropdown,
                        // como identificação + descrição, adapte aqui.
                        return item.text;
                    },
                    templateSelection: function(item) {
                        return item.text || 'Selecione uma prateleira';
                    }
                });

                // -------------------------------------------------------
                // 2) Quando o usuário seleciona uma prateleira, buscar JSON e preencher
                // -------------------------------------------------------
                $('#produto_prateleira_id').on('change', function() {
                    let prateleiraId = $(this).val();
                    if (!prateleiraId) {
                        // Se limpou a seleção, esconde o container de detalhes
                        $('#prateleira_detalhes').hide();
                        return;
                    }

                    // Requisição AJAX para /produto_prateleiras/{id}/json
                    $.ajax({
                        url: '/produto_prateleiras/' + prateleiraId + '/json',
                        method: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            // Preenche cada campo dentro de #prateleira_detalhes
                            $('#det_identificacao').text(data.identificacao || '-');
                            $('#det_descricao').text(data.descricao || '-');
                            $('#det_posicao').text(data.posicao || '-');
                            $('#det_localizacao').text(data.localizacao || '-');
                            $('#det_observacao').text(data.observacao || '-');

                            // Formata ISO -> dd/mm/YYYY HH:mm
                            if (data.created_at) {
                                let c = new Date(data.created_at);
                                let cad = c.toLocaleDateString('pt-BR') + ' ' +
                                    String(c.getHours()).padStart(2,'0') + ':' +
                                    String(c.getMinutes()).padStart(2,'0');
                                $('#det_created_at').text(cad);
                            } else {
                                $('#det_created_at').text('-');
                            }
                            if (data.updated_at) {
                                let u = new Date(data.updated_at);
                                let upd = u.toLocaleDateString('pt-BR') + ' ' +
                                    String(u.getHours()).padStart(2,'0') + ':' +
                                    String(u.getMinutes()).padStart(2,'0');
                                $('#det_updated_at').text(upd);
                            } else {
                                $('#det_updated_at').text('-');
                            }

                            // Ajusta o título no header do card com a identificação
                            $('#titulo_prateleira').text(data.identificacao ? ` [${data.identificacao}]` : '');

                            // Exibe o container de detalhes
                            $('#prateleira_detalhes').slideDown(200);
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: 'Não foi possível carregar os dados da prateleira selecionada.'
                            });
                            $('#prateleira_detalhes').hide();
                        }
                    });
                });

                // -------------------------------------------------------
                // 3) Se já existir uma prateleira selecionada (edição),
                //    dispara automaticamente o “change” para carregar os detalhes
                // -------------------------------------------------------
                @isset($produto)
                @if($produto->produto_prateleira_id)
                $('#produto_prateleira_id').trigger('change');
                @endif
                @endisset

            });
        </script>
        <script>
            (function(){
                function initSelect2IbsCbs(){
                    if(!window.jQuery || !jQuery.fn.select2) return;

                    $('#cst_ibs_cbs').select2({
                        width: '100%',
                        placeholder: 'Selecione...',
                        allowClear: true,
                        ajax: {
                            url: '/produtos/ajax/cst-ibs-cbs',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { term: params.term || '', page: params.page || 1 };
                            },
                            processResults: function (data) {
                                return data;
                            }
                        }
                    });

                    $('#class_trib_ibs_cbs').select2({
                        width: '100%',
                        placeholder: 'Selecione...',
                        allowClear: true,
                        ajax: {
                            url: '/produtos/ajax/class-trib-ibs-cbs',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { term: params.term || '', page: params.page || 1 };
                            },
                            processResults: function (data) {
                                return data;
                            }
                        }
                    });

                    async function atualizarReducao(){
                        const cst = ($('#cst_ibs_cbs').val() || '').toString().trim();
                        const cls = ($('#class_trib_ibs_cbs').val() || '').toString().trim();

                        // só aplica automaticamente quando CST = 200
                        if(cst !== '200' || !cls){
                            return;
                        }

                        try{
                            const url = '/produtos/ajax/reducao-ibs-cbs?cst=' + encodeURIComponent(cst) + '&class=' + encodeURIComponent(cls);
                            const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const j = await r.json();

                            const predibs = (j && typeof j.predibs !== 'undefined') ? j.predibs : 0;
                            const predcbs = (j && typeof j.predcbs !== 'undefined') ? j.predcbs : 0;

                            $('#reducao_ibs').val(predibs);
                            $('#reducao_cbs').val(predcbs);
                        }catch(e){
                            // silencioso (não quebra cadastro)
                        }
                    }

                    $('#cst_ibs_cbs').on('change', atualizarReducao);
                    $('#class_trib_ibs_cbs').on('change', atualizarReducao);

                    // se estiver editando e já tiver valores, tenta preencher ao carregar
                    setTimeout(atualizarReducao, 250);
                }

                document.addEventListener('DOMContentLoaded', initSelect2IbsCbs);
            })();
        </script>
    @endsection
@endsection
