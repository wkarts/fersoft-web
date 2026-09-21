@extends('default.layout')

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

<div class="card card-custom gutter-b">
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
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Motorista: <span class="text-danger">*</span></label>
                        <input type="text" name="nome_motorista" class="form-control" value="{{ $mtr->motorista_nome }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Placa do Veículo: <span class="text-danger">*</span></label>
                        <input type="text" name="placa_veiculo" class="form-control" value="{{ $mtr->veiculo_placa }}" required>
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
                            <thead>
                              <tr>
                                  <th style="width: 15%">Cód. IBAMA</th>
                                  <th style="width: 12%">Qtd</th>
                                  <th style="width: 13%">Unidade</th>
                                  <th style="width: 12%">Estado Físico</th>
                                  <th style="width: 15%">Classe</th> <!-- Nova coluna -->
                                  <th style="width: 15%">Acondicionamento</th>
                                  <th style="width: 15%">Tratamento</th>
                                  <th style="width: 3%">#</th>
                              </tr>
                          </thead>
                        </thead>
                        <tbody>
                            @if(isset($itens))
                                @foreach($itens as $index => $item)
                                    <tr id="linha-{{ $index }}">
                                        <td><input type="text" name="residuos[{{ $index }}][codigo_ibama]" class="form-control form-control-sm" value="{{ $item->cod_ibama }}" required></td>
                                        <td><input type="number" step="0.0001" name="residuos[{{ $index }}][quantidade]" class="form-control form-control-sm" value="{{ $item->quantidade }}" required></td>
                                        <td>
                                            <select name="residuos[{{ $index }}][unidade]" class="form-control form-control-sm" required>
                                                @php $uniAtual = $item->unidade_medida ?? $item->unidade ?? 2; @endphp
                                                <option value="2" {{ $uniAtual == 2 ? 'selected' : '' }}>Quilograma (kg)</option>
                                                <option value="3" {{ $uniAtual == 3 ? 'selected' : '' }}>Tonelada (t)</option>
                                                <option value="20" {{ $uniAtual == 20 ? 'selected' : '' }}>Metro cúbico (m³)</option>
                                                <option value="21" {{ $uniAtual == 21 ? 'selected' : '' }}>Litro (L)</option>
                                            </select>
                                        </td>
                                        <!-- ESTADO FÍSICO -->
                                          <td>
                                              <select name="residuos[{{ $index }}][estado_fisico]" class="form-control form-control-sm" required>
                                                  @php $estAtual = $item->estado_fisico ?? 1; @endphp
                                                  <option value="1" {{ $estAtual == 1 ? 'selected' : '' }}>Sólido</option>
                                                  <option value="2" {{ $estAtual == 2 ? 'selected' : '' }}>Líquido</option>
                                                  <option value="3" {{ $estAtual == 3 ? 'selected' : '' }}>Semissólido</option>
                                                  <option value="4" {{ $estAtual == 4 ? 'selected' : '' }}>Gasoso</option>
                                              </select>
                                          </td>

                                          <!-- CLASSE DO RESÍDUO (CÓDIGOS OFICIAIS SINIR) -->
                                          <td>
                                              <select name="residuos[{{ $index }}][classe_residuo]" class="form-control form-control-sm" required>
                                                  @php $claAtual = $item->classe_residuo ?? $item->classe ?? 42; @endphp
                                                  <option value="42" {{ $claAtual == 42 ? 'selected' : '' }}>CLASSE II B (Inerte)</option>
                                                  <option value="43" {{ $claAtual == 43 ? 'selected' : '' }}>CLASSE II A (Não Inerte)</option>
                                                  <option value="1"  {{ $claAtual == 1  ? 'selected' : '' }}>CLASSE I (Perigoso)</option>
                                                  <option value="2"  {{ $claAtual == 2  ? 'selected' : '' }}>OUTROS</option>
                                                  <option value="11" {{ $claAtual == 11 ? 'selected' : '' }}>CLASSE A (RCC)</option>
                                                  <option value="12" {{ $claAtual == 12 ? 'selected' : '' }}>CLASSE B (RCC)</option>
                                                  <option value="13" {{ $claAtual == 13 ? 'selected' : '' }}>CLASSE C (RCC)</option>
                                                  <option value="14" {{ $claAtual == 14 ? 'selected' : '' }}>CLASSE D (RCC)</option>
                                              </select>
                                          </td>
                                        <td>
                                            <!-- ATENÇÃO: Name corrigido para "acondicionamento" para bater com o Controller -->
                                            <select name="residuos[{{ $index }}][acondicionamento]" class="form-control form-control-sm" required>
                                                @php $acondAtual = $item->acondicionamento_id ?? $item->acondicionamento ?? ''; @endphp
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
                                        </td>
                                        <td>
                                            <!-- ATENÇÃO: Name "tratamento" -->
                                            <select name="residuos[{{ $index }}][tratamento]" class="form-control form-control-sm" required>
                                                @php $tratAtual = $item->tratamento_id ?? $item->tratamento ?? ''; @endphp
                                                <option value="43" {{ $tratAtual == 43 ? 'selected' : '' }}>Reciclagem</option>
                                                <option value="60" {{ $tratAtual == 60 ? 'selected' : '' }}>Coprocessamento</option>
                                                <option value="4"  {{ $tratAtual == 4 ? 'selected' : '' }}>Aterro Resíduos Classe I</option>
                                                <option value="52" {{ $tratAtual == 52 ? 'selected' : '' }}>Aterro Sanitário - RSU</option>
                                                <option value="17" {{ $tratAtual == 17 ? 'selected' : '' }}>Aterro Resíduos Classes IIA e IIB</option>
                                                <option value="51" {{ $tratAtual == 51 ? 'selected' : '' }}>Aterro de Reservaçaõ - RCC</option>
                                                <option value="25" {{ $tratAtual == 25 ? 'selected' : '' }}>Autoclave</option>
                                                <option value="30" {{ $tratAtual == 30 ? 'selected' : '' }}>Barragem de Rejeitos</option>
                                                <option value="27" {{ $tratAtual == 27 ? 'selected' : '' }}>Biodigestão</option>
                                                <option value="31" {{ $tratAtual == 31 ? 'selected' : '' }}>Biometanização</option>
                                                <option value="32" {{ $tratAtual == 32 ? 'selected' : '' }}>Biorremediação</option>
                                                <option value="29" {{ $tratAtual == 29 ? 'selected' : '' }}>Blendagem para Coprocessamento</option>
                                                <option value="73" {{ $tratAtual == 73 ? 'selected' : '' }}>Blendagem para Incineração</option>
                                                <option value="50" {{ $tratAtual == 50 ? 'selected' : '' }}>Compostagem</option>
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
                                                <option value="45" {{ $tratAtual == 45 ? 'selected' : '' }}>Recuperação Energética</option>
                                                <option value="44" {{ $tratAtual == 44 ? 'selected' : '' }}>Rerrefino</option>
                                                <option value="23" {{ $tratAtual == 23 ? 'selected' : '' }}>Tratamento de Efluentes</option>
                                                <option value="49" {{ $tratAtual == 49 ? 'selected' : '' }}>Tratamento Térmico - outros</option>
                                                <option value="46" {{ $tratAtual == 46 ? 'selected' : '' }}>Triagem com Armazenamento</option>
                                                <option value="61" {{ $tratAtual == 61 ? 'selected' : '' }}>Triagem e Transbordo</option>
                                                <option value="47" {{ $tratAtual == 47 ? 'selected' : '' }}>Uso Agrícola</option>
                                                <option value="48" {{ $tratAtual == 48 ? 'selected' : '' }}>Uso Alimentação Animal</option>
                                            </select>
                                        </td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-linha" data-id="{{ $index }}"><i class="fa fa-trash"></i></button></td>
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
                <td><input type="text" name="residuos[${itemIndex}][codigo_ibama]" class="form-control form-control-sm" required></td>
                <td><input type="number" step="0.0001" name="residuos[${itemIndex}][quantidade]" class="form-control form-control-sm" required></td>

                <td>
                    <select name="residuos[${itemIndex}][unidade]" class="form-control form-control-sm" required>
                        <option value="2">Quilograma (kg)</option>
                        <option value="3">Tonelada (t)</option>
                        <option value="20">Metro cúbico (m³)</option>
                        <option value="21">Litro (L)</option>
                    </select>
                </td>


				// DENTRO DO $('#btnAddResiduo').on('click', function() { ...

                  `<td>
                      <select name="residuos[${itemIndex}][estado_fisico]" class="form-control form-control-sm" required>
                          <option value="1">Sólido</option>
                          <option value="2">Líquido</option>
                          <option value="3">Semissólido</option>
                          <option value="4">Gasoso</option>
                      </select>
                  </td>

                  <td>
                      <select name="residuos[${itemIndex}][classe_residuo]" class="form-control form-control-sm" required>
                          <option value="42">CLASSE II B (Inerte)</option>
                          <option value="43">CLASSE II A (Não Inerte)</option>
                          <option value="1">CLASSE I (Perigoso)</option>
                          <option value="2">OUTROS</option>
                          <option value="11">CLASSE A (RCC)</option>
                          <option value="12">CLASSE B (RCC)</option>
                          <option value="13">CLASSE C (RCC)</option>
                          <option value="14">CLASSE D (RCC)</option>
                      </select>
                  </td>`
                <td>
                    <select name="residuos[${itemIndex}][estado_fisico]" class="form-control form-control-sm" required>
                        <option value="4">Sólido</option>
                        <option value="2">Líquido</option>
                        <option value="1">Semissólido</option>
                        <option value="3">Gasoso</option>
                    </select>
                </td>

                <td>
                    <select name="residuos[${itemIndex}][acondicionamento]" class="form-control form-control-sm" required>
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
                </td>

                <td>
                    <select name="residuos[${itemIndex}][tratamento]" class="form-control form-control-sm" required>
                        <option value="43">Reciclagem</option>
                        <option value="60">Coprocessamento</option>
                        <option value="4">Aterro Resíduos Classe I</option>
                        <option value="52">Aterro Sanitário - RSU</option>
                        <option value="17">Aterro Resíduos Classes IIA e IIB</option>
                        <option value="51">Aterro de Reservaçaõ - RCC</option>
                        <option value="25">Autoclave</option>
                        <option value="30">Barragem de Rejeitos</option>
                        <option value="27">Biodigestão</option>
                        <option value="31">Biometanização</option>
                        <option value="32">Biorremediação</option>
                        <option value="29">Blendagem para Coprocessamento</option>
                        <option value="73">Blendagem para Incineração</option>
                        <option value="50">Compostagem</option>
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
                        <option value="45">Recuperação Energética</option>
                        <option value="44">Rerrefino</option>
                        <option value="23">Tratamento de Efluentes</option>
                        <option value="49">Tratamento Térmico - outros</option>
                        <option value="46">Triagem com Armazenamento</option>
                        <option value="61">Triagem e Transbordo</option>
                        <option value="47">Uso Agrícola</option>
                        <option value="48">Uso Alimentação Animal</option>
                    </select>
                </td>

                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-icon btn-danger btn-remover-linha" data-id="${itemIndex}">
                        <i class="fa fa-trash"></i>
                    </button>
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

    if ($('#tabela-residuos tbody tr').length === 0) {
        $('#btnAddResiduo').trigger('click');
    }
});
</script>
@endsection