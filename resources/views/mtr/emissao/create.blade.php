@extends('default.layout')

@section('content')
    <style>
        .form-control, .form-select, .select2-container--default .select2-selection--single {
            border: 1px solid #a1a8c3 !important;
            border-radius: 4px !important;
            min-height: 38px !important;
            background-color: #ffffff !important;
        }
        .border-box {
            border: 1px solid #cbd5e1 !important;
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .table-residuos th { background-color: #e2e8f0; font-weight: bold; }
        .bg-light-warning { background-color: #fff8dd !important; }
        .depara-status { font-size: 11px; margin-top: 3px; display: block; }
    </style>

    @php
        $produtosLista = $produtosComDepara ?? [];
    @endphp

    <div class="card card-custom gutter-b">
        <div class="card-header bg-primary py-3">
            <div class="card-title">
                <h3 class="card-label text-white font-weight-bolder">
                    <i class="fa fa-truck text-white mr-2"></i>
                    <i class="fa fa-recycle text-white mr-2"></i>
                    Emissão de MTR Nacional
                    @if(session('origem_tipo') == 'nfe')
                        <span class="badge badge-info ml-2">Importado da NF-e</span>
                    @elseif(session('origem_tipo') == 'ticket_pesagem')
                        <span class="badge badge-warning ml-2">Importado da Pesagem</span>
                    @else
                        <span class="badge badge-secondary ml-2">Preenchimento Manual</span>
                    @endif
                </h3>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('mtr.emissao.store') }}" method="POST" id="formMtr">
                @csrf

                <input type="hidden" name="origem_tipo" value="{{ session('origem_tipo') ?? 'avulso' }}">
                <input type="hidden" name="origem_id" value="{{ session('origem_id') ?? '' }}">

                <div class="border-box">
                    <h5 class="text-primary font-weight-bold mb-3"><span class="badge badge-primary mr-2">1</span> Identificação dos Envolvidos</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Unidade Geradora (Sua Empresa): <span class="text-danger">*</span></label>
                            <select name="gerador_id" class="form-control" required>
                                @if(count($unidadesGeradoras) > 1)
                                    <option value="">-- Selecione sua Unidade --</option>
                                @endif

                                @foreach($unidadesGeradoras as $unidade)
                                    <option value="{{ $unidade->id }}" data-cnpj="{{ $unidade->cpf_cnpj }}"
                                        {{ (session('gerador_id') == $unidade->id || old('gerador_id') == $unidade->id || count($unidadesGeradoras) == 1) ? 'selected' : '' }}>
                                        {{ $unidade->descricao ?: $unidade->orgao }} (CNPJ: {{ $unidade->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Transportador: <span class="text-danger">*</span></label>
                            <select name="transportador_cnpj" class="form-control select2" required>
                                <option value="">-- Selecione o Transportador --</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}" {{ (session('transportador_cnpj') == $c->cpf_cnpj || old('transportador_cnpj') == $c->cpf_cnpj) ? 'selected' : '' }}>
                                        {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Destinador Final / Recebedor: <span class="text-danger">*</span></label>
                            <select name="destinador_cnpj" class="form-control select2" required>
                                <option value="">-- Selecione o Destinador --</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}" {{ (session('destinador_cnpj') == $c->cpf_cnpj || old('destinador_cnpj') == $c->cpf_cnpj) ? 'selected' : '' }}>
                                        {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="border-box">
                    <h5 class="text-primary font-weight-bold mb-3"><span class="badge badge-primary mr-2">2</span> Dados do Motorista e Veículo</h5>

                    <!-- LISTAS OCULTAS PARA AUTOCOMPLETAR -->
                    <datalist id="listaMotoristas">
                        @if(isset($motoristasLista))
                            @foreach($motoristasLista as $mot)
                                <option value="{{ mb_strtoupper($mot->nome) }}">
                            @endforeach
                        @endif
                    </datalist>

                    <datalist id="listaPlacas">
                        @if(isset($veiculosLista))
                            @foreach($veiculosLista as $veic)
                                <option value="{{ mb_strtoupper($veic->placa) }}">
                            @endforeach
                        @endif
                    </datalist>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Nome do Motorista (Livre ou Busca): <span class="text-danger">*</span></label>
                            <input type="text" name="nome_motorista" id="inputNomeMotorista" list="listaMotoristas" class="form-control" autocomplete="off" placeholder="Digite livremente ou busque..." value="{{ session('motorista_nome') ?? old('motorista_nome') }}" required>
                            @if(isset($motoristasLista) && count($motoristasLista) > 0)
                                <select class="form-control form-control-sm mt-1 select-atalho-motorista" data-target="#inputNomeMotorista">
                                    <option value="">-- Selecionar do cadastro de motoristas --</option>
                                    @foreach($motoristasLista as $mot)
                                        <option value="{{ mb_strtoupper($mot->nome) }}">{{ mb_strtoupper($mot->nome) }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Placa do Veículo (Livre ou Busca): <span class="text-danger">*</span></label>
                            <input type="text" name="placa_veiculo" id="inputPlacaVeiculo" list="listaPlacas" class="form-control" autocomplete="off" placeholder="Digite livremente ou busque..." value="{{ session('placa_veiculo') ?? old('placa_veiculo') }}" required>
                            @if(isset($veiculosLista) && count($veiculosLista) > 0)
                                <select class="form-control form-control-sm mt-1 select-atalho-placa" data-target="#inputPlacaVeiculo">
                                    <option value="">-- Selecionar do cadastro de veículos --</option>
                                    @foreach($veiculosLista as $veic)
                                        <option value="{{ mb_strtoupper($veic->placa) }}">{{ mb_strtoupper($veic->placa) }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Data/Hora de Expedição: <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="data_expedicao" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                    </div>
                </div>

                <div class="border-box bg-light-warning">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="possui_armazenamento" id="possuiArmazenamento" value="1">
                        <label class="form-check-label font-weight-bold text-dark" for="possuiArmazenamento">
                            <i class="fa fa-warehouse mr-1"></i> Esta carga passará por Armazenamento Temporário?
                        </label>
                    </div>
                    <div class="row mt-3" id="divArmazenador" style="display: none;">
                        <div class="col-md-12 form-group mb-0">
                            <label class="font-weight-bold">Selecione o Armazenador Temporário (CNPJ):</label>
                            <select name="armazenador_cnpj" class="form-control select2" style="width: 100%;">
                                <option value="">-- Selecione o Armazenador --</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}">{{ $c->razao_social }} ({{ $c->cpf_cnpj }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="border-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-primary font-weight-bold mb-0">
                            <span class="badge badge-primary mr-2">3</span>
                            <i class="fa fa-recycle text-success mr-1"></i>
                            Carga e Resíduos
                        </h5>
                        <button type="button" class="btn btn-sm btn-info font-weight-bold" id="btnAddResiduo">
                            <i class="fa fa-plus"></i> Adicionar Resíduo
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-residuos" id="tabela-residuos">
                            <thead>
                            <tr>
                                <th style="width: 24%">Produto (De-Para) / Cód. IBAMA</th>
                                <th style="width: 11%">Qtd / Peso</th>
                                <th style="width: 14%">Unidade</th>
                                <th style="width: 14%">Estado Físico</th>
                                <th style="width: 16%">Acondicionamento</th>
                                <th style="width: 16%">Tratamento</th>
                                <th style="width: 5%">#</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(session('residuos_importados'))
                                @foreach(session('residuos_importados') as $index => $res)
                                    @php
                                        $codigoIbama = is_array($res) ? ($res['codigo_ibama'] ?? '') : ($res->codigo_ibama ?? '');
                                        $quantidade  = is_array($res) ? ($res['quantidade'] ?? '') : ($res->quantidade ?? '');
                                        $uniAtual    = is_array($res) ? ($res['unidade'] ?? 2) : ($res->unidade ?? 2);
                                        $fisicoAtual = is_array($res) ? ($res['estado_fisico'] ?? 1) : ($res->estado_fisico ?? 1);
                                        $acondAtual  = is_array($res) ? ($res['acondicionamento_id'] ?? 26) : ($res->acondicionamento_id ?? 26);
                                        $tratAtual   = is_array($res) ? ($res['tratamento_id'] ?? 43) : ($res->tratamento_id ?? 43);
                                        $claAtual    = is_array($res) ? ($res['classe_residuo'] ?? 43) : ($res->classe_residuo ?? 43);
                                    @endphp
                                    <tr id="linha-{{ $index }}">
                                        <td colspan="7" class="p-0">
                                            <div class="row m-0 p-2">
                                                <div class="col-md-3 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Produto (Puxa De-Para)</label>
                                                    <select class="form-control form-control-sm select-produto-depara" data-id="{{ $index }}">
                                                        <option value="">-- Informar IBAMA manual --</option>
                                                        @foreach($produtosLista as $prod)
                                                            <option value="{{ $prod['id'] }}"
                                                                    data-ibama="{{ $prod['cod_ibama'] }}"
                                                                    data-unidade="{{ $prod['unidade_medida'] }}"
                                                                    data-estado="{{ $prod['estado_fisico'] }}"
                                                                    data-acond="{{ $prod['acondicionamento_id'] }}"
                                                                    data-trat="{{ $prod['tratamento_id'] }}"
                                                                    data-classe="{{ $prod['classe_residuo'] }}"
                                                                    data-onu="{{ $prod['numero_onu'] }}"
                                                                    data-risco="{{ $prod['classe_risco'] }}"
                                                                    data-embarque="{{ $prod['nome_embarque'] }}"
                                                                    data-grupo="{{ $prod['grupo_embalagem'] }}"
                                                                    data-tem-depara="{{ $prod['tem_depara'] ? '1' : '0' }}">
                                                                {{ $prod['nome'] }} {{ $prod['tem_depara'] && $prod['cod_ibama'] ? '(' . $prod['cod_ibama'] . ')' : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="depara-status text-muted" id="depara-msg-{{ $index }}">Selecione o produto para preencher os parâmetros.</small>
                                                </div>

                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Cód. IBAMA <span class="text-danger">*</span></label>
                                                    <input type="text" name="residuos[{{ $index }}][codigo_ibama]" id="ibama-{{ $index }}" class="form-control form-control-sm" value="{{ $codigoIbama }}" placeholder="Ex: 191202" required>
                                                </div>

                                                <div class="col-md-1 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Qtd/Peso <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.0001" name="residuos[{{ $index }}][quantidade]" id="qtd-{{ $index }}" class="form-control form-control-sm font-weight-bold text-primary" value="{{ $quantidade }}" required>
                                                </div>

                                                <div class="col-md-1 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Unidade</label>
                                                    <select name="residuos[{{ $index }}][unidade]" id="unidade-{{ $index }}" class="form-control form-control-sm" required>
                                                        <option value="2" {{ $uniAtual == 2 ? 'selected' : '' }}>KG</option>
                                                        <option value="3" {{ $uniAtual == 3 ? 'selected' : '' }}>TON</option>
                                                        <option value="20" {{ $uniAtual == 20 ? 'selected' : '' }}>M³</option>
                                                        <option value="21" {{ $uniAtual == 21 ? 'selected' : '' }}>LT</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-1 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Est. Físico</label>
                                                    <select name="residuos[{{ $index }}][estado_fisico]" id="estado-{{ $index }}" class="form-control form-control-sm" required>
                                                        <option value="4" {{ $fisicoAtual == 4 ? 'selected' : '' }}>Sólido</option>
                                                        <option value="2" {{ $fisicoAtual == 2 ? 'selected' : '' }}>Líquido</option>
                                                        <option value="1" {{ $fisicoAtual == 1 ? 'selected' : '' }}>Semissólido</option>
                                                        <option value="3" {{ $fisicoAtual == 3 ? 'selected' : '' }}>Gasoso</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Acondicionamento</label>
                                                    <select name="residuos[{{ $index }}][acondicionamento_id]" id="acond-{{ $index }}" class="form-control form-control-sm" required>
                                                        <option value="26" {{ $acondAtual == 26 ? 'selected' : '' }}>Big Bag</option>
                                                        <option value="4"  {{ $acondAtual == 4 ? 'selected' : '' }}>Caçamba aberta</option>
                                                        <option value="5"  {{ $acondAtual == 5 ? 'selected' : '' }}>Caçamba fechada</option>
                                                        <option value="21" {{ $acondAtual == 21 ? 'selected' : '' }}>Caixa</option>
                                                        <option value="22" {{ $acondAtual == 22 ? 'selected' : '' }}>Caixa de papelão</option>
                                                        <option value="23" {{ $acondAtual == 23 ? 'selected' : '' }}>Cilindro</option>
                                                        <option value="3"  {{ $acondAtual == 3 ? 'selected' : '' }}>Contêiner</option>
                                                        <option value="24" {{ $acondAtual == 24 ? 'selected' : '' }}>Fardo</option>
                                                        <option value="8"  {{ $acondAtual == 8 ? 'selected' : '' }}>Granel</option>
                                                        <option value="13" {{ $acondAtual == 13 ? 'selected' : '' }}>Outros</option>
                                                        <option value="25" {{ $acondAtual == 25 ? 'selected' : '' }}>Palete</option>
                                                        <option value="2"  {{ $acondAtual == 2 ? 'selected' : '' }}>Saco plástico</option>
                                                        <option value="9"  {{ $acondAtual == 9 ? 'selected' : '' }}>Tambor</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-1 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Tratamento</label>
                                                    <select name="residuos[{{ $index }}][tratamento_id]" id="trat-{{ $index }}" class="form-control form-control-sm" required>
                                                        <option value="51" {{ $tratAtual == 51 ? 'selected' : '' }}>Aterro de Reservaçaõ - RCC</option>
                                                        <option value="4"  {{ $tratAtual == 4 ? 'selected' : '' }}>Aterro Resíduos Classe I</option>
                                                        <option value="17" {{ $tratAtual == 17 ? 'selected' : '' }}>Aterro Resíduos Classes IIA e IIB</option>
                                                        <option value="52" {{ $tratAtual == 52 ? 'selected' : '' }}>Aterro Sanitário - RSU</option>
                                                        <option value="25" {{ $tratAtual == 25 ? 'selected' : '' }}>Autoclave</option>
                                                        <option value="30" {{ $tratAtual == 30 ? 'selected' : '' }}>Barragem de Rejeitos</option>
                                                        <option value="27" {{ $tratAtual == 27 ? 'selected' : '' }}>Biodigestão</option>
                                                        <option value="31" {{ $tratAtual == 31 ? 'selected' : '' }}>Biometanização</option>
                                                        <option value="32" {{ $tratAtual == 32 ? 'selected' : '' }}>Biorremediação</option>
                                                        <option value="29" {{ $tratAtual == 29 ? 'selected' : '' }}>Blendagem para Coprocessamento</option>
                                                        <option value="73" {{ $tratAtual == 73 ? 'selected' : '' }}>Blendagem para Incineração</option>
                                                        <option value="50" {{ $tratAtual == 50 ? 'selected' : '' }}>Compostagem</option>
                                                        <option value="60" {{ $tratAtual == 60 ? 'selected' : '' }}>Coprocessamento</option>
                                                        <option value="33" {{ $tratAtual == 33 ? 'selected' : '' }}>Desativação de Fosfina</option>
                                                        <option value="24" {{ $tratAtual == 24 ? 'selected' : '' }}>Descontaminação de Lâmpadas</option>
                                                        <option value="28" {{ $tratAtual == 28 ? 'selected' : '' }}>Desmontagem de Veículos</option>
                                                        <option value="34" {{ $tratAtual == 34 ? 'selected' : '' }}>Disposição em Cava de Mineração</option>
                                                        <option value="36" {{ $tratAtual == 36 ? 'selected' : '' }}>Estação de Transbordo - RSU</option>
                                                        <option value="72" {{ $tratAtual == 72 ? 'selected' : '' }}>Fertirrigação</option>
                                                        <option value="38" {{ $tratAtual == 38 ? 'selected' : '' }}>Fins Didáticos/Pesquisa</option>
                                                        <option value="39" {{ $tratAtual == 39 ? 'selected' : '' }}>Gaseificação</option>
                                                        <option value="26" {{ $tratAtual == 26 ? 'selected' : '' }}>Incineração</option>
                                                        <option value="40" {{ $tratAtual == 40 ? 'selected' : '' }}>Microondas</option>
                                                        <option value="53" {{ $tratAtual == 53 ? 'selected' : '' }}>Outros</option>
                                                        <option value="41" {{ $tratAtual == 41 ? 'selected' : '' }}>Pilha de Estéril</option>
                                                        <option value="42" {{ $tratAtual == 42 ? 'selected' : '' }}>Pirólise</option>
                                                        <option value="43" {{ $tratAtual == 43 ? 'selected' : '' }}>Reciclagem</option>
                                                        <option value="45" {{ $tratAtual == 45 ? 'selected' : '' }}>Recuperação Energética</option>
                                                        <option value="44" {{ $tratAtual == 44 ? 'selected' : '' }}>Rerrefino</option>
                                                        <option value="23" {{ $tratAtual == 23 ? 'selected' : '' }}>Tratamento de Efluentes</option>
                                                        <option value="49" {{ $tratAtual == 49 ? 'selected' : '' }}>Tratamento Térmico - outros</option>
                                                        <option value="46" {{ $tratAtual == 46 ? 'selected' : '' }}>Triagem com Armazenamento</option>
                                                        <option value="61" {{ $tratAtual == 61 ? 'selected' : '' }}>Triagem e Transbordo</option>
                                                        <option value="47" {{ $tratAtual == 47 ? 'selected' : '' }}>Uso Agrícola</option>
                                                        <option value="48" {{ $tratAtual == 48 ? 'selected' : '' }}>Uso Alimentação Animal</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-1 p-1 text-center align-self-end">
                                                    <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-linha" data-id="{{ $index }}">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row m-0 p-2 border-top bg-light">
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Classe do Resíduo <span class="text-danger">*</span></label>
                                                    <select name="residuos[{{ $index }}][classe_residuo]" id="classe-{{ $index }}" class="form-control form-control-sm select-classe" data-id="{{ $index }}" required>
                                                        <option value="1"  {{ $claAtual == 1  ? 'selected' : '' }}>CLASSE I (Perigoso)</option>
                                                        <option value="43" {{ $claAtual == 43 ? 'selected' : '' }}>CLASSE II A (Não Inerte)</option>
                                                        <option value="42" {{ $claAtual == 42 ? 'selected' : '' }}>CLASSE II B (Inerte)</option>
                                                        <option value="2"  {{ $claAtual == 2  ? 'selected' : '' }}>OUTROS</option>
                                                        <option value="11" {{ $claAtual == 11 ? 'selected' : '' }}>CLASSE A (RCC)</option>
                                                        <option value="12" {{ $claAtual == 12 ? 'selected' : '' }}>CLASSE B (RCC)</option>
                                                        <option value="13" {{ $claAtual == 13 ? 'selected' : '' }}>CLASSE C (RCC)</option>
                                                        <option value="14" {{ $claAtual == 14 ? 'selected' : '' }}>CLASSE D (RCC)</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Número ONU</label>
                                                    <input type="text" name="residuos[{{ $index }}][numero_onu]" id="onu-{{ $index }}" class="form-control form-control-sm" value="{{ is_array($res) ? ($res['numero_onu'] ?? '') : ($res->numero_onu ?? '') }}" placeholder="Ex: 1234">
                                                </div>
                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Classe de Risco</label>
                                                    <input type="text" name="residuos[{{ $index }}][classe_risco]" id="risco-{{ $index }}" class="form-control form-control-sm" value="{{ is_array($res) ? ($res['classe_risco'] ?? '') : ($res->classe_risco ?? '') }}" placeholder="Ex: 3">
                                                </div>
                                                <div class="col-md-3 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Nome de Embarque</label>
                                                    <input type="text" name="residuos[{{ $index }}][nome_embarque]" id="embarque-{{ $index }}" class="form-control form-control-sm" value="{{ is_array($res) ? ($res['nome_embarque'] ?? '') : ($res->nome_embarque ?? '') }}" placeholder="Nome apropriado">
                                                </div>
                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Grupo Embalagem</label>
                                                    <select name="residuos[{{ $index }}][grupo_embalagem]" id="grupo-{{ $index }}" class="form-control form-control-sm">
                                                        <option value="">Selecione</option>
                                                        @php $ge = is_array($res) ? ($res['grupo_embalagem'] ?? '') : ($res->grupo_embalagem ?? ''); @endphp
                                                        <option value="I" {{ $ge == 'I' ? 'selected' : '' }}>I</option>
                                                        <option value="II" {{ $ge == 'II' ? 'selected' : '' }}>II</option>
                                                        <option value="III" {{ $ge == 'III' ? 'selected' : '' }}>III</option>
                                                        <option value="NA" {{ $ge == 'NA' ? 'selected' : '' }}>N/A</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label class="font-weight-bold">Observações (Impressas no MTR):</label>
                    <textarea name="observacao" class="form-control" rows="3" placeholder="Informações adicionais da carga...">{{ session('observacao_importada') ?? '' }}</textarea>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('mtr.emissao.index') }}" class="btn btn-secondary font-weight-bold">
                        <i class="fa fa-arrow-left"></i> Voltar
                    </a>
                    <div>
                        <button type="submit" name="acao" value="rascunho" class="btn btn-warning font-weight-bold mr-2">
                            <i class="fa fa-save"></i> Salvar Rascunho
                        </button>
                        <button type="submit" name="acao" value="transmitir" class="btn btn-success font-weight-bold px-5">
                            <i class="fa fa-paper-plane"></i> Emitir MTR (SINIR)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        var PRODUTOS_DEPARA = @json($produtosLista);

        function montarOptionsProdutos() {
            var opts = '<option value="">-- Informar IBAMA manual --</option>';
            if (Array.isArray(PRODUTOS_DEPARA)) {
                PRODUTOS_DEPARA.forEach(function(p) {
                    var rotulo = p.nome + (p.tem_depara && p.cod_ibama ? ' (' + p.cod_ibama + ')' : '');
                    opts += `<option value="${p.id}"
                    data-ibama="${p.cod_ibama || ''}"
                    data-unidade="${p.unidade_medida || 2}"
                    data-estado="${p.estado_fisico || 1}"
                    data-acond="${p.acondicionamento_id || 8}"
                    data-trat="${p.tratamento_id || 43}"
                    data-classe="${p.classe_residuo || 43}"
                    data-onu="${p.numero_onu || ''}"
                    data-risco="${p.classe_risco || ''}"
                    data-embarque="${p.nome_embarque || ''}"
                    data-grupo="${p.grupo_embalagem || ''}"
                    data-tem-depara="${p.tem_depara ? '1' : '0'}">${rotulo}</option>`;
                });
            }
            return opts;
        }

        function iniciarScriptEmissaoMTR() {
            if (typeof window.jQuery === 'undefined') {
                setTimeout(iniciarScriptEmissaoMTR, 100);
                return;
            }

            var $ = window.jQuery;

            $(document).ready(function() {
                if ($.fn.select2) {
                    $('.select2').select2({ width: '100%', allowClear: true });
                }

                // Atalhos de Motorista e Placa (permitem digitar livremente ou puxar do cadastro)
                $(document).on('change', '.select-atalho-motorista', function() {
                    var val = $(this).val();
                    var target = $(this).data('target');
                    if (val && target) {
                        $(target).val(val).trigger('change');
                    }
                });

                $(document).on('change', '.select-atalho-placa', function() {
                    var val = $(this).val();
                    var target = $(this).data('target');
                    if (val && target) {
                        $(target).val(val).trigger('change');
                    }
                });

                // Seleção de Produto -> Puxa parametrização De-Para e foca na quantidade/peso
                $(document).on('change', '.select-produto-depara', function() {
                    var idx = $(this).data('id');
                    var $opt = $(this).find('option:selected');
                    var $msg = $('#depara-msg-' + idx);

                    if (!$(this).val()) {
                        $msg.removeClass('text-success text-warning').addClass('text-muted').text('Preenchimento manual.');
                        return;
                    }

                    var ibama    = $opt.data('ibama');
                    var unidade  = $opt.data('unidade');
                    var estado   = $opt.data('estado');
                    var acond    = $opt.data('acond');
                    var trat     = $opt.data('trat');
                    var classe   = $opt.data('classe');
                    var temDepara = $opt.data('tem-depara') == '1';

                    if (ibama)   $('#ibama-' + idx).val(ibama);
                    if (unidade) $('#unidade-' + idx).val(unidade);
                    if (estado)  $('#estado-' + idx).val(estado);
                    if (acond)   $('#acond-' + idx).val(acond);
                    if (trat)    $('#trat-' + idx).val(trat);
                    if (classe)  $('#classe-' + idx).val(classe).trigger('change');

                    if (classe == 1) {
                        $('#onu-' + idx).val($opt.data('onu') || '');
                        $('#risco-' + idx).val($opt.data('risco') || '');
                        $('#embarque-' + idx).val($opt.data('embarque') || '');
                        $('#grupo-' + idx).val($opt.data('grupo') || '');
                    }

                    if (temDepara && ibama) {
                        $msg.removeClass('text-muted text-warning').addClass('text-success').html('<i class="fa fa-check-circle"></i> De-Para aplicado! Informe apenas a Qtd/Peso.');
                    } else {
                        $msg.removeClass('text-muted text-success').addClass('text-warning').html('<i class="fa fa-exclamation-circle"></i> Sem De-Para vinculado. Informe o IBAMA e a Qtd/Peso.');
                    }

                    $('#qtd-' + idx).focus();
                });

                $('#possuiArmazenamento').change(function() {
                    if ($(this).is(':checked')) {
                        $('#divArmazenador').slideDown();
                    } else {
                        $('#divArmazenador').slideUp();
                        $('select[name="armazenador_cnpj"]').val('').trigger('change');
                    }
                });

                var itemIndex = $('#tabela-residuos tbody tr').length;
                var optionsProdutosHtml = montarOptionsProdutos();

                $('#btnAddResiduo').on('click', function() {
                    var html = `
                    <tr id="linha-${itemIndex}">
                        <td colspan="7" class="p-0">
                            <div class="row m-0 p-2">
                                <div class="col-md-3 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Produto (Puxa De-Para)</label>
                                    <select class="form-control form-control-sm select-produto-depara" data-id="${itemIndex}">
                                        ${optionsProdutosHtml}
                                    </select>
                                    <small class="depara-status text-muted" id="depara-msg-${itemIndex}">Selecione o produto para preencher os parâmetros.</small>
                                </div>
                                <div class="col-md-2 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Cód. IBAMA <span class="text-danger">*</span></label>
                                    <input type="text" name="residuos[${itemIndex}][codigo_ibama]" id="ibama-${itemIndex}" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-1 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Qtd/Peso <span class="text-danger">*</span></label>
                                    <input type="number" step="0.0001" name="residuos[${itemIndex}][quantidade]" id="qtd-${itemIndex}" class="form-control form-control-sm font-weight-bold text-primary" required>
                                </div>
                                <div class="col-md-1 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Unidade</label>
                                    <select name="residuos[${itemIndex}][unidade]" id="unidade-${itemIndex}" class="form-control form-control-sm" required>
                                        <option value="2">KG</option>
                                        <option value="3">TON</option>
                                        <option value="20">M³</option>
                                        <option value="21">LT</option>
                                    </select>
                                </div>
                                <div class="col-md-1 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Est. Físico</label>
                                    <select name="residuos[${itemIndex}][estado_fisico]" id="estado-${itemIndex}" class="form-control form-control-sm" required>
                                        <option value="4">Sólido</option>
                                        <option value="2">Líquido</option>
                                        <option value="1">Semissólido</option>
                                        <option value="3">Gasoso</option>
                                    </select>
                                </div>
                                <div class="col-md-2 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Acondicionamento</label>
                                    <select name="residuos[${itemIndex}][acondicionamento_id]" id="acond-${itemIndex}" class="form-control form-control-sm" required>
                                        <option value="26">Big Bag</option>
                                        <option value="4">Caçamba aberta</option>
                                        <option value="5">Caçamba fechada</option>
                                        <option value="21">Caixa</option>
                                        <option value="22">Caixa de papelão</option>
                                        <option value="23">Cilindro</option>
                                        <option value="3">Contêiner</option>
                                        <option value="24">Fardo</option>
                                        <option value="8">Granel</option>
                                        <option value="13">Outros</option>
                                        <option value="25">Palete</option>
                                        <option value="2">Saco plástico</option>
                                        <option value="9">Tambor</option>
                                    </select>
                                </div>
                                <div class="col-md-1 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Tratamento</label>
                                    <select name="residuos[${itemIndex}][tratamento_id]" id="trat-${itemIndex}" class="form-control form-control-sm" required>
                                        <option value="51">Aterro de Reservaçaõ - RCC</option>
                                        <option value="4">Aterro Resíduos Classe I</option>
                                        <option value="17">Aterro Resíduos Classes IIA e IIB</option>
                                        <option value="52">Aterro Sanitário - RSU</option>
                                        <option value="25">Autoclave</option>
                                        <option value="30">Barragem de Rejeitos</option>
                                        <option value="27">Biodigestão</option>
                                        <option value="31">Biometanização</option>
                                        <option value="32">Biorremediação</option>
                                        <option value="29">Blendagem para Coprocessamento</option>
                                        <option value="73">Blendagem para Incineração</option>
                                        <option value="50">Compostagem</option>
                                        <option value="60">Coprocessamento</option>
                                        <option value="33">Desativação de Fosfina</option>
                                        <option value="24">Descontaminação de Lâmpadas</option>
                                        <option value="28">Desmontagem de Veículos</option>
                                        <option value="34">Disposição em Cava de Mineração</option>
                                        <option value="36">Estação de Transbordo - RSU</option>
                                        <option value="72">Fertirrigação</option>
                                        <option value="38">Fins Didáticos/Pesquisa</option>
                                        <option value="39">Gaseificação</option>
                                        <option value="26">Incineração</option>
                                        <option value="40">Microondas</option>
                                        <option value="53">Outros</option>
                                        <option value="41">Pilha de Estéril</option>
                                        <option value="42">Pirólise</option>
                                        <option value="43" selected>Reciclagem</option>
                                        <option value="45">Recuperação Energética</option>
                                        <option value="44">Rerrefino</option>
                                        <option value="23">Tratamento de Efluentes</option>
                                        <option value="49">Tratamento Térmico - outros</option>
                                        <option value="46">Triagem com Armazenamento</option>
                                        <option value="61">Triagem e Transbordo</option>
                                        <option value="47">Uso Agrícola</option>
                                        <option value="48">Uso Alimentação Animal</option>
                                    </select>
                                </div>
                                <div class="col-md-1 p-1 text-center align-self-end">
                                    <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-linha" data-id="${itemIndex}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="row m-0 p-2 border-top bg-light">
                                <div class="col-md-2 p-1">
                                    <label class="font-weight-bold" style="font-size: 10px;">Classe do Resíduo <span class="text-danger">*</span></label>
                                    <select name="residuos[${itemIndex}][classe_residuo]" id="classe-${itemIndex}" class="form-control form-control-sm select-classe" data-id="${itemIndex}" required>
                                        <option value="1">CLASSE I (Perigoso)</option>
                                        <option value="43" selected>CLASSE II A (Não Inerte)</option>
                                        <option value="42">CLASSE II B (Inerte)</option>
                                        <option value="2">OUTROS</option>
                                        <option value="11">CLASSE A (RCC)</option>
                                        <option value="12">CLASSE B (RCC)</option>
                                        <option value="13">CLASSE C (RCC)</option>
                                        <option value="14">CLASSE D (RCC)</option>
                                    </select>
                                </div>
                                <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                                    <label class="font-weight-bold" style="font-size: 10px;">Número ONU</label>
                                    <input type="text" name="residuos[${itemIndex}][numero_onu]" id="onu-${itemIndex}" class="form-control form-control-sm" placeholder="Ex: 1234">
                                </div>
                                <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                                    <label class="font-weight-bold" style="font-size: 10px;">Classe de Risco</label>
                                    <input type="text" name="residuos[${itemIndex}][classe_risco]" id="risco-${itemIndex}" class="form-control form-control-sm" placeholder="Ex: 3">
                                </div>
                                <div class="col-md-3 p-1 div-perigoso-${itemIndex}" style="display: none;">
                                    <label class="font-weight-bold" style="font-size: 10px;">Nome de Embarque</label>
                                    <input type="text" name="residuos[${itemIndex}][nome_embarque]" id="embarque-${itemIndex}" class="form-control form-control-sm" placeholder="Nome apropriado">
                                </div>
                                <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                                    <label class="font-weight-bold" style="font-size: 10px;">Grupo Embalagem</label>
                                    <select name="residuos[${itemIndex}][grupo_embalagem]" id="grupo-${itemIndex}" class="form-control form-control-sm">
                                        <option value="">Selecione</option>
                                        <option value="I">I</option>
                                        <option value="II">II</option>
                                        <option value="III">III</option>
                                        <option value="NA">N/A</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                    $('#tabela-residuos tbody').append(html);
                    itemIndex++;
                });

                $(document).on('click', '.btn-remover-linha', function() {
                    var id = $(this).data('id');
                    $('#linha-' + id).remove();
                });

                $(document).on('change', '.select-classe', function() {
                    var id = $(this).data('id');
                    if ($(this).val() === '1') {
                        $('.div-perigoso-' + id).show();
                    } else {
                        $('.div-perigoso-' + id).hide();
                        $('.div-perigoso-' + id + ' input, .div-perigoso-' + id + ' select').val('');
                    }
                });

                if ($('#tabela-residuos tbody tr').length === 0) {
                    $('#btnAddResiduo').trigger('click');
                }
            });
        }

        iniciarScriptEmissaoMTR();
    </script>
@endsection
