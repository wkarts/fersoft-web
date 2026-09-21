@extends('default.layout')

@section('content')
@php
    $editing = isset($data) && $data;
    $oldItems = old('itens', $editing ? $data->itens->map(fn($i) => [
        'tipo_item' => $i->tipo_item,
        'servico_id' => $i->servico_id,
        'produto_id' => $i->produto_id,
        'quantidade_prevista' => $i->quantidade_prevista,
        'valor_unitario' => $i->valor_unitario,
    ])->toArray() : []);
@endphp
<div class="container-fluid">
    <form method="POST" action="{{ $editing ? '/contratos/update/' . $data->id : '/contratos/save' }}" enctype="multipart/form-data">
        @csrf
        @if($editing)<input type="hidden" name="id" value="{{ $data->id }}">@endif

        <div class="card card-custom gutter-b">
            <div class="card-header"><h3 class="card-title">{{ $editing ? 'Editar' : 'Novo' }} Contrato de Locação / Serviços</h3></div>
            <div class="card-body">
                @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

                <div class="row">
                    <div class="col-lg-3 form-group">
                        <label>Nº Contrato</label>
                        <input name="numero_contrato" class="form-control" value="{{ old('numero_contrato', $data->numero_contrato ?? '') }}">
                    </div>
                    <div class="col-lg-5 form-group">
                        <label>Cliente *</label>
                        <select name="cliente_id" class="form-control select2" required>
                            <option value="">Selecione</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ (string)old('cliente_id', $data->cliente_id ?? '') === (string)$cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 form-group">
                        <label>Filial / Matriz</label>
                        <select name="filial_id" class="form-control">
                            <option value="">Matriz</option>
                            @foreach($filiaisLista as $filial)
                                <option value="{{ $filial->id }}" {{ (string)old('filial_id', $data->filial_id ?? '') === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 form-group">
                        <label>Vendedor</label>
                        <select name="vendedor_id" class="form-control select2">
                            <option value="">Selecione</option>
                            @foreach($vendedores as $v)
                                <option value="{{ $v->id }}" {{ (string)old('vendedor_id', $data->vendedor_id ?? '') === (string)$v->id ? 'selected' : '' }}>{{ $v->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4 form-group">
                        <label>Contato</label>
                        <input name="contato_nome" class="form-control" value="{{ old('contato_nome', $data->contato_nome ?? '') }}">
                    </div>
                    <div class="col-lg-4 form-group">
                        <label>Telefone contato</label>
                        <input name="contato_telefone" class="form-control" value="{{ old('contato_telefone', $data->contato_telefone ?? '') }}">
                    </div>
                </div>

                <h5 class="mt-3">Obra / Local</h5>
                <div class="row">
                    <div class="col-lg-2 form-group"><label>CEP</label><input name="cep_obra" class="form-control" value="{{ old('cep_obra', $data->cep_obra ?? '') }}"></div>
                    <div class="col-lg-5 form-group"><label>Endereço</label><input name="endereco_obra" class="form-control" value="{{ old('endereco_obra', $data->endereco_obra ?? '') }}"></div>
                    <div class="col-lg-2 form-group"><label>Número</label><input name="numero_obra" class="form-control" value="{{ old('numero_obra', $data->numero_obra ?? '') }}"></div>
                    <div class="col-lg-3 form-group"><label>Bairro</label><input name="bairro_obra" class="form-control" value="{{ old('bairro_obra', $data->bairro_obra ?? '') }}"></div>
                    <div class="col-lg-6 form-group">
                        <label>Cidade</label>
                        <select name="cidade_obra_id" class="form-control select2">
                            <option value="">Selecione</option>
                            @foreach($cidades as $cidade)
                                <option value="{{ $cidade->id }}" {{ (string)old('cidade_obra_id', $data->cidade_obra_id ?? '') === (string)$cidade->id ? 'selected' : '' }}>{{ $cidade->nome }} ({{ $cidade->uf }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 form-group"><label>Data início *</label><input type="date" name="data_inicio" class="form-control" required value="{{ old('data_inicio', isset($data->data_inicio) ? $data->data_inicio->format('Y-m-d') : date('Y-m-d')) }}"></div>
                    <div class="col-lg-3 form-group"><label>Data fim</label><input type="date" name="data_fim" class="form-control" value="{{ old('data_fim', isset($data->data_fim) ? $data->data_fim->format('Y-m-d') : '') }}"></div>
                </div>

                <h5 class="mt-3">Financeiro</h5>
                <div class="row">
                    <div class="col-lg-4 form-group"><label>Valor do contrato *</label><input name="valor_contrato" class="form-control money" required value="{{ old('valor_contrato', isset($data) ? number_format((float)$data->valor_contrato,2,',','.') : '') }}"></div>
                    <div class="col-lg-4 form-group"><label>Retenção (%)</label><input name="percentual_retencao" class="form-control money" value="{{ old('percentual_retencao', isset($data) ? number_format((float)$data->percentual_retencao,2,',','.') : '0,00') }}"></div>
                    <div class="col-lg-4 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            @foreach(['Ativo','Finalizado','Suspenso','Cancelado'] as $status)
                                <option value="{{ $status }}" {{ old('status', $data->status ?? 'Ativo') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6 form-group"><label>Arquivo do contrato</label><input type="file" name="arquivo_contrato" class="form-control"></div>
                    <div class="col-lg-6 form-group"><label>Observações</label><textarea name="observacoes" class="form-control">{{ old('observacoes', $data->observacoes ?? '') }}</textarea></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h5 class="mb-0">Itens previstos do contrato</h5>
                    <button type="button" id="btn-add-item-contrato" class="btn btn-sm btn-primary">+ Item</button>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-bordered" id="contrato-itens">
                        <thead><tr><th>Tipo</th><th>Serviço / Produto</th><th>Quantidade</th><th>Valor unitário</th><th>Total</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="/contratos" class="btn btn-light-danger">Cancelar</a>
                <button class="btn btn-success">Salvar contrato</button>
            </div>
        </div>
    </form>
</div>

<script>
(function(){
    const services = @json($servicos->map(fn($s) => ['id'=>$s->id,'nome'=>$s->nome]));
    const products = @json($produtos->map(fn($p) => ['id'=>$p->id,'nome'=>$p->nome]));
    const initial = @json($oldItems);
    const tbody = document.querySelector('#contrato-itens tbody');
    let index = 0;

    function esc(v){ return String(v ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
    function options(items, selected){
        return '<option value="">Selecione</option>' + items.map(i => '<option value="'+i.id+'" '+(String(i.id)===String(selected)?'selected':'')+'>'+esc(i.nome)+'</option>').join('');
    }
    function add(item = {}) {
        const i = index++;
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select class="form-control tipo" name="itens['+i+'][tipo_item]"><option value="Servico">Serviço</option><option value="Locacao">Locação</option></select></td>'+
            '<td><select class="form-control servico" name="itens['+i+'][servico_id]">'+options(services,item.servico_id)+'</select>'+
            '<select class="form-control produto" name="itens['+i+'][produto_id]" style="display:none">'+options(products,item.produto_id)+'</select></td>'+
            '<td><input class="form-control qtd" name="itens['+i+'][quantidade_prevista]" value="'+esc(item.quantidade_prevista || 1)+'"></td>'+
            '<td><input class="form-control valor" name="itens['+i+'][valor_unitario]" value="'+esc(item.valor_unitario || 0)+'"></td>'+
            '<td class="total">0,00</td>'+
            '<td><button type="button" class="btn btn-sm btn-danger remover">×</button></td>';
        tbody.appendChild(tr);
        const tipo = tr.querySelector('.tipo');
        tipo.value = item.tipo_item || 'Servico';
        function toggle(){
            tr.querySelector('.servico').style.display = tipo.value === 'Servico' ? '' : 'none';
            tr.querySelector('.produto').style.display = tipo.value === 'Locacao' ? '' : 'none';
        }
        function total(){
            const q = parseFloat(String(tr.querySelector('.qtd').value).replace(',','.')) || 0;
            const v = parseFloat(String(tr.querySelector('.valor').value).replace('.','').replace(',','.')) || 0;
            tr.querySelector('.total').textContent = (q*v).toFixed(2).replace('.',',');
        }
        tipo.addEventListener('change', toggle);
        tr.querySelector('.qtd').addEventListener('input', total);
        tr.querySelector('.valor').addEventListener('input', total);
        tr.querySelector('.remover').addEventListener('click', () => tr.remove());
        toggle(); total();
    }
    document.getElementById('btn-add-item-contrato').addEventListener('click', () => add());
    (initial.length ? initial : [{}]).forEach(add);
})();
</script>
@endsection
