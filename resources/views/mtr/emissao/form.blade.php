@php
    $editing = isset($mtr) && $mtr;
    $origemTipo = old('origem_tipo', session('origem_tipo', $mtr->tipo_origem ?? 'avulso'));
    $origemId = old('origem_id', session('origem_id', $mtr->origem_id ?? ''));
    $ticketPesagemId = old('ticket_pesagem_id', session('ticket_pesagem_id', $mtr->ticket_pesagem_id ?? ''));
    $vendaId = old('venda_id', session('venda_id', $mtr->venda_id ?? ''));
    $residuosInicial = old('residuos');

    if ($residuosInicial === null) {
        if ($editing) {
            $residuosInicial = collect($itens)->map(fn($i) => [
                'codigo_ibama' => $i->cod_ibama,
                'quantidade' => $i->quantidade,
                'unidade' => $i->unidade_medida,
                'estado_fisico' => $i->estado_fisico,
                'classe_residuo' => $i->classe_residuo,
                'acondicionamento' => $i->acondicionamento_id,
                'tratamento' => $i->tratamento_id,
            ])->values()->all();
        } else {
            $residuosInicial = session('residuos_importados', []);
        }
    }
@endphp

<input type="hidden" name="origem_tipo" value="{{ $origemTipo }}">
<input type="hidden" name="origem_id" value="{{ $origemId }}">
<input type="hidden" name="ticket_pesagem_id" value="{{ $ticketPesagemId }}">
<input type="hidden" name="venda_id" value="{{ $vendaId }}">

<div class="row">
    <div class="col-lg-4 form-group">
        <label>Unidade Geradora *</label>
        <select name="gerador_id" class="form-control" required>
            <option value="">Selecione</option>
            @foreach($unidadesGeradoras as $u)
                <option value="{{ $u->id }}" {{ (string)old('gerador_id', '') === (string)$u->id || ($editing && preg_replace('/\D+/','',$u->cpf_cnpj) === preg_replace('/\D+/','',$mtr->gerador_cnpj)) ? 'selected' : '' }}>
                    {{ $u->descricao ?: $u->cpf_cnpj }} - {{ $u->orgao }} / unidade {{ $u->unidade_id }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-4 form-group">
        <label>Transportador CNPJ/CPF *</label>
        <input name="transportador_cnpj" class="form-control" required value="{{ old('transportador_cnpj', $mtr->transportador_cnpj ?? '') }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>Destinador CNPJ/CPF *</label>
        <input name="destinador_cnpj" class="form-control" required value="{{ old('destinador_cnpj', session('destinador_cnpj', $mtr->destinador_cnpj ?? '')) }}">
    </div>

    <div class="col-lg-4 form-group">
        <label>Motorista</label>
        <input name="nome_motorista" class="form-control" value="{{ old('nome_motorista', session('motorista_nome', $mtr->motorista_nome ?? '')) }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>Placa</label>
        <input name="placa_veiculo" class="form-control" value="{{ old('placa_veiculo', session('placa_veiculo', $mtr->veiculo_placa ?? '')) }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>Data de expedição</label>
        <input type="datetime-local" name="data_expedicao" class="form-control" value="{{ old('data_expedicao', $editing && $mtr->data_expedicao ? $mtr->data_expedicao->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="col-lg-4 form-group">
        <label class="d-block">Armazenamento temporário</label>
        <label><input type="checkbox" name="possui_armazenamento" value="1" {{ old('possui_armazenamento', !empty($mtr->armazenador_cnpj ?? null)) ? 'checked' : '' }}> Possui armazenador</label>
    </div>
    <div class="col-lg-4 form-group">
        <label>CNPJ armazenador</label>
        <input name="armazenador_cnpj" class="form-control" value="{{ old('armazenador_cnpj', $mtr->armazenador_cnpj ?? '') }}">
    </div>
    <div class="col-lg-4 form-group">
        <label>Origem</label>
        <input class="form-control" value="{{ strtoupper(str_replace('_',' ', $origemTipo)) }}{{ $origemId ? ' #' . $origemId : '' }}" readonly>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4">
    <h5>Resíduos</h5>
    <button type="button" id="mtrAddResiduo" class="btn btn-sm btn-primary">+ Resíduo</button>
</div>
<div class="table-responsive">
    <table class="table table-bordered" id="mtrResiduos">
        <thead><tr><th>Cód. IBAMA</th><th>Quantidade</th><th>Unidade</th><th>Estado</th><th>Classe</th><th>Acond.</th><th>Trat.</th><th></th></tr></thead>
        <tbody></tbody>
    </table>
</div>

<div class="form-group">
    <label>Observação</label>
    <textarea name="observacao" class="form-control" rows="3">{{ old('observacao', session('observacao_importada', $mtr->observacao ?? '')) }}</textarea>
</div>

<div class="text-right">
    <a href="{{ route('mtr.emissao.index') }}" class="btn btn-light-danger">Cancelar</a>
    <button name="acao" value="rascunho" class="btn btn-light-primary">Salvar rascunho</button>
    <button name="acao" value="transmitir" class="btn btn-success">Salvar e transmitir</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const initial = @json(array_values($residuosInicial ?: []));
    const tbody = document.querySelector('#mtrResiduos tbody');
    const addBtn = document.getElementById('mtrAddResiduo');
    let index = 0;

    const esc = value => String(value ?? '').replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));

    function add(item = {}) {
        const i = index++;
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input class="form-control" required name="residuos['+i+'][codigo_ibama]" value="'+esc(item.codigo_ibama||'')+'"></td>'+
            '<td><input class="form-control" required name="residuos['+i+'][quantidade]" value="'+esc(item.quantidade||'')+'"></td>'+
            '<td><input class="form-control" required name="residuos['+i+'][unidade]" value="'+esc(item.unidade||'2')+'"></td>'+
            '<td><input class="form-control" name="residuos['+i+'][estado_fisico]" value="'+esc(item.estado_fisico||1)+'"></td>'+
            '<td><input class="form-control" required name="residuos['+i+'][classe_residuo]" value="'+esc(item.classe_residuo||43)+'"></td>'+
            '<td><input class="form-control" required name="residuos['+i+'][acondicionamento]" value="'+esc(item.acondicionamento||item.acondicionamento_id||8)+'"></td>'+
            '<td><input class="form-control" required name="residuos['+i+'][tratamento]" value="'+esc(item.tratamento||item.tratamento_id||43)+'"></td>'+
            '<td><button type="button" class="btn btn-sm btn-danger">&times;</button></td>';
        tr.querySelector('button').addEventListener('click', () => tr.remove());
        tbody.appendChild(tr);
    }

    addBtn.addEventListener('click', () => add());
    (initial.length ? initial : [{}]).forEach(add);
});
</script>
