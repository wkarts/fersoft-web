@extends('default.layout')

@section('content')
<style>
    .form-control, .form-select {
        border: 1px solid #a1a8c3 !important;
        border-radius: 4px !important;
        min-height: 38px !important;
        background-color: #ffffff !important;
    }
    /* Força a exibição correta dos selects padrão caso o Select2 falhe */
    select.form-control { display: block !important; }
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        border: 1px solid #a1a8c3 !important;
        border-radius: 4px !important;
        height: 38px !important;
        background-color: #ffffff !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        color: #212529 !important;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .border-box {
        border: 1px solid #cbd5e1 !important;
        background-color: #f8fafc;
        border-radius: 6px;
        padding: 18px;
        margin-bottom: 20px;
    }
</style>

<div class="card card-custom gutter-b">
    <div class="card-header bg-warning py-3">
        <div class="card-title">
            <h3 class="card-label text-dark font-weight-bolder">
                <i class="fa fa-edit text-dark mr-2"></i> Editar Regra De-Para MTR
            </h3>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-custom alert-light-danger fade show mb-4">
                <div class="alert-text">
                    <strong>Atenção! Por favor verifique os erros:</strong>
                    <ul class="mb-0 mt-1 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('mtr.depara.update', $depara->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- BLOCO 1: REGRA NO FERSOFT -->
            <div class="border-box">
                <h5 class="text-primary font-weight-bold mb-3">
                    <span class="badge badge-primary mr-2">1</span> Vínculo de Origem no Fersoft ERP
                </h5>
                <div class="row">
                    <!-- Opção 1: Por Subcategoria -->
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold text-success">
                            <i class="fa fa-star text-success mr-1"></i> Por Subcategoria (Recomendado):
                        </label>
                        <select name="sub_categoria_id" id="sub_categoria_id" class="form-control select2">
                            <option value="">-- Selecione a Subcategoria --</option>
                            @if(isset($subcategorias))
                                @foreach($subcategorias as $sub)
                                    <option value="{{ $sub->id }}" {{ old('sub_categoria_id', $depara->sub_categoria_id) == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->nome }} @if(isset($sub->categoria_nome)) ({{ $sub->categoria_nome }}) @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <small class="form-text text-muted">Aplica a todos os produtos deste subgrupo.</small>
                    </div>

                    <!-- Opção 2: Por Produto Específico -->
                    <div class="col-md-5 form-group">
                        <label class="font-weight-bold">Ou por Produto Específico:</label>
                        <select name="produto_id" id="produto_id" class="form-control select2">
                            <option value="">-- Selecione um Produto (Exceção) --</option>
                            @if(isset($produtos))
                                @foreach($produtos as $p)
                                    <option value="{{ $p->id }}" {{ old('produto_id', $depara->produto_id) == $p->id ? 'selected' : '' }}>
                                        {{ $p->nome }} (NCM: {{ $p->NCM ?? 'N/D' }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <small class="form-text text-muted">Usado apenas se este item tiver regra diferente do subgrupo.</small>
                    </div>

                    <!-- Opção 3: Por NCM -->
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Ou por NCM:</label>
                        <input type="text" name="ncm" class="form-control" value="{{ old('ncm', $depara->ncm) }}" placeholder="Ex: 76020000">
                        <small class="form-text text-muted">Regra genérica baseada na NCM.</small>
                    </div>
                </div>
            </div>

            <!-- BLOCO 2: DADOS DO RESÍDUO PARA O MTR (SINIR / IEMA) -->
            <div class="border-box">
                <h5 class="text-primary font-weight-bold mb-3">
                    <span class="badge badge-primary mr-2">2</span> Classificação do Resíduo no Órgão Ambiental
                </h5>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Órgão Ambientador: <span class="text-danger">*</span></label>
                        <select name="orgao" class="form-control" required>
                            <option value="SINIR" {{ old('orgao', $depara->orgao) == 'SINIR' ? 'selected' : '' }}>SINIR (Nacional)</option>
                            <option value="IEMA" {{ old('orgao', $depara->orgao) == 'IEMA' ? 'selected' : '' }}>IEMA (Espírito Santo)</option>
                            <option value="SIGOR" {{ old('orgao', $depara->orgao) == 'SIGOR' ? 'selected' : '' }}>SIGOR (São Paulo)</option>
                            <option value="FEAM" {{ old('orgao', $depara->orgao) == 'FEAM' ? 'selected' : '' }}>FEAM (Minas Gerais)</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Código IBAMA: <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="cod_ibama" id="cod_ibama" class="form-control" value="{{ old('cod_ibama', $depara->cod_ibama) }}" required readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalIbama">
                                    <i class="fa fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Descrição Oficial do Resíduo: <span class="text-danger">*</span></label>
                        <input type="text" name="descricao_residuo" id="descricao_residuo" class="form-control" value="{{ old('descricao_residuo', $depara->descricao_residuo) }}" required readonly>
                    </div>
                </div>
            </div>

            <!-- BLOCO 3: PROPRIEDADES FÍSICAS, EMBALAGEM E DESTINAÇÃO -->
            <div class="border-box">
                <h5 class="text-primary font-weight-bold mb-3">
                    <span class="badge badge-primary mr-2">3</span> Propriedades Padrão para Emissão
                </h5>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Estado Físico: <span class="text-danger">*</span></label>
                        <select name="estado_fisico" class="form-control" required>
                            <option value="1" {{ old('estado_fisico', $depara->estado_fisico) == '4' ? 'selected' : '' }}>SÓLIDO</option>
                            <option value="2" {{ old('estado_fisico', $depara->estado_fisico) == '2' ? 'selected' : '' }}>LÍQUIDO</option>
                            <option value="3" {{ old('estado_fisico', $depara->estado_fisico) == '1' ? 'selected' : '' }}>SEMISSÓLIDO</option>
                            <option value="4" {{ old('estado_fisico', $depara->estado_fisico) == '3' ? 'selected' : '' }}>GASOSO</option>
                        </select>
                    </div>

                    <select name="classe_residuo" class="form-control select2" required>
                        <optgroup label="Indústria (NBR 10.004)">
                            <option value="1" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '1' ? 'selected' : '' }}>CLASSE I</option>
                            <option value="43" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '43' ? 'selected' : '' }}>CLASSE II A</option>
                            <option value="42" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '42' ? 'selected' : '' }}>CLASSE II B</option>
                            <option value="2" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '2' ? 'selected' : '' }}>OUTROS</option>
                        </optgroup>
                        <optgroup label="Construção Civil (RCC)">
                            <option value="11" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '11' ? 'selected' : '' }}>CLASSE A (RCC)</option>
                            <option value="12" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '12' ? 'selected' : '' }}>CLASSE B (RCC)</option>
                            <option value="13" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '13' ? 'selected' : '' }}>CLASSE C (RCC)</option>
                            <option value="14" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '14' ? 'selected' : '' }}>CLASSE D (RCC)</option>
                        </optgroup>
                        <optgroup label="Serviços de Saúde (RSS)">
                            <option value="41" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '41' ? 'selected' : '' }}>GRUPO A (RSS)</option>
                            <option value="21" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '21' ? 'selected' : '' }}>GRUPO A1 (RSS)</option>
                            <option value="22" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '22' ? 'selected' : '' }}>GRUPO A2 (RSS)</option>
                            <option value="23" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '23' ? 'selected' : '' }}>GRUPO A3 (RSS)</option>
                            <option value="24" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '24' ? 'selected' : '' }}>GRUPO A4 (RSS)</option>
                            <option value="25" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '25' ? 'selected' : '' }}>GRUPO A5 (RSS)</option>
                            <option value="32" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '32' ? 'selected' : '' }}>GRUPO B (RSS)</option>
                            <option value="33" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '33' ? 'selected' : '' }}>GRUPO C (RSS)</option>
                            <option value="34" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '34' ? 'selected' : '' }}>GRUPO D (RSS)</option>
                            <option value="35" {{ old('classe_residuo', $depara->classe_residuo ?? '') == '35' ? 'selected' : '' }}>GRUPO E (RSS)</option>
                        </optgroup>
                    </select>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Acondicionamento: <span class="text-danger">*</span></label>
                        <select name="acondicionamento_id" class="form-control select2" required>
                            <option value="26" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '26' ? 'selected' : '' }}>Big Bag</option>
                            <option value="4"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '4' ? 'selected' : '' }}>Caçamba aberta</option>
                            <option value="5"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '5' ? 'selected' : '' }}>Caçamba fechada</option>
                            <option value="21" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '21' ? 'selected' : '' }}>Caixa</option>
                            <option value="22" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '22' ? 'selected' : '' }}>Caixa de papelão</option>
                            <option value="23" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '23' ? 'selected' : '' }}>Cilindro</option>
                            <option value="3"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '3' ? 'selected' : '' }}>Contêiner</option>
                            <option value="24" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '24' ? 'selected' : '' }}>Fardo</option>
                            <option value="8"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '8' ? 'selected' : '' }}>Granel</option>
                            <option value="13" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '13' ? 'selected' : '' }}>Outros</option>
                            <option value="25" {{ old('acondicionamento_id', $depara->acondicionamento_id) == '25' ? 'selected' : '' }}>Palete</option>
                            <option value="2"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '2' ? 'selected' : '' }}>Saco plástico</option>
                            <option value="9"  {{ old('acondicionamento_id', $depara->acondicionamento_id) == '9' ? 'selected' : '' }}>Tambor</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Tratamento: <span class="text-danger">*</span></label>
                        <select name="tratamento_id" class="form-control select2" required>
                            <option value="43" {{ old('tratamento_id', $depara->tratamento_id) == '43' ? 'selected' : '' }}>Reciclagem</option>
                            <option value="60" {{ old('tratamento_id', $depara->tratamento_id) == '60' ? 'selected' : '' }}>Coprocessamento</option>
                            <option value="4"  {{ old('tratamento_id', $depara->tratamento_id) == '4' ? 'selected' : '' }}>Aterro Resíduos Classe I</option>
                            <option value="52" {{ old('tratamento_id', $depara->tratamento_id) == '52' ? 'selected' : '' }}>Aterro Sanitário - RSU</option>
                            <option value="17" {{ old('tratamento_id', $depara->tratamento_id) == '17' ? 'selected' : '' }}>Aterro Resíduos Classes IIA e IIB</option>
                            <option value="51" {{ old('tratamento_id', $depara->tratamento_id) == '51' ? 'selected' : '' }}>Aterro de Reservaçaõ - RCC</option>
                            <option value="25" {{ old('tratamento_id', $depara->tratamento_id) == '25' ? 'selected' : '' }}>Autoclave</option>
                            <option value="30" {{ old('tratamento_id', $depara->tratamento_id) == '30' ? 'selected' : '' }}>Barragem de Rejeitos</option>
                            <option value="27" {{ old('tratamento_id', $depara->tratamento_id) == '27' ? 'selected' : '' }}>Biodigestão</option>
                            <option value="31" {{ old('tratamento_id', $depara->tratamento_id) == '31' ? 'selected' : '' }}>Biometanização</option>
                            <option value="32" {{ old('tratamento_id', $depara->tratamento_id) == '32' ? 'selected' : '' }}>Biorremediação</option>
                            <option value="29" {{ old('tratamento_id', $depara->tratamento_id) == '29' ? 'selected' : '' }}>Blendagem para Coprocessamento</option>
                            <option value="73" {{ old('tratamento_id', $depara->tratamento_id) == '73' ? 'selected' : '' }}>Blendagem para Incineração</option>
                            <option value="50" {{ old('tratamento_id', $depara->tratamento_id) == '50' ? 'selected' : '' }}>Compostagem</option>
                            <option value="33" {{ old('tratamento_id', $depara->tratamento_id) == '33' ? 'selected' : '' }}>Desativação de Fosfina</option>
                            <option value="24" {{ old('tratamento_id', $depara->tratamento_id) == '24' ? 'selected' : '' }}>Descontaminação de Lâmpadas</option>
                            <option value="28" {{ old('tratamento_id', $depara->tratamento_id) == '28' ? 'selected' : '' }}>Desmontagem de Veículos</option>
                            <option value="34" {{ old('tratamento_id', $depara->tratamento_id) == '34' ? 'selected' : '' }}>Disposição em Cava de Mineração</option>
                            <option value="36" {{ old('tratamento_id', $depara->tratamento_id) == '36' ? 'selected' : '' }}>Estação de Transbordo - RSU</option>
                            <option value="72" {{ old('tratamento_id', $depara->tratamento_id) == '72' ? 'selected' : '' }}>Fertirrigação</option>
                            <option value="38" {{ old('tratamento_id', $depara->tratamento_id) == '38' ? 'selected' : '' }}>Fins Didáticos/Pesquisa</option>
                            <option value="39" {{ old('tratamento_id', $depara->tratamento_id) == '39' ? 'selected' : '' }}>Gaseificação</option>
                            <option value="26" {{ old('tratamento_id', $depara->tratamento_id) == '26' ? 'selected' : '' }}>Incineração</option>
                            <option value="40" {{ old('tratamento_id', $depara->tratamento_id) == '40' ? 'selected' : '' }}>Microondas</option>
                            <option value="53" {{ old('tratamento_id', $depara->tratamento_id) == '53' ? 'selected' : '' }}>Outros</option>
                            <option value="41" {{ old('tratamento_id', $depara->tratamento_id) == '41' ? 'selected' : '' }}>Pilha de Estéril</option>
                            <option value="42" {{ old('tratamento_id', $depara->tratamento_id) == '42' ? 'selected' : '' }}>Pirólise</option>
                            <option value="45" {{ old('tratamento_id', $depara->tratamento_id) == '45' ? 'selected' : '' }}>Recuperação Energética</option>
                            <option value="44" {{ old('tratamento_id', $depara->tratamento_id) == '44' ? 'selected' : '' }}>Rerrefino</option>
                            <option value="23" {{ old('tratamento_id', $depara->tratamento_id) == '23' ? 'selected' : '' }}>Tratamento de Efluentes</option>
                            <option value="49" {{ old('tratamento_id', $depara->tratamento_id) == '49' ? 'selected' : '' }}>Tratamento Térmico - outros</option>
                            <option value="46" {{ old('tratamento_id', $depara->tratamento_id) == '46' ? 'selected' : '' }}>Triagem com Armazenamento</option>
                            <option value="61" {{ old('tratamento_id', $depara->tratamento_id) == '61' ? 'selected' : '' }}>Triagem e Transbordo</option>
                            <option value="47" {{ old('tratamento_id', $depara->tratamento_id) == '47' ? 'selected' : '' }}>Uso Agrícola</option>
                            <option value="48" {{ old('tratamento_id', $depara->tratamento_id) == '48' ? 'selected' : '' }}>Uso Alimentação Animal</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Unidade de Medida MTR: <span class="text-danger">*</span></label>
                        <select name="unidade_medida" class="form-control" required>
                            <option value="2"  {{ old('unidade_medida', $depara->unidade_medida) == '2' ? 'selected' : '' }}>Quilograma (Kg)</option>
                            <option value="3"  {{ old('unidade_medida', $depara->unidade_medida) == '3' ? 'selected' : '' }}>Tonelada (Ton)</option>
                            <option value="20" {{ old('unidade_medida', $depara->unidade_medida) == '20' ? 'selected' : '' }}>Metro Cúbico (M³)</option>
                            <option value="21" {{ old('unidade_medida', $depara->unidade_medida) == '21' ? 'selected' : '' }}>Litro (Lt)</option>
                        </select>
                    </div>

                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Fator de Conversão:</label>
                        <input type="number" step="0.0001" name="fator_conversao" class="form-control" value="{{ old('fator_conversao', $depara->fator_conversao) }}" required>
                        <small class="form-text text-muted">Qtd NF × Fator = Qtd MTR</small>
                    </div>
                </div>
            </div>

            <!-- BOTÕES -->
            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('mtr.depara.index') }}" class="btn btn-secondary mr-2">
                    <i class="fa fa-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-warning font-weight-bold px-5">
                    <i class="fa fa-save"></i> Atualizar Regra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE SELEÇÃO IBAMA -->
