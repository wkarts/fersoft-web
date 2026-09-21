@extends('default.layout')
@section('content')
<div class="card">
    <div class="card-body">
        <h4>Nova Medição / Faturamento Mensal</h4>
        <p><strong>Contrato ID:</strong> {{ $contrato->id }} | <strong>Cliente:</strong> {{ $contrato->cliente->razao_social ?? '' }}</p>
        <p><strong>Valor Total do Contrato:</strong> R$ {{ number_format($contrato->valor_contrato, 2, ',', '.') }} | <strong>Saldo Disponível:</strong> R$ {{ number_format($contrato->valor_contrato - $contrato->valor_faturado, 2, ',', '.') }}</p>
        
        <form method="POST" action="{{ route('contratos.medicoes.store', $contrato->id) }}">
            @csrf

            <hr>
            <h5>1. Itens da Medição (Mão de Obra e Locações)</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="tabela-itens">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Item (Serviço / Equipamento)</th>
                            <th>Quantidade</th>
                            <th>Valor Unitário (R$)</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="itens[0][tipo_item]" class="form-control tipo-item" required>
                                    <option value="Servico">Serviço (Mão de Obra)</option>
                                    <option value="Locacao">Locação (Equipamento)</option>
                                </select>
                            </td>
                            <td>
                                <select name="itens[0][servico_id]" class="form-control servico-select">
                                    <option value="">Selecione o Serviço</option>
                                    @foreach($servicos as $s)
                                        <option value="{{ $s->id }}">{{ $s->nome }}</option>
                                    @endforeach
                                </select>
                                <select name="itens[0][produto_id]" class="form-control produto-select" style="display:none;">
                                    <option value="">Selecione o Equipamento</option>
                                    @foreach($produtos as $p)
                                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.01" name="itens[0][quantidade]" class="form-control" value="1" required></td>
                            <td><input type="text" name="itens[0][valor_unitario]" class="form-control money" required></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)">X</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary btn-sm" onclick="adicionarItem()">+ Adicionar Item</button>
            </div>

            <hr>
            <h5>2. Condição de Pagamento e Financeiro</h5>
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Categoria da Conta (Plano de Contas)</label>
                    <select name="categoria_conta_id" class="form-control" required>
                        <option value="">Selecione a Categoria</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Tipo de Pagamento</label>
                    <select name="tipo_pagamento" class="form-control" required>
                        <option value="">Selecione o Tipo</option>
                        @foreach($tiposPagamento as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Valor Total da Medição (R$)</label>
                    <input type="text" name="valor_total" class="form-control money" required>
                </div>
            </div>

            <hr>
            <h5>3. Parcelas e Vencimentos</h5>
            <div id="container-parcelas">
                <div class="row parcela-row mb-2">
                    <div class="col-md-5">
                        <label>Vencimento</label>
                        <input type="date" name="parcelas[0][vencimento]" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label>Valor da Parcela (R$)</label>
                        <input type="text" name="parcelas[0][valor]" class="form-control money" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removerParcela(this)">Remover</button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" onclick="adicionarParcela()">+ Adicionar Parcela</button>

            <div class="row mt-4">
                <div class="col-md-12 text-right">
                    <a href="{{ route('contratos.medicoes.index', $contrato->id) }}" class="btn btn-danger">Voltar</a>
                    <button type="submit" class="btn btn-success">Gerar Medição e Integrar Financeiro</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Script simples para gerenciar linhas dinâmicas de itens e parcelas
    let itemIdx = 1;
    function adicionarItem() {
        let html = `<tr>
            <td>
                <select name="itens[${itemIdx}][tipo_item]" class="form-control tipo-item" onchange="mudarTipoItem(this)" required>
                    <option value="Servico">Serviço (Mão de Obra)</option>
                    <option value="Locacao">Locação (Equipamento)</option>
                </select>
            </td>
            <td>
                <select name="itens[${itemIdx}][servico_id]" class="form-control servico-select">
                    <option value="">Selecione o Serviço</option>
                    @foreach($servicos as $s)<option value="{{ $s->id }}">{{ $s->nome }}</option>@endforeach
                </select>
                <select name="itens[${itemIdx}][produto_id]" class="form-control produto-select" style="display:none;">
                    <option value="">Selecione o Equipamento</option>
                    @foreach($produtos as $p)<option value="{{ $p->id }}">{{ $p->nome }}</option>@endforeach
                </select>
            </td>
            <td><input type="number" step="0.01" name="itens[${itemIdx}][quantidade]" class="form-control" value="1" required></td>
            <td><input type="text" name="itens[${itemIdx}][valor_unitario]" class="form-control money" required></td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)">X</button></td>
        </tr>`;
        document.querySelector('#tabela-itens tbody').insertAdjacentHTML('beforeend', html);
        itemIdx++;
    }

    function removerLinha(btn) {
        btn.closest('tr').remove();
    }

    let parcelaIdx = 1;
    function adicionarParcela() {
        let html = `<div class="row parcela-row mb-2">
            <div class="col-md-5">
                <input type="date" name="parcelas[${parcelaIdx}][vencimento]" class="form-control" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="parcelas[${parcelaIdx}][valor]" class="form-control money" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="removerParcela(this)">Remover</button>
            </div>
        </div>`;
        document.getElementById('container-parcelas').insertAdjacentHTML('beforeend', html);
        parcelaIdx++;
    }

    function removerParcela(btn) {
        btn.closest('.parcela-row').remove();
    }

    // Alterna entre select de serviço e produto baseado no tipo escolhido
    document.addEventListener('change', function(e) {
        if(e.target.classList.contains('tipo-item')) {
            let tr = e.target.closest('tr');
            let servicoSelect = tr.querySelector('.servico-select');
            let produtoSelect = tr.querySelector('.produto-select');
            if(e.target.value === 'Servico') {
                servicoSelect.style.display = 'block';
                servicoSelect.setAttribute('required', 'required');
                produtoSelect.style.display = 'none';
                produtoSelect.removeAttribute('required');
            } else {
                produtoSelect.style.display = 'block';
                produtoSelect.setAttribute('required', 'required');
                servicoSelect.style.display = 'none';
                servicoSelect.removeAttribute('required');
            }
        }
    });
</script>
@endsection