@php $editing = isset($depara) && $depara; @endphp
<div class="row">
    <div class="col-lg-3 form-group">
        <label>Órgão *</label>
        <select name="orgao" class="form-control" required>
            @foreach(['SINIR','IEMA','SIGOR','FEAM','IMA','FEPAM'] as $orgao)
                <option value="{{ $orgao }}" {{ old('orgao',$depara->orgao ?? 'SINIR') === $orgao ? 'selected' : '' }}>{{ $orgao }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Produto</label>
        <select name="produto_id" class="form-control select2">
            <option value="">Selecione</option>
            @foreach($produtos as $produto)
                <option value="{{ $produto->id }}" {{ (string)old('produto_id',$depara->produto_id ?? '') === (string)$produto->id ? 'selected' : '' }}>{{ $produto->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Categoria</label>
        <select name="categoria_id" class="form-control">
            <option value="">Selecione</option>
            @foreach($categorias as $categoria)
                <option value="{{ $categoria->id }}" {{ (string)old('categoria_id',$depara->categoria_id ?? '') === (string)$categoria->id ? 'selected' : '' }}>{{ $categoria->nome }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3 form-group">
        <label>Subcategoria</label>
        <select name="sub_categoria_id" class="form-control">
            <option value="">Selecione</option>
            @foreach($subcategorias as $sub)
                <option value="{{ $sub->id }}" {{ (string)old('sub_categoria_id',$depara->sub_categoria_id ?? '') === (string)$sub->id ? 'selected' : '' }}>{{ $sub->categoria_nome }} / {{ $sub->nome }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-3 form-group"><label>NCM</label><input name="ncm" class="form-control" value="{{ old('ncm',$depara->ncm ?? '') }}"></div>
    <div class="col-lg-3 form-group"><label>Código IBAMA *</label><input name="cod_ibama" class="form-control" required value="{{ old('cod_ibama',$depara->cod_ibama ?? '') }}"></div>
    <div class="col-lg-6 form-group"><label>Descrição do resíduo *</label><input name="descricao_residuo" class="form-control" required value="{{ old('descricao_residuo',$depara->descricao_residuo ?? '') }}"></div>

    <div class="col-lg-2 form-group"><label>Estado físico *</label><input name="estado_fisico" class="form-control" required value="{{ old('estado_fisico',$depara->estado_fisico ?? 1) }}"></div>
    <div class="col-lg-2 form-group"><label>Classe *</label><input name="classe_residuo" class="form-control" required value="{{ old('classe_residuo',$depara->classe_residuo ?? '43') }}"></div>
    <div class="col-lg-2 form-group"><label>Acondicionamento *</label><input name="acondicionamento_id" class="form-control" required value="{{ old('acondicionamento_id',$depara->acondicionamento_id ?? 8) }}"></div>
    <div class="col-lg-2 form-group"><label>Tratamento *</label><input name="tratamento_id" class="form-control" required value="{{ old('tratamento_id',$depara->tratamento_id ?? 43) }}"></div>
    <div class="col-lg-2 form-group"><label>Unidade *</label><input name="unidade_medida" class="form-control" required value="{{ old('unidade_medida',$depara->unidade_medida ?? '2') }}"></div>
    <div class="col-lg-2 form-group"><label>Fator conversão</label><input name="fator_conversao" class="form-control" value="{{ old('fator_conversao',$depara->fator_conversao ?? '1.0000') }}"></div>
</div>
<div class="text-right">
    <a href="{{ route('mtr.depara.index') }}" class="btn btn-light-danger">Cancelar</a>
    <button class="btn btn-success">Salvar</button>
</div>
