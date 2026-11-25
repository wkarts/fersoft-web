@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush
@section('content')

    <header class="fer-page__heading"><h1>Coleta / Agendamento</h1></header>
<style>
        .select2-container .select2-selection--single,
        .select2-container .select2-selection--multiple {
            min-height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.42rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            color: #495057 !important;
            padding-left: 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
        .card-section-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #3F4254;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #EBEDF3;
        }
        .parada-box {
            background: #f8f9fa;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }
        .parada-box:hover {
            border-color: #b5b5c3;
            box-shadow: 0px 0px 15px rgba(0,0,0,0.05);
        }
    </style>

    @php
        $listaVeiculos = $veiculos ?? [];
        $listaTipos = $tipos ?? [];
        $listaFuncionarios = $funcionarios ?? [];
        $listaMotoristas = $motoristas ?? [];
        $listaClientes = $clientes ?? [];
        $listaFornecedores = $fornecedores ?? [];
        $listaCombustiveis = $combustiveis ?? [];
        $listaCaixas = $caixas ?? [];
        $listaProdutosResiduos = $produtosResiduos ?? [];
    @endphp

    <div class="d-flex flex-column flex-column-fluid" id="kt_content">
        <div class="fer-page fer-operational fer-form card card-custom gutter-b shadow-sm">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="card-label font-weight-bolder">
                        <i class="la la-truck icon-xl text-primary mr-2"></i>
                        {{ $title }}
                    </h3>
                </div>
            </div>

            <div class="card-body">
                <form method="post" action="{{ isset($data->id) ? $actionUpdate : $actionSave }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $data->id ?? '' }}">
                    <input type="hidden" name="coleta_origem_id" value="{{ $data->coleta_origem_id ?? '' }}">

                    <!-- 1. DADOS BÁSICOS -->
                    <div class="card-section-title">
                        <i class="la la-info-circle text-primary"></i> 1. Dados Básicos do Transporte
                    </div>

                    <!-- 🚀 CAMPO: IMPORTAR DA BALANÇA -->
                    <div class="row mb-4 bg-light-primary p-3 rounded" style="border-left: 4px solid #3699FF;">
                        <div class="col-lg-6 col-md-12">
                            <div class="form-group mb-0">
                                <label for="pesagem_id" class="text-primary font-weight-bold">
                                    <i class="fas fa-balance-scale"></i> Importar Expedição da Balança (Agenda a partir do Peso)
                                </label>
                                <select name="pesagem_id" id="pesagem_id" class="form-control select2">
                                    <option value="">-- Planeamento Manual (Não associar Balança) --</option>
                                    @if(isset($pesagensDisponiveis))
                                        @foreach($pesagensDisponiveis as $pesagem)
                                            <option value="{{ $pesagem->id }}"
                                                    @if(isset($data) && $data->pesagem_id == $pesagem->id) selected @endif>
                                                TKT #{{ $pesagem->id }} - Placa: {{ $pesagem->veiculo->placa ?? 'S/ Placa' }} | Peso: {{ number_format($pesagem->peso_liquido, 2, ',', '.') }} KG
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="form-text text-muted mt-2">
                                    <i class="fas fa-magic"></i> Ao selecionar, o sistema vai preencher automaticamente o veículo, cliente, produto e peso.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- 🚀 NOVO: CHECKBOX DE TERCEIRIZADO -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="checkbox checkbox-outline checkbox-success font-weight-bold">
                                <input type="checkbox" id="is_terceirizado" name="is_terceirizado" value="1"
                                    {{ (isset($data) && $data->is_terceirizado == 1) ? 'checked' : '' }}>
                                <span></span> Veículo / Motorista Terceirizado (Sem Frota Interna e Sem Rastreio)
                            </label>
                            <small class="form-text text-muted d-block mt-1">Ao marcar, o link de apontamento do material será enviado por WhatsApp 30 minutos após o horário agendado de saída.</small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- BLOCO FROTA INTERNA -->
                        <div class="form-group col-lg-2 col-md-6 bloco-frota-interna">
                            <label class="col-form-label font-weight-bold">Veículo / Placa</label>
                            <select id="veiculo_id" class="form-control select2" name="veiculo_id">
                                <option value="">Selecione um Veículo</option>
                                @php foreach ($listaVeiculos as $veiculo): @endphp
                                <option value="{{ $veiculo->id }}" data-km="{{ $veiculo->quilometragem }}"
                                    {{ old('veiculo_id', $data->veiculo_id ?? '') == $veiculo->id ? 'selected' : '' }}>
                                    {{ $veiculo->placa }} - {{ $veiculo->modelo }}
                                </option>
                                @php endforeach; @endphp
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 bloco-frota-interna">
                            <label class="col-form-label font-weight-bold">Motorista Responsável</label>
                            <select id="motorista_id" class="form-control select2" name="motorista_id">
                                <option value="">Selecione um Motorista</option>
                                @php foreach ($listaMotoristas as $f): @endphp
                                <option value="{{ $f->id }}" {{ old('motorista_id', $data->motorista_id ?? '') == $f->id ? 'selected' : '' }}>
                                    {{ $f->nome }}
                                </option>
                                @php endforeach; @endphp
                            </select>
                        </div>

                        <!-- BLOCO TERCEIRIZADO -->
                        <div class="form-group col-lg-3 col-md-4 bloco-terceirizado" style="display: none;">
                            <label class="col-form-label font-weight-bold">Nome do Terceiro/Motorista</label>
                            <input type="text" class="form-control" name="terceiro_nome" id="terceiro_nome" value="{{ old('terceiro_nome', $data->terceiro_nome ?? '') }}" placeholder="Nome do motorista">
                        </div>

                        <div class="form-group col-lg-2 col-md-4 bloco-terceirizado" style="display: none;">
                            <label class="col-form-label font-weight-bold">CPF/CNPJ do Terceiro</label>
                            <input type="text" class="form-control cpf_cnpj" name="terceiro_documento" value="{{ old('terceiro_documento', $data->terceiro_documento ?? '') }}" placeholder="Apenas números">
                        </div>

                        <div class="form-group col-lg-2 col-md-4 bloco-terceirizado" style="display: none;">
                            <label class="col-form-label font-weight-bold text-success">WhatsApp (Para Envio Link)</label>
                            <input type="text" class="form-control telefone" name="terceiro_telefone" id="terceiro_telefone" value="{{ old('terceiro_telefone', $data->terceiro_telefone ?? '') }}" placeholder="(00) 00000-0000">
                        </div>

                        <!-- CAMPOS COMUNS (Tipo e MTR) -->
                        <div class="form-group col-lg-3 col-md-4">
                            <label class="col-form-label font-weight-bold">Tipo de Movimentação</label>
                            <select class="form-control select2" name="tipo_movimentacao_id" required>
                                <option value="">Selecione o Tipo</option>
                                @php foreach ($listaTipos as $t): @endphp
                                <option value="{{ $t->id }}" {{ old('tipo_movimentacao_id', $data->tipo_movimentacao_id ?? '') == $t->id ? 'selected' : '' }}>
                                    {{ $t->nome }}
                                </option>
                                @php endforeach; @endphp
                            </select>
                        </div>

                        <div class="form-group col-lg-3 col-md-4 col-sm-6 bloco-frota-interna">
                            <label class="font-weight-bold text-dark">Integração com a Balança</label>
                            <div class="checkbox-inline mt-1">
                                <label class="checkbox checkbox-success font-weight-bold">
                                    <input type="checkbox" name="passar_balanca" value="1"
                                        {{ (isset($data) && $data->passar_balanca == 1) ? 'checked' : '' }}>
                                    <span></span> Passar na balança no retorno
                                </label>
                            </div>
                            <span class="form-text text-muted font-size-sm">Gera a pré-pesagem automaticamente.</span>
                        </div>

                        <div class="form-group col-lg-2 col-md-4">
                            <label class="col-form-label font-weight-bold">Gerar MTR?</label>
                            <select class="form-control" name="gerar_mtr">
                                <option value="0" {{ old('gerar_mtr', $data->gerar_mtr ?? 0) == 0 ? 'selected' : '' }}>🔴 NÃO</option>
                                <option value="1" {{ old('gerar_mtr', $data->gerar_mtr ?? 0) == 1 ? 'selected' : '' }}>🟢 SIM (Automático)</option>
                            </select>
                        </div>

                        <div class="form-group col-lg-2 col-md-4 bloco-frota-interna">
                            <label class="col-form-label font-weight-bold">Local de Partida</label>
                            <select class="form-control" name="tipo_partida">
                                <option value="empresa" {{ old('tipo_partida', $data->tipo_partida ?? 'empresa') == 'empresa' ? 'selected' : '' }}>🏢 Base</option>
                                <option value="residencia" {{ old('tipo_partida', $data->tipo_partida ?? '') == 'residencia' ? 'selected' : '' }}>🏠 Casa</option>
                            </select>
                        </div>
                    </div>

                    <!-- 2. EQUIPE E AJUDANTES -->
                    <div class="card-section-title mt-4">
                        <i class="la la-users text-primary"></i> 2. Equipe de Apoio (Ajudantes)
                    </div>
                    <div class="row">
                        <div class="form-group col-12">
                            <label class="col-form-label font-weight-bold">Ajudantes Escalados (Múltiplos)</label>
                            <select class="form-control select2" name="ajudantes_ids[]" multiple="multiple">
                                @php foreach ($listaFuncionarios as $f): @endphp
                                <option value="{{ $f->id }}"
                                    {{ (isset($data) && $data->ajudantes && $data->ajudantes->contains($f->id)) ? 'selected' : '' }}>
                                    {{ $f->nome }}
                                </option>
                                @php endforeach; @endphp
                            </select>
                        </div>
                    </div>

                    <!-- BLOCOS DE CONFIGURAÇÃO -->
                    <div class="row mt-4">
                        <div class="col-lg-4 col-md-12 mb-3">
                            <div class="p-3 rounded h-100 shadow-xs" style="background: #f0f8ff; border: 1px solid #b8daff;">
                                <label class="col-form-label font-weight-bold text-dark">
                                    <i class="la la-mobile text-primary"></i> Notificação de Ponto
                                </label>
                                <select class="form-control" name="bater_ponto_whatsapp">
                                    <option value="1" {{ old('bater_ponto_whatsapp', $data->bater_ponto_whatsapp ?? 1) == 1 ? 'selected' : '' }}>🟢 SIM</option>
                                    <option value="0" {{ old('bater_ponto_whatsapp', $data->bater_ponto_whatsapp ?? 1) == 0 ? 'selected' : '' }}>🔴 NÃO</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-12 mb-3">
                            <div class="p-3 rounded h-100 shadow-xs" style="background: #fffdf0; border: 1px solid #ffeeba;">
                                <label class="col-form-label font-weight-bold text-dark">
                                    <i class="la la-clipboard-check text-warning"></i> Checklist Pré-Viagem
                                </label>
                                <select class="form-control" name="checklist_obrigatorio">
                                    <option value="1" {{ old('checklist_obrigatorio', $data->checklist_obrigatorio ?? 1) == 1 ? 'selected' : '' }}>🟢 SIM</option>
                                    <option value="0" {{ old('checklist_obrigatorio', $data->checklist_obrigatorio ?? 1) == 0 ? 'selected' : '' }}>🔴 NÃO</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-12 mb-3">
                            <div class="p-3 rounded h-100 shadow-xs" style="background: #f4f6f9; border: 1px solid #d3d3d3;">
                                <label class="col-form-label font-weight-bold text-dark">
                                    <i class="la la-gas-pump text-info"></i> Link de Abastecimento
                                </label>
                                <select class="form-control" name="solicitar_abastecimento">
                                    <option value="1" {{ old('solicitar_abastecimento', $data->solicitar_abastecimento ?? 0) == 1 ? 'selected' : '' }}>🟢 ENVIAR (Início Rota)</option>
                                    <option value="0" {{ old('solicitar_abastecimento', $data->solicitar_abastecimento ?? 0) == 0 ? 'selected' : '' }}>🔴 NÃO ENVIAR</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ROTA MULTI-STOP -->
                    <div class="card-section-title mt-4">
                        <i class="la la-map-marker text-primary"></i> 3. Rota de Coletas / Entregas (Multi-Stop & Caçambas)
                    </div>
                    <div class="row p-3 rounded" style="background: #f8f9fa; border: 1px solid #ddd;">
                        <div class="col-12 text-right mb-3">
                            <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" onclick="adicionarDestino()">
                                <i class="la la-plus"></i> Adicionar Parada / Destino
                            </button>
                        </div>
                        <div class="col-12" id="container-destinos">
                            @if(isset($data) && $data->destinos && $data->destinos->count() > 0)
                                @php foreach ($data->destinos as $key => $dest): @endphp
                                <div class="parada-box" id="linha_destino_old_{{ $key }}">
                                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                        <span class="font-weight-bolder text-dark font-size-h6">Parada #<span class="n-ordem">{{ $key + 1 }}</span></span>
                                        <button type="button" class="btn btn-sm btn-light-danger font-weight-bold py-1 px-3" onclick="removerDestino('old_{{ $key }}')">
                                            <i class="la la-trash"></i> Remover
                                        </button>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-lg-4 col-md-6 mb-3">
                                            <label class="font-weight-bold small">Cliente ou Fornecedor</label>
                                            <select class="form-control select2-clientes select2" name="destinos[old_{{ $key }}][entidade_id]">
                                                <option value="">Endereço Avulso...</option>
                                                <optgroup label="Clientes">
                                                    @php foreach ($listaClientes as $c): @endphp
                                                    <option value="cliente_{{ $c->id }}"
                                                            data-lat="{{ $c->latitude }}"
                                                            data-lon="{{ $c->longitude }}"
                                                            data-end="{{ $c->rua ?? '' }}, {{ $c->numero ?? '' }} - {{ $c->bairro ?? '' }}"
                                                        {{ $dest->cliente_id == $c->id ? 'selected' : '' }}>
                                                        {{ $c->razao_social ?? $c->nome }}
                                                    </option>
                                                    @php endforeach; @endphp
                                                </optgroup>
                                                <optgroup label="Fornecedores">
                                                    @php foreach ($listaFornecedores as $f): @endphp
                                                    <option value="fornecedor_{{ $f->id }}"
                                                            data-lat="{{ $f->latitude }}"
                                                            data-lon="{{ $f->longitude }}"
                                                            data-end="{{ $f->rua ?? '' }}, {{ $f->numero ?? '' }} - {{ $f->bairro ?? '' }}"
                                                        {{ $dest->fornecedor_id == $f->id ? 'selected' : '' }}>
                                                        [FOR] {{ $f->razao_social ?? $f->nome }}
                                                    </option>
                                                    @php endforeach; @endphp
                                                </optgroup>
                                            </select>
                                            <div class="mt-3">
                                                <label class="checkbox checkbox-success checkbox-sm">
                                                    <input type="checkbox" name="destinos[old_{{ $key }}][is_aterro]" value="1" {{ ($dest->is_aterro ?? 0) == 1 ? 'checked' : '' }}>
                                                    <span></span> <b class="ml-2 text-success">É o Aterro / Destino Final?</b>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-4 col-md-6 mb-3">
                                            <label class="font-weight-bold small">Endereço P/ GPS</label>
                                            <input type="text" class="form-control endereco-input mb-3" name="destinos[old_{{ $key }}][endereco_completo]" value="{{ $dest->endereco_completo }}">
                                            <input type="hidden" name="destinos[old_{{ $key }}][latitude]" class="lat-input" value="{{ $dest->latitude }}">
                                            <input type="hidden" name="destinos[old_{{ $key }}][longitude]" class="lon-input" value="{{ $dest->longitude }}">

                                            <label class="font-weight-bold small">Operação de Caçamba</label>
                                            <select name="destinos[old_{{ $key }}][tipo_operacao_caixa]" class="form-control">
                                                <option value="nenhuma" {{ ($dest->tipo_operacao_caixa ?? 'nenhuma') == 'nenhuma' ? 'selected' : '' }}>Sem Caixa</option>
                                                <option value="colocar" {{ ($dest->tipo_operacao_caixa ?? '') == 'colocar' ? 'selected' : '' }}>Deixar Caçamba</option>
                                                <option value="retirar" {{ ($dest->tipo_operacao_caixa ?? '') == 'retirar' ? 'selected' : '' }}>Recolher Caçamba</option>
                                                <option value="trocar" {{ ($dest->tipo_operacao_caixa ?? '') == 'trocar' ? 'selected' : '' }}>Trocar Caçamba</option>
                                            </select>
                                            <div class="mt-2">
                                                <label class="font-weight-bold small text-primary">Cobrar Frete desta Parada?</label>
                                                <select name="destinos[old_{{ $key }}][cobrar_frete]" class="form-control">
                                                    <option value="1" {{ ($dest->cobrar_frete ?? 1) == 1 ? 'selected' : '' }}>🟢 SIM (Faturar na Medição)</option>
                                                    <option value="0" {{ ($dest->cobrar_frete ?? 1) == 0 ? 'selected' : '' }}>🔴 NÃO (Incluso / Cortesia)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group col-lg-4 col-md-12 mb-3">
                                            <div class="row">
                                                <div class="col-6 mb-2">
                                                    <label class="font-weight-bold small text-success">Caçamba Deixada</label>
                                                    <select name="destinos[old_{{ $key }}][caixa_deixada_id]" class="form-control">
                                                        <option value="">-- Nenhuma --</option>
                                                        @php foreach ($listaCaixas as $cx): @endphp
                                                        <option value="{{ $cx->id }}" {{ ($dest->caixa_deixada_id ?? '') == $cx->id ? 'selected' : '' }}>
                                                            {{ $cx->referencia ? $cx->referencia . ' - ' : '' }}{{$cx->nome }}
                                                        </option>
                                                        @php endforeach; @endphp
                                                    </select>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <label class="font-weight-bold small text-danger">Caçamba Retirada</label>
                                                    <select name="destinos[old_{{ $key }}][caixa_retirada_id]" class="form-control">
                                                        <option value="">-- Nenhuma --</option>
                                                        @php foreach ($listaCaixas as$cx): @endphp
                                                        <option value="{{ $cx->id }}" {{ ($dest->caixa_retirada_id ?? '') == $cx->id ? 'selected' : '' }}>
                                                            {{ $cx->referencia ? $cx->referencia . ' - ' : '' }}{{$cx->nome }}
                                                        </option>
                                                        @php endforeach; @endphp
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- 🚀 NOVO: MULTIPLOS RESÍDUOS POR PARADA -->
                                            <div class="mt-3 p-3 rounded" style="background-color: #f1f3f5; border: 1px dashed #ced4da;">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="font-weight-bold small text-primary m-0"><i class="fas fa-recycle"></i> Materiais / Resíduos da Parada</label>
                                                    <button type="button" class="btn btn-sm btn-light-primary py-1 px-2 font-weight-bold" onclick="adicionarResiduoParada('old_{{ $key }}')">
                                                        <i class="la la-plus"></i> Adicionar Material
                                                    </button>
                                                </div>

                                                <div id="container-residuos-old_{{ $key }}">
                                                    @php
                                                        // Recupera itens salvos, caso seja modo de edição
                                                        $itensDestino = \DB::table('movimentacao_destino_itens')->where('movimentacao_destino_id', $dest->id)->get();
                                                    @endphp

                                                    @if($itensDestino->count() > 0)
                                                        @foreach($itensDestino as $idxRes => $res)
                                                            <div class="row mb-2 residuo-row">
                                                                <div class="col-7">
                                                                    <select name="destinos[old_{{ $key }}][residuos][old_{{ $idxRes }}][produto_id]" class="form-control form-control-sm">
                                                                        <option value="">-- Selecione o Material --</option>
                                                                        @foreach ($listaProdutosResiduos as $pr)
                                                                            <option value="{{ $pr->id }}" {{ $res->produto_id == $pr->id ? 'selected' : '' }}>{{ $pr->nome }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-4">
                                                                    <input type="text" name="destinos[old_{{ $key }}][residuos][old_{{ $idxRes }}][quantidade]" class="form-control form-control-sm text-right" placeholder="Peso (KG)" value="{{ number_format($res->quantidade ?? 0, 2, ',', '') }}">
                                                                </div>
                                                                <div class="col-1 text-right">
                                                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger mt-1" onclick="$(this).closest('.residuo-row').remove();"><i class="la la-trash"></i></button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                @php endforeach; @endphp
                            @endif
                        </div>
                    </div>

                    <!-- 4. CONTROLE DE PERCURSO -->
                    <div class="card-section-title mt-4">
                        <i class="la la-tachometer text-primary"></i> 4. Horários e KM
                    </div>
                    <div class="row">
                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">Data/Hora Saída Garagem</label>
                            <input type="datetime-local" class="form-control" name="data_hora_saida"
                                   value="{{ old('data_hora_saida', isset($data->data_hora_saida) ? \Carbon\Carbon::parse($data->data_hora_saida)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">KM Inicial</label>
                            <input type="number" id="km_inicial" class="form-control" name="km_inicial" value="{{ old('km_inicial', $data->km_inicial ?? '') }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">Data/Hora Retorno Garagem</label>
                            <input type="datetime-local" class="form-control" name="data_hora_chegada"
                                   value="{{ old('data_hora_chegada', isset($data->data_hora_chegada) ? \Carbon\Carbon::parse($data->data_hora_chegada)->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <div class="form-group col-lg-3 col-md-6">
                            <label class="col-form-label font-weight-bold">KM Final</label>
                            <input type="number" class="form-control" name="km_final" value="{{ old('km_final', $data->km_final ?? '') }}">
                        </div>
                    </div>

                    <!-- 5. STATUS GERAL -->
                    <div class="card-section-title mt-4">
                        <i class="la la-clock text-primary"></i> 5. Status da Viagem
                    </div>
                    <div class="row p-3 rounded" style="background: #f4f6f9; border: 1px solid #e4e6ef;">
                        <div class="form-group col-lg-4 col-md-6">
                            <label class="col-form-label font-weight-bold">Status Atual</label>
                            <select class="form-control" name="status">
                                <option value="agendado" {{ old('status', $data->status ?? '') == 'agendado' ? 'selected' : '' }}>Agendado / Planejado</option>
                                <option value="iniciado" {{ old('status', $data->status ?? '') == 'iniciado' ? 'selected' : '' }}>Em Percurso</option>
                                <option value="finalizado" {{ old('status', $data->status ?? '') == 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                            </select>
                        </div>
                    </div>

                    <!-- 6. ABASTECIMENTOS DA VIAGEM -->
                    <div class="card-section-title mt-4">
                        <i class="la la-gas-pump text-primary"></i> 6. Abastecimentos da Viagem
                    </div>
                    <div class="row p-3 rounded" style="background: #f8f9fa; border: 1px solid #ddd;">
                        <div class="col-12 text-right mb-2">
                            <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" id="btn-add-abastecimento">
                                <i class="la la-plus"></i> Adicionar Abastecimento
                            </button>
                        </div>
                        <div class="col-12 table-responsive">
                            <table class="table table-bordered table-striped bg-white rounded" id="tabela_abastecimentos">
                                <thead>
                                <tr class="bg-secondary text-dark">
                                    <th width="20%">Produto</th>
                                    <th width="12%">Local</th>
                                    <th width="14%">Data</th>
                                    <th width="12%">KM Atual</th>
                                    <th width="12%">Litros</th>
                                    <th width="12%">Vl. Unit.</th>
                                    <th width="12%">Subtotal</th>
                                    <th width="6%">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($data) &&$data->abastecimentos)
                                    @php foreach ($data->abastecimentos as $key =>$item): @endphp
                                    <tr id="row_old_{{ $key }}">
                                        <td>
                                            <select name="abastecimentos[old_{{ $key }}][produto_id]" class="form-control select2">
                                                @php foreach ($listaCombustiveis as$c): @endphp
                                                <option value="{{ $c->id }}" {{ $item->produto_id == $c->id ? 'selected' : '' }}>{{ $c->nome }}</option>
                                                @php endforeach; @endphp
                                            </select>
                                        </td>
                                        <td>
                                            <select name="abastecimentos[old_{{ $key }}][tipo]" class="form-control">
                                                <option value="externo" {{ $item->tipo == 'externo' ? 'selected' : '' }}>Externo</option>
                                                <option value="interno" {{ $item->tipo == 'interno' ? 'selected' : '' }}>Interno</option>
                                            </select>
                                        </td>
                                        <td><input type="date" name="abastecimentos[old_{{ $key }}][data]" class="form-control" value="{{ $item->data_abastecimento }}"></td>
                                        <td><input type="number" name="abastecimentos[old_{{ $key }}][km_abastecimento]" class="form-control" value="{{ $item->km_abastecimento }}"></td>
                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][quantidade]" class="form-control qty" value="{{ number_format($item->quantidade, 3, ',', '.') }}"></td>
                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][unitario]" class="form-control unit" value="{{ number_format($item->valor_unitario, 3, ',', '.') }}"></td>
                                        <td><input type="text" name="abastecimentos[old_{{ $key }}][total]" class="form-control total-row" value="{{ number_format($item->valor_total, 2, ',', '.') }}" readonly></td>
                                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove(); calcTotalGeral();"><i class="la la-trash"></i></button></td>
                                    </tr>
                                    @php endforeach; @endphp
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 7. OUTRAS DESPESAS -->
                    <div class="card-section-title mt-4">
                        <i class="la la-wallet text-primary"></i> 7. Outras Despesas da Viagem
                    </div>
                    <div class="row p-3 rounded" style="background: #fff3e0; border: 1px solid #ffcc80;">
                        <div class="col-12 text-right mb-2">
                            <button type="button" class="btn btn-warning btn-sm font-weight-bold shadow-sm" id="btn-add-despesa">
                                <i class="la la-plus"></i> Adicionar Despesa
                            </button>
                        </div>
                        <div class="col-12 table-responsive">
                            <table class="table table-bordered table-striped bg-white rounded" id="tabela_despesas">
                                <thead>
                                <tr class="bg-warning text-dark">
                                    <th width="25%">Tipo de Despesa</th>
                                    <th width="50%">Descrição / Motivo</th>
                                    <th width="15%">Valor (R$)</th>
                                    <th width="10%">Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($data) &&$data->despesas)
                                    @php foreach ($data->despesas as $key =>$desp): @endphp
                                    <tr id="row_desp_old_{{ $key }}">
                                        <td>
                                            <select name="despesas[old_{{ $key }}][tipo]" class="form-control">
                                                <option value="Pedágio" {{ $desp->tipo == 'Pedágio' ? 'selected' : '' }}>Pedágio</option>
                                                <option value="Alimentação" {{ $desp->tipo == 'Alimentação' ? 'selected' : '' }}>Alimentação</option>
                                                <option value="Pernoite" {{ $desp->tipo == 'Pernoite' ? 'selected' : '' }}>Pernoite</option>
                                                <option value="Outros" {{ $desp->tipo == 'Outros' ? 'selected' : '' }}>Outros</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="despesas[old_{{ $key }}][descricao]" class="form-control" value="{{ $desp->descricao }}"></td>
                                        <td><input type="text" name="despesas[old_{{ $key }}][valor]" class="form-control val-despesa" value="{{ number_format($desp->valor, 2, ',', '.') }}"></td>
                                        <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();"><i class="la la-trash"></i></button></td>
                                    </tr>
                                    @php endforeach; @endphp
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <label class="col-form-label font-weight-bold">Observações Gerais</label>
                        <textarea class="form-control" name="observacao" rows="3">{{ old('observacao', $data->observacao ?? '') }}</textarea>
                    </div>

                    <div class="card-footer mt-4 text-center">
                        <a class="btn btn-danger mr-2 font-weight-bold" href="{{ $actionCancel }}">
                            <i class="la la-close"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success font-weight-bold px-5">
                            <i class="la la-check"></i> Salvar Movimentação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TEMPLATES OCULTOS PARA O JAVASCRIPT -->
    <div id="templates-ocultos" style="display: none;">
        <select id="tpl-options-entidades">
            <option value="">Endereço Avulso...</option>
            <optgroup label="Clientes">
                @php foreach ($listaClientes as$c): @endphp
                <option value="cliente_{{ $c->id }}" data-lat="{{ $c->latitude }}" data-lon="{{ $c->longitude }}" data-end="{{ $c->rua ?? '' }}, {{ $c->numero ?? '' }} - {{ $c->bairro ?? '' }}">{{ $c->razao_social ?? $c->nome }}</option>
                @php endforeach; @endphp
            </optgroup>
            <optgroup label="Fornecedores">
                @php foreach ($listaFornecedores as$f): @endphp
                <option value="fornecedor_{{ $f->id }}" data-lat="{{ $f->latitude }}" data-lon="{{ $f->longitude }}" data-end="{{ $f->rua ?? '' }}, {{ $f->numero ?? '' }} - {{ $f->bairro ?? '' }}">[FOR] {{ $f->razao_social ?? $f->nome }}</option>
                @php endforeach; @endphp
            </optgroup>
        </select>

        <select id="tpl-options-caixas">
            <option value="">-- Nenhuma --</option>
            @php foreach ($listaCaixas as$cx): @endphp
            <option value="{{ $cx->id }}">{{ $cx->referencia ? $cx->referencia . ' - ' : '' }}{{$cx->nome }}</option>
            @php endforeach; @endphp
        </select>

        <select id="tpl-options-residuos">
            <option value="">-- Selecione o Material --</option>
            @php foreach ($listaProdutosResiduos as$pr): @endphp
            <option value="{{ $pr->id }}">{{ $pr->nome }}</option>
            @php endforeach; @endphp
        </select>

        <select id="tpl-options-combustiveis">
            <option value="">Selecione</option>
            @php foreach ($listaCombustiveis as$c): @endphp
            <option value="{{ $c->id }}" data-preco="{{ $c->valor_compra }}">{{ $c->nome }}</option>
            @php endforeach; @endphp
        </select>
    </div>