<div class="modal fade" id="modalIbama" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fa fa-list text-white mr-2"></i> Tabela Oficial IBAMA (SINIR / IN 13/2012)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <input type="text" id="filtroIbama" class="form-control form-control-lg" placeholder="🔍 Digite o código ou palavra (ex: 191202, metais ferrosos)..." autocomplete="off">
                </div>

                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-bordered table-striped table-hover" id="tabelaIbama">
                        <thead class="thead-light sticky-top" style="z-index: 1;">
                            <tr>
                                <th width="120">Código IBAMA</th>
                                <th>Descrição Oficial do Resíduo</th>
                                <th width="120" class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($residuos))
                                @foreach($residuos as $res)
                                    <tr data-codigo="{{ $res->res_codigo_ibama }}" data-descricao="{{ $res->res_descricao }}">
                                        <td><code>{{ $res->res_codigo_ibama }}</code></td>
                                        <td>{{ $res->res_descricao }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-xs btn-success btnSelecionarResiduo">
                                                <i class="fa fa-check"></i> Selecionar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
jQuery(document).ready(function($) {
    
    // 1. Inicializa os Select2
    if ($.fn.select2) {
        $('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });

        $('.select2').select2({
            width: '100%',
            placeholder: "-- Selecione --",
            allowClear: true
        });
    }

    // 2. Filtro otimizado para o modal do IBAMA
    let timer;
    $('#filtroIbama').on('input', function() {
        clearTimeout(timer);
        var termoPesquisa = $(this).val().toLowerCase().trim();
        
        timer = setTimeout(function() {
            $('#tabelaIbama tbody tr').each(function() {
                var textoDaLinha = $(this).text().toLowerCase();
                
                if (textoDaLinha.includes(termoPesquisa)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }, 150);
    });

    // 3. Ação do botão "Selecionar" dentro do modal
    $(document).on('click', '.btnSelecionarResiduo', function(e) {
        e.preventDefault();
        
        var tr = $(this).closest('tr');
        var codigo = tr.attr('data-codigo');
        var descricao = tr.attr('data-descricao');

        // Preenche os campos da tela
        $('#cod_ibama').val(codigo);
        $('#descricao_residuo').val(descricao);

        // Fecha o modal de forma segura
        try {
            $('#modalIbama').modal('hide');
        } catch (err) {
            $('#modalIbama').removeClass('show').hide();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }
    });

});
</script>
@endsection