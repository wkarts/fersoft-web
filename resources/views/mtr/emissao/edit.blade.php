@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-document-fiscal.css') }}?v={{ filemtime(public_path('css/ui-document-fiscal.css')) }}">
@endpush

@section('content')
    <style>
        .form-control, .form-select, .select2-container--default .select2-selection--single {
            border: 1px solid #a1a8c3 !important; border-radius: 4px !important; min-height: 38px !important; background-color: #ffffff !important;
        }
        .border-box {
            border: 1px solid #cbd5e1 !important; background-color: #f8fafc; border-radius: 6px; padding: 18px; margin-bottom: 20px;
        }
        .table-residuos th { background-color: #e2e8f0; font-weight: bold; }
        .bg-light-warning { background-color: #fff8dd !important; }
    </style>

    <div class="document-fiscal-page document-fiscal-page--mtr card card-custom gutter-b">
        <div class="card-header bg-warning py-3">
            <div class="card-title">
                <h3 class="card-label text-dark font-weight-bolder">
                    <i class="fa fa-edit text-dark mr-2"></i> Editar Rascunho de MTR
                </h3>
            </div>
        </div>

        <div class="card-body">
            <form action="{{ route('mtr.emissao.update', $mtr->id) }}" method="POST" id="formMtr">
                @csrf
                @method('PUT')

                <!-- BLOCO 1: IDENTIFICAÇÃO -->
                <div class="border-box">
                    <h5 class="text-primary font-weight-bold mb-3"><span class="badge badge-primary mr-2">1</span> Identificação dos Envolvidos</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Unidade Geradora: <span class="text-danger">*</span></label>
                            <select name="gerador_id" class="form-control" required>
                                @foreach($unidadesGeradoras as $unidade)
                                    <option value="{{ $unidade->id }}" {{ preg_replace('/\D/', '', $unidade->cpf_cnpj) == $mtr->gerador_cnpj ? 'selected' : '' }}>
                                        {{ $unidade->descricao ?: $unidade->orgao }} (CNPJ: {{ $unidade->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Transportador: <span class="text-danger">*</span></label>
                            <select name="transportador_cnpj" class="form-control select2" required>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}" {{ preg_replace('/\D/', '', $c->cpf_cnpj) == $mtr->transportador_cnpj ? 'selected' : '' }}>
                                        {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Destinador Final: <span class="text-danger">*</span></label>
                            <select name="destinador_cnpj" class="form-control select2" required>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}" {{ preg_replace('/\D/', '', $c->cpf_cnpj) == $mtr->destinador_cnpj ? 'selected' : '' }}>
                                        {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- BLOCO 2: TRANSPORTE -->
                <div class="border-box">
                    <h5 class="text-primary font-weight-bold mb-3"><span class="badge badge-primary mr-2">2</span> Dados do Transporte</h5>

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
                            <label class="font-weight-bold">Motorista: <span class="text-danger">*</span></label>
                            <input type="text" name="nome_motorista" list="listaMotoristas" class="form-control" autocomplete="off" value="{{ $mtr->motorista_nome }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Placa do Veículo: <span class="text-danger">*</span></label>
                            <input type="text" name="placa_veiculo" list="listaPlacas" class="form-control" autocomplete="off" value="{{ $mtr->veiculo_placa }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Data/Hora de Expedição: <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="data_expedicao" class="form-control" value="{{ date('Y-m-d\TH:i', strtotime($mtr->data_expedicao)) }}" required>
                        </div>
                    </div>
                </div>

                <!-- ARMAZENADOR TEMPORÁRIO -->
                <div class="border-box bg-light-warning">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="possui_armazenamento" id="possuiArmazenamento" value="1" {{ $mtr->armazenador_cnpj ? 'checked' : '' }}>
                        <label class="form-check-label font-weight-bold text-dark" for="possuiArmazenamento">
                            <i class="fa fa-warehouse mr-1"></i> Esta carga passará por Armazenamento Temporário?
                        </label>
                    </div>
                    <div class="row mt-3" id="divArmazenador" style="display: {{ $mtr->armazenador_cnpj ? 'flex' : 'none' }};">
                        <div class="col-md-12 form-group mb-0">
                            <label class="font-weight-bold">Selecione o Armazenador Temporário:</label>
                            <select name="armazenador_cnpj" class="form-control select2" style="width: 100%;">
                                <option value="">-- Selecione --</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->cpf_cnpj }}" {{ preg_replace('/\D/', '', $c->cpf_cnpj) == $mtr->armazenador_cnpj ? 'selected' : '' }}>
                                        {{ $c->razao_social }} ({{ $c->cpf_cnpj }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- BLOCO 3: RESÍDUOS -->
                <div class="border-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-primary font-weight-bold mb-0"><span class="badge badge-primary mr-2">3</span> Resíduos</h5>
                        <button type="button" class="btn btn-sm btn-info font-weight-bold" id="btnAddResiduo"><i class="fa fa-plus"></i> Adicionar Resíduo</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-residuos" id="tabela-residuos">
                            <thead>
                            <tr>
                                <th style="width: 20%">Cód. IBAMA / Resíduo</th>
                                <th style="width: 10%">Qtd</th>
                                <th style="width: 15%">Unidade</th>
                                <th style="width: 15%">Estado Físico</th>
                                <th style="width: 15%">Acondicionamento</th>
                                <th style="width: 15%">Tratamento</th>
                                <th style="width: 5%">#</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(isset($itens))
                                @foreach($itens as $index => $item)
                                    @php
                                        $claAtual = $item->classe_residuo ?? $item->classe ?? 43;
                                    @endphp
                                    <tr id="linha-{{ $index }}">
                                        <td colspan="7" class="p-0">
                                            <div class="row m-0 p-2">
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Cód. IBAMA</label>
                                                    <input type="text" name="residuos[{{ $index }}][codigo_ibama]" class="form-control form-control-sm" value="{{ $item->cod_ibama }}" required>
                                                </div>
                                                <div class="col-md-1 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Qtd</label>
                                                    <input type="number" step="0.0001" name="residuos[{{ $index }}][quantidade]" class="form-control form-control-sm" value="{{ $item->quantidade }}" required>
                                                </div>
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Unidade</label>
                                                    <select name="residuos[{{ $index }}][unidade]" class="form-control form-control-sm" required>
                                                        @php $uniAtual = $item->unidade_medida ?? $item->unidade ?? 2; @endphp
                                                        <option value="2" {{ $uniAtual == 2 ? 'selected' : '' }}>Quilograma (kg)</option>
                                                        <option value="3" {{ $uniAtual == 3 ? 'selected' : '' }}>Tonelada (t)</option>
                                                        <option value="20" {{ $uniAtual == 20 ? 'selected' : '' }}>Metro cúbico (m³)</option>
                                                        <option value="21" {{ $uniAtual == 21 ? 'selected' : '' }}>Litro (L)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Estado Físico</label>
                                                    <select name="residuos[{{ $index }}][estado_fisico]" class="form-control form-control-sm" required>
                                                        @php $estAtual = $item->estado_fisico ?? 4; @endphp
                                                        <option value="4" {{ $estAtual == 4 ? 'selected' : '' }}>Sólido</option>
                                                        <option value="2" {{ $estAtual == 2 ? 'selected' : '' }}>Líquido</option>
                                                        <option value="1" {{ $estAtual == 1 ? 'selected' : '' }}>Semissólido</option>
                                                        <option value="3" {{ $estAtual == 3 ? 'selected' : '' }}>Gasoso</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Acondicionamento</label>
                                                    <select name="residuos[{{ $index }}][acondicionamento_id]" class="form-control form-control-sm" required>
                                                        @php $acondAtual = $item->acondicionamento_id ?? $item->acondicionamento ?? 26; @endphp
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
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Tratamento</label>
                                                    <select name="residuos[{{ $index }}][tratamento_id]" class="form-control form-control-sm" required>
                                                        @php $tratAtual = $item->tratamento_id ?? $item->tratamento ?? 43; @endphp
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

                                            <!-- SEGUNDA LINHA: CLASSE E DADOS PERIGOSOS -->
                                            <div class="row m-0 p-2 border-top bg-light">
                                                <div class="col-md-2 p-1">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Classe do Resíduo <span class="text-danger">*</span></label>
                                                    <select name="residuos[{{ $index }}][classe_residuo]" class="form-control form-control-sm select-classe" data-id="{{ $index }}" required>
                                                        <option value="1" {{ $claAtual == 1 ? 'selected' : '' }}>CLASSE I (Perigoso)</option>
                                                        <option value="43" {{ $claAtual == 43 ? 'selected' : '' }}>CLASSE II A (Não Inerte)</option>
                                                        <option value="42" {{ $claAtual == 42 ? 'selected' : '' }}>CLASSE II B (Inerte)</option>
                                                        <option value="2"  {{ $claAtual == 2  ? 'selected' : '' }}>OUTROS</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Número ONU</label>
                                                    <input type="text" name="residuos[{{ $index }}][numero_onu]" class="form-control form-control-sm" value="{{ $item->numero_onu }}" placeholder="Ex: 1234">
                                                </div>
                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Classe de Risco</label>
                                                    <input type="text" name="residuos[{{ $index }}][classe_risco]" class="form-control form-control-sm" value="{{ $item->classe_risco }}" placeholder="Ex: 3">
                                                </div>
                                                <div class="col-md-3 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Nome de Embarque</label>
                                                    <input type="text" name="residuos[{{ $index }}][nome_embarque]" class="form-control form-control-sm" value="{{ $item->nome_embarque }}" placeholder="Nome apropriado">
                                                </div>
                                                <div class="col-md-2 p-1 div-perigoso-{{ $index }}" style="display: {{ $claAtual == 1 ? 'block' : 'none' }};">
                                                    <label class="font-weight-bold" style="font-size: 10px;">Grupo Embalagem</label>
                                                    <select name="residuos[{{ $index }}][grupo_embalagem]" class="form-control form-control-sm">
                                                        <option value="">Selecione</option>
                                                        <option value="I" {{ $item->grupo_embalagem == 'I' ? 'selected' : '' }}>I</option>
                                                        <option value="II" {{ $item->grupo_embalagem == 'II' ? 'selected' : '' }}>II</option>
                                                        <option value="III" {{ $item->grupo_embalagem == 'III' ? 'selected' : '' }}>III</option>
                                                        <option value="NA" {{ $item->grupo_embalagem == 'NA' ? 'selected' : '' }}>N/A</option>
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
                    <label class="font-weight-bold">Observações:</label>
                    <textarea name="observacao" class="form-control" rows="3">{{ $mtr->observacao }}</textarea>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('mtr.emissao.index') }}" class="btn btn-secondary font-weight-bold"><i class="fa fa-arrow-left"></i> Voltar</a>
                    <div>
                        <button type="submit" name="acao" value="rascunho" class="btn btn-warning font-weight-bold mr-2"><i class="fa fa-save"></i> Atualizar Rascunho</button>
                        <button type="submit" name="acao" value="transmitir" class="btn btn-success font-weight-bold px-5"><i class="fa fa-paper-plane"></i> Transmitir MTR</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) { $('.select2').select2({ width: '100%', allowClear: true }); }

            $('#possuiArmazenamento').change(function() {
                if($(this).is(':checked')) { $('#divArmazenador').slideDown(); }
                else { $('#divArmazenador').slideUp(); $('select[name="armazenador_cnpj"]').val('').trigger('change'); }
            });

            var itemIndex = $('#tabela-residuos tbody tr').length;

            $('#btnAddResiduo').on('click', function() {
                var html = `
            <tr id="linha-${itemIndex}">
                <td colspan="7" class="p-0">
                    <div class="row m-0 p-2">
                        <div class="col-md-2 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Cód. IBAMA</label>
                            <input type="text" name="residuos[${itemIndex}][codigo_ibama]" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-1 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Qtd</label>
                            <input type="number" step="0.0001" name="residuos[${itemIndex}][quantidade]" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-2 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Unidade</label>
                            <select name="residuos[${itemIndex}][unidade]" class="form-control form-control-sm" required>
                                <option value="2">Quilograma (kg)</option>
                                <option value="3">Tonelada (t)</option>
                                <option value="20">Metro cúbico (m³)</option>
                                <option value="21">Litro (L)</option>
                            </select>
                        </div>
                        <div class="col-md-2 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Estado Físico</label>
                            <select name="residuos[${itemIndex}][estado_fisico]" class="form-control form-control-sm" required>
                                <option value="4">Sólido</option>
                                <option value="2">Líquido</option>
                                <option value="1">Semissólido</option>
                                <option value="3">Gasoso</option>
                            </select>
                        </div>
                        <div class="col-md-2 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Acondicionamento</label>
                            <select name="residuos[${itemIndex}][acondicionamento_id]" class="form-control form-control-sm" required>
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
                        <div class="col-md-2 p-1">
                            <label class="font-weight-bold" style="font-size: 10px;">Tratamento</label>
                            <select name="residuos[${itemIndex}][tratamento_id]" class="form-control form-control-sm" required>
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
                            <select name="residuos[${itemIndex}][classe_residuo]" class="form-control form-control-sm select-classe" data-id="${itemIndex}" required>
                                <option value="43" selected>CLASSE II A</option>
                                <option value="44">CLASSE II B</option>
                                <option value="1">CLASSE I (Perigoso)</option>
                            </select>
                        </div>
                        <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                            <label class="font-weight-bold" style="font-size: 10px;">Número ONU</label>
                            <input type="text" name="residuos[${itemIndex}][numero_onu]" class="form-control form-control-sm" placeholder="Ex: 1234">
                        </div>
                        <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                            <label class="font-weight-bold" style="font-size: 10px;">Classe de Risco</label>
                            <input type="text" name="residuos[${itemIndex}][classe_risco]" class="form-control form-control-sm" placeholder="Ex: 3">
                        </div>
                        <div class="col-md-3 p-1 div-perigoso-${itemIndex}" style="display: none;">
                            <label class="font-weight-bold" style="font-size: 10px;">Nome de Embarque</label>
                            <input type="text" name="residuos[${itemIndex}][nome_embarque]" class="form-control form-control-sm" placeholder="Nome apropriado">
                        </div>
                        <div class="col-md-2 p-1 div-perigoso-${itemIndex}" style="display: none;">
                            <label class="font-weight-bold" style="font-size: 10px;">Grupo Embalagem</label>
                            <select name="residuos[${itemIndex}][grupo_embalagem]" class="form-control form-control-sm">
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
    </script>
@endsection