@endsection

@section('javascript')
    <script>
        var contRow = 1000;
        var contDesp = 2000;
        var destinoIndex = 5000;
        let residuoIndex = 10000;

        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            $('.select2-clientes').select2({ width: '100%' });
            reorganizarOrdem();

            if ($('#container-destinos .parada-box').length === 0) {
                // =========================================================
                // 🚀 INÍCIO DO NOVO BLOCO (Carrega Destino Sugerido)
                // =========================================================
                @if(isset($data) && !empty($data->coleta_origem_id))
                adicionarDestinoSugerido(
                    "{{ $data->destino_sugerido_id }}",
                    "{{ $data->destino_sugerido_endereco }}",
                    "{{ $data->destino_sugerido_peso }}",
                    "{{ $data->destino_sugerido_produto_id }}",
                    "{{ $data->destino_sugerido_caixa_id }}"
                );
                @else
                adicionarDestino();
                @endif
                // =========================================================
                // 🚀 FIM DO NOVO BLOCO
                // =========================================================
            } else {
                // Caso seja tela de Edição, garante que as paradas que já existiam mas não tinham resíduos tenham o botão pronto
                $('#container-destinos .parada-box').each(function() {
                    let destId = $(this).attr('id').replace('linha_destino_', '');
                    if($(`#container-residuos-${destId} .residuo-row`).length === 0) {
                        adicionarResiduoParada(destId);
                    }
                });
            }

            // 🚀 LÓGICA DO TERCEIRIZADO
            function alternarTerceirizado() {
                if ($('#is_terceirizado').is(':checked')) {
                    $('.bloco-frota-interna').hide();
                    $('.bloco-terceirizado').show();

                    // Remove obrigatório da frota interna
                    $('#veiculo_id').removeAttr('required');
                    $('#motorista_id').removeAttr('required');

                    // Exige os dados do terceiro se quiser gerar MTR (opcional, pode ajustar conforme a sua regra)
                    $('#terceiro_nome').attr('required', 'required');
                    $('#terceiro_telefone').attr('required', 'required');
                } else {
                    $('.bloco-terceirizado').hide();
                    $('.bloco-frota-interna').show();

                    // Repõe obrigatório na frota interna
                    $('#veiculo_id').attr('required', 'required');
                    $('#motorista_id').attr('required', 'required');

                    // Remove do terceiro
                    $('#terceiro_nome').removeAttr('required');
                    $('#terceiro_telefone').removeAttr('required');
                }
            }

            $('#is_terceirizado').on('change', function() {
                alternarTerceirizado();
            });

            // Executa no load da página
            alternarTerceirizado();

            calcTotalGeral();

            // =========================================================
            // 🚀 MOTOR DE AUTOPREENCHIMENTO DA BALANÇA
            // =========================================================
            $('#pesagem_id').on('change', function() {
                let pesagemId = $(this).val();

                if(!pesagemId) return;

                $(this).prop('disabled', true);
                let btnSalvar = $('button[type="submit"]');
                let textoOriginal = btnSalvar.html();
                btnSalvar.html('<i class="fas fa-spinner fa-spin"></i> A carregar ticket...').prop('disabled', true);

                $.ajax({
                    url: "{{ url('/movimentacaoVeiculo/dados-pesagem') }}/" + pesagemId,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {

                            // 1. Preenche Veículo
                            if(response.veiculo_id) {
                                $('#veiculo_id').val(response.veiculo_id).trigger('change');
                            }

                            // 2. Preenche Motorista
                            if(response.motorista_id) {
                                $('select[name="motorista_id"]').val(response.motorista_id).trigger('change');
                            }

                            // 3. Define a Hora de Saída
                            let now = new Date();
                            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
                            $('input[name="data_hora_saida"]').val(now.toISOString().slice(0,16));

                            // 4. Preenche o Destino e os Resíduos
                            let primeiraCaixa = $('#container-destinos .parada-box').first();

                            if(primeiraCaixa.length > 0) {
                                let destinoId = primeiraCaixa.attr('id').replace('linha_destino_', '');
                                let selectCliente = primeiraCaixa.find('.select2-clientes');

                                if (response.cliente_id && selectCliente.length > 0) {
                                    let valCliente = 'cliente_' + response.cliente_id;
                                    if(selectCliente.find('option[value="'+valCliente+'"]').length > 0) {
                                        selectCliente.val(valCliente).trigger('change');
                                    }
                                }
                                else if (response.fornecedor_id && selectCliente.length > 0) {
                                    let valFornecedor = 'fornecedor_' + response.fornecedor_id;
                                    if(selectCliente.find('option[value="'+valFornecedor+'"]').length > 0) {
                                        selectCliente.val(valFornecedor).trigger('change');
                                    }
                                }

                                // 5 e 6. 🚀 PREENCHE MÚLTIPLOS RESÍDUOS DIRETO DO TICKET
                                if(response.itens && response.itens.length > 0) {
                                    let containerResiduos = $(`#container-residuos-${destinoId}`);

                                    // Limpa os espaços vazios iniciais
                                    containerResiduos.empty();

                                    // Adiciona todos os itens mágicamente
                                    response.itens.forEach(item => {
                                        adicionarResiduoParada(destinoId, item.produto_id, item.quantidade);
                                    });
                                }
                            }

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                                    icon: 'success', title: 'Ticket carregado! Carga e destinos preenchidos.'
                                });
                            }
                        }
                    },
                    error: function() {
                        alert('Não foi possível ler os dados deste ticket. Tente novamente.');
                    },
                    complete: function() {
                        $('#pesagem_id').prop('disabled', false);
                        btnSalvar.html(textoOriginal).prop('disabled', false);
                    }
                });
            });
            // =========================================================

            // Ação corrigida: Tratar KM sem vírgulas para evitar bugs no banco
            $('#veiculo_id').on('change', function() {
                var km = $(this).find(':selected').data('km');
                if (km !== undefined && km !== null && km !== '') {
                    // Substitui vírgula por ponto e converte para número inteiro
                    var kmLimpo = Math.round(parseFloat(km.toString().replace(',', '.')));
                    $('#km_inicial').val(kmLimpo);
                }
            });

            $('#btn-add-abastecimento').click(function() {
                var opcoesComb = $('#tpl-options-combustiveis').html();
                var row = `
                <tr id="row_${contRow}">
                    <td>
                        <select name="abastecimentos[${contRow}][produto_id]" class="form-control select2-prod" required>
                            ${opcoesComb}
                        </select>
                    </td>
                    <td>
                        <select name="abastecimentos[${contRow}][tipo]" class="form-control">
                            <option value="externo">Externo</option>
                            <option value="interno">Interno</option>
                        </select>
                    </td>
                    <td><input type="date" name="abastecimentos[${contRow}][data]" class="form-control" value="{{ date('Y-m-d') }}" required></td>
                    <td><input type="number" name="abastecimentos[${contRow}][km_abastecimento]" class="form-control" placeholder="KM"></td>
                    <td><input type="number" step="0.001" name="abastecimentos[${contRow}][quantidade]" class="form-control qty" value="0.000"></td>
                    <td><input type="number" step="0.001" name="abastecimentos[${contRow}][unitario]" class="form-control unit" value="0.000"></td>
                    <td><input type="text" name="abastecimentos[${contRow}][total]" class="form-control total-row" readonly value="0,00"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(${contRow})"><i class="la la-trash"></i></button>
                    </td>
                </tr>`;
                $('#tabela_abastecimentos tbody').append(row);
                $('.select2-prod').last().select2({ width: '100%' });
                contRow++;
            });

            $('#btn-add-despesa').click(function() {
                var row = `
                <tr id="row_desp_${contDesp}">
                    <td>
                        <select name="despesas[${contDesp}][tipo]" class="form-control">
                            <option value="Pedágio">Pedágio</option>
                            <option value="Alimentação">Alimentação</option>
                            <option value="Pernoite">Pernoite</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </td>
                    <td><input type="text" name="despesas[${contDesp}][descricao]" class="form-control" placeholder="Ex: Pedágio BR-116"></td>
                    <td><input type="number" step="0.01" name="despesas[${contDesp}][valor]" class="form-control val-despesa" value="0.00"></td>
                    <td class="text-center"><button type="button" class="btn btn-danger btn-sm" onclick="$(this).closest('tr').remove();"><i class="la la-trash"></i></button></td>
                </tr>`;
                $('#tabela_despesas tbody').append(row);
                contDesp++;
            });
        });

        // =========================================================
        // 🚀 INÍCIO DAS NOVAS FUNÇÕES DOS RESÍDUOS
        // =========================================================

        // 🚀 FUNÇÃO NOVA: ADICIONA AS LINHAS DINÂMICAS DE RESÍDUOS
        function adicionarResiduoParada(destinoId, produtoId = '', quantidade = '') {
            residuoIndex++;
            let opcoesResiduos = $('#tpl-options-residuos').html();

            let qtdFormatada = quantidade;
            if(quantidade && !isNaN(quantidade)) {
                qtdFormatada = parseFloat(quantidade).toFixed(2).replace('.', ',');
            }

            let html = `
                <div class="row mb-2 residuo-row">
                    <div class="col-7">
                        <select name="destinos[${destinoId}][residuos][${residuoIndex}][produto_id]" class="form-control form-control-sm">
                            ${opcoesResiduos}
                        </select>
                    </div>
                    <div class="col-4">
                        <input type="text" name="destinos[${destinoId}][residuos][${residuoIndex}][quantidade]" class="form-control form-control-sm text-right" placeholder="Peso (KG)" value="${qtdFormatada}">
                    </div>
                    <div class="col-1 text-right">
                        <button type="button" class="btn btn-sm btn-icon btn-light-danger mt-1" onclick="$(this).closest('.residuo-row').remove();"><i class="la la-trash"></i></button>
                    </div>
                </div>
            `;
            $(`#container-residuos-${destinoId}`).append(html);

            if(produtoId) {
                $(`select[name="destinos[${destinoId}][residuos][${residuoIndex}][produto_id]"]`).val(produtoId);
            }
        }

        // 🚀 CORREÇÃO 1: Na função adicionarDestinoSugerido (usar novo formato)
        function adicionarDestinoSugerido(clienteId, endereco, peso, produtoId, caixaId) {
            adicionarDestino(); // Chama a função normal que cria a caixinha

            let linha = $('#linha_destino_' + destinoIndex);

            if(clienteId) {
                linha.find('.select2-clientes').val('cliente_' + clienteId).trigger('change');
            }
            if(endereco) {
                linha.find('.endereco-input').val(endereco);
            }
            if(caixaId) {
                linha.find('select[name$="[caixa_deixada_id]"]').val(caixaId);
                linha.find('select[name$="[tipo_operacao_caixa]"]').val('colocar');
            }
            // Preenche o(s) resíduo(s)
            if(produtoId || peso) {
                let destinoIdReal = linha.attr('id').replace('linha_destino_', '');
                $(`#container-residuos-${destinoIdReal}`).empty(); // Limpa o vazio default
                adicionarResiduoParada(destinoIdReal, produtoId, peso);
            }
        }

        function adicionarDestino() {
            destinoIndex++;

            let opcoesEntidades = $('#tpl-options-entidades').html();
            let opcoesCaixas = $('#tpl-options-caixas').html();

            let html = `
            <div class="parada-box" id="linha_destino_${destinoIndex}">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="font-weight-bolder text-dark font-size-h6">Parada #<span class="n-ordem"></span></span>
                    <button type="button" class="btn btn-sm btn-light-danger font-weight-bold py-1 px-3" onclick="removerDestino('${destinoIndex}')">
                        <i class="la la-trash"></i> Remover
                    </button>
                </div>
                <div class="row">
                    <div class="form-group col-lg-4 col-md-6 mb-3">
                        <label class="font-weight-bold small">Cliente ou Fornecedor</label>
                        <select class="form-control select2-clientes" name="destinos[${destinoIndex}][entidade_id]">
                            ${opcoesEntidades}
                        </select>
                        <div class="mt-3">
                            <label class="checkbox checkbox-success checkbox-sm">
                                <input type="checkbox" name="destinos[${destinoIndex}][is_aterro]" value="1">
                                <span></span> <b class="ml-2 text-success">É o Aterro / Destino Final?</b>
                            </label>
                        </div>
                    </div>

                    <div class="form-group col-lg-4 col-md-6 mb-3">
                        <label class="font-weight-bold small">Endereço P/ GPS</label>
                        <input type="text" class="form-control endereco-input mb-3" name="destinos[${destinoIndex}][endereco_completo]" placeholder="Endereço">
                        <input type="hidden" name="destinos[${destinoIndex}][latitude]" class="lat-input">
                        <input type="hidden" name="destinos[${destinoIndex}][longitude]" class="lon-input">

                        <label class="font-weight-bold small">Operação de Caçamba</label>
                        <select name="destinos[${destinoIndex}][tipo_operacao_caixa]" class="form-control">
                            <option value="nenhuma">Sem Caixa</option>
                            <option value="colocar">Deixar Caçamba</option>
                            <option value="retirar">Recolher Caçamba</option>
                            <option value="trocar">Trocar Caçamba</option>
                        </select>

                        <div class="mt-2">
                          <label class="font-weight-bold small text-primary">Cobrar Frete desta Parada?</label>
                          <select name="destinos[${destinoIndex}][cobrar_frete]" class="form-control">
                              <option value="1" selected>🟢 SIM (Faturar na Medição)</option>
                              <option value="0">🔴 NÃO (Incluso / Cortesia)</option>
                          </select>
                      </div>
                    </div>

                    <div class="form-group col-lg-4 col-md-12 mb-3">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="font-weight-bold small text-success">Caçamba Deixada</label>
                                <select name="destinos[${destinoIndex}][caixa_deixada_id]" class="form-control">
                                    ${opcoesCaixas}
                                </select>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="font-weight-bold small text-danger">Caçamba Retirada</label>
                                <select name="destinos[${destinoIndex}][caixa_retirada_id]" class="form-control">
                                    ${opcoesCaixas}
                                </select>
                            </div>
                        </div>

                        <!-- 🚀 NOVO: MULTIPLOS RESÍDUOS POR PARADA (GERADOS VIA JS) -->
                        <div class="mt-3 p-3 rounded" style="background-color: #f1f3f5; border: 1px dashed #ced4da;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold small text-primary m-0"><i class="fas fa-recycle"></i> Materiais / Resíduos da Parada</label>
                                <button type="button" class="btn btn-sm btn-light-primary py-1 px-2 font-weight-bold" onclick="adicionarResiduoParada('${destinoIndex}')">
                                    <i class="la la-plus"></i> Adicionar Material
                                </button>
                            </div>
                            <div id="container-residuos-${destinoIndex}">
                                <!-- Será populado pela função JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

            $('#container-destinos').append(html);
            $(`#linha_destino_${destinoIndex} .select2-clientes`).select2({ width: '100%' });
            adicionarResiduoParada(destinoIndex);
            reorganizarOrdem();
        }

        function removerDestino(id) {
            $(`#linha_destino_${id}`).remove();
            reorganizarOrdem();
        }

        function reorganizarOrdem() {
            $('#container-destinos .parada-box').each(function(index) {
                $(this).find('.n-ordem').text(index + 1);
            });
        }

        $(document).on('change', '.select2-clientes', function() {
            let opt = $(this).find('option:selected');
            let lat = opt.data('lat');
            let lon = opt.data('lon');
            let end = opt.data('end');
            let linha = $(this).closest('.parada-box');

            if(end && end.trim() !== '' && end.replace(/[\s,\-]/g, '') !== '') {
                linha.find('.endereco-input').val(end);
            }
            if(lat) linha.find('.lat-input').val(lat);
            if(lon) linha.find('.lon-input').val(lon);
        });

        // FUNÇÕES DE ABASTECIMENTO RESTAURADAS À FORMA ORIGINAL
        function removeRow(id) {
            $(`#row_${id}`).remove();
            calcTotalGeral();
        }

        $(document).on('change', '.select2-prod', function() {
            let preco = $(this).find(':selected').data('preco');
            $(this).closest('tr').find('.unit').val(preco);
            updateRowTotal($(this).closest('tr'));
        });

        $(document).on('keyup change', '.qty, .unit', function() {
            updateRowTotal($(this).closest('tr'));
        });

        function updateRowTotal(row) {
            let qty = parseFloat(row.find('.qty').val().replace(',', '.')) || 0;
            let unit = parseFloat(row.find('.unit').val().replace(',', '.')) || 0;
            let total = qty * unit;
            row.find('.total-row').val(total.toFixed(2).replace('.', ','));
            calcTotalGeral();
        }

        function calcTotalGeral() {
            let soma = 0;
            $('.total-row').each(function() {
                let val = $(this).val().replace(/\./g, '').replace(',', '.');
                soma += parseFloat(val) || 0;
            });
            $('#total_geral_abastecimento').val(soma.toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
        }
    </script>

@endsection
