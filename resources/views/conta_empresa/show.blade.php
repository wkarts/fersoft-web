@extends('default.layout', ['title' => 'Movimentações conta ' . $item->nome])

@section('content')

{{-- (GRUPO) CABEÇALHO: Informações da conta e botões de ação --}}
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))
            <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
        @endif
        @if(session('mensagem_erro'))
            <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
        @endif

        <div class="row align-items-center">
            <div class="col-md-7">
                <h3 class="mb-0">Conta: {{ $item->nome }}</h3>
                <p class="text-muted mb-0">Agência: {{ $item->agencia }} | Conta: {{ $item->conta }}</p>
            </div>
            
            <div class="col-md-5 text-right">
                {{-- NOVO BOTÃO DE SINCRONIZAÇÃO --}}
                <button onclick="confirmarSincronismo({{ $item->id }})" class="btn btn-warning font-weight-bold">
                    <i class="la la-refresh"></i> Sincronizar Histórico
                </button>

                <a href="{{ route('contas-empresa.imprimir-extrato', $item->id) }}?data_inicio={{$data_inicio}}&data_final={{$data_final}}" target="_blank" class="btn btn-info">
                    <i class="fa fa-print"></i> Imprimir Extrato
                </a>
            </div>

            <div class="col-12 mt-3">
                <a href="{{ route('contas-empresa.index') }}" class="btn btn-light-primary font-weight-bold">
                    <i class="la la-arrow-left"></i> Voltar para Contas
                </a>
            </div>
        </div>
    </div>
</div>

{{-- (GRUPO) LANÇAMENTO MANUAL: Formulário para inserir entradas/saídas avulsas --}}
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Novo Lançamento Manual</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('item-conta.store') }}" method="post">
            @csrf
            <input type="hidden" name="conta_id" value="{{ $item->id }}">
            <div class="row">
                <div class="col-md-2">
                    <label class="font-weight-bold">Data</label>
                    <input type="date" name="data_pagamento" class="form-control" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold">Descrição</label>
                    <input type="text" name="descricao" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold">Valor</label>
                    <input type="text" name="valor" class="form-control money" required>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold">Tipo</label>
                    <select name="tipo" class="form-control" required>
                        <option value="entrada">Entrada (Crédito)</option>
                        <option value="saida">Saída (Débito)</option>
                    </select>
                </div>
              
                 <div class="form-group col-md-6">
                    <label>Conta de Destino (Para Transferência)</label>
                    <select name="conta_destino_id" class="form-control custom-select">
                        <option value="">Nenhuma (Lançamento Simples)</option>
                        @foreach($contas as $c)
                            @if($c->id != $item->id) <option value="{{ $c->id }}">{{ $c->nome }}</option>
                            @endif
                        @endforeach
                    </select>
                    <small class="text-muted text-info">Se selecionar, o sistema criará o lançamento oposto na conta escolhida.</small>
                </div>
              
                <div class="col-md-2">
                    <label class="font-weight-bold">Categoria</label>
                    <select name="plano_conta_id" class="form-control" required>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3 font-weight-bold">Salvar Lançamento</button>
        </form>
    </div>
</div>

{{-- (GRUPO) LISTAGEM / EXTRATO: Tabela de movimentações com filtros --}}
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="table-responsive">
            
            {{-- Filtro de Busca Interno --}}
            <form class="row mb-5" method="get" action="{{ route('contas-empresa.show', [$item->id]) }}">
                <div class="col-md-2">
                    <label class="font-weight-bold">Data inicial</label>
                    <input value="{{ $data_inicio ?? '' }}" type="date" name="data_inicio" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold">Data final</label>
                    <input value="{{ $data_final ?? '' }}" type="date" name="data_final" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold">Tipo</label>
                    <select name="tipo" class="form-control custom-select">
                        <option value="">Selecione</option>
                        <option @if(isset($tipo) && $tipo == 'entrada') selected @endif value="entrada">Entrada</option>
                        <option @if(isset($tipo) && $tipo == 'saida') selected @endif value="saida">Saída</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <br>
                    <button type="submit" class="btn btn-light-primary px-6 font-weight-bold mt-1">
                        <i class="la la-search"></i> Filtrar
                    </button>
                    <a class="btn btn-warning px-6 font-weight-bold mt-1" href="{{ route('contas-empresa.show', [$item->id]) }}">
                        <i class="la la-eraser"></i> Limpar
                    </a>
                </div>
            </form>

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria / Usuário</th>
                        <th class="text-right">Entrada (+)</th>
                        <th class="text-right">Saída (-)</th>
                        <th class="text-right">Saldo</th>
                        <th class="text-right">Ações</th> 
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-light">
                        <td colspan="5"><strong>SALDO ANTERIOR AO PERÍODO</strong></td>
                        <td class="text-right"><strong>R$ {{ moeda($saldo_anterior) }}</strong></td>
                        <td></td>
                    </tr>

                    @php $saldo_acumulado = $saldo_anterior; @endphp

                    @forelse($data as $m)
                        @php
                            if($m->tipo == 'entrada') { $saldo_acumulado += $m->valor; } 
                            else { $saldo_acumulado -= $m->valor; }
                        @endphp

                        <tr>
                            <td>{{ __date($m->data_pagamento ?? $m->created_at) }}</td>
                            <td>{{ $m->descricao }}</td>
                            <td>
                                <small class="d-block text-dark">Cat: {{ $m->categoria->nome ?? 'Sem Categoria' }}</small>
                                <small class="d-block text-muted">User: {{ $m->usuario->nome ?? 'N/A' }}</small>
                            </td>

                            <td class="text-right text-success">
                                {{ $m->tipo == 'entrada' ? '+ R$ ' . moeda($m->valor) : '-' }}
                            </td>
                            <td class="text-right text-danger">
                                {{ $m->tipo == 'saida' ? '- R$ ' . moeda($m->valor) : '-' }}
                            </td>
                            <td class="text-right">
                                <span class="{{ $saldo_acumulado < 0 ? 'text-danger' : 'text-info' }} font-weight-bold">
                                    R$ {{ moeda($saldo_acumulado) }}
                                </span>
                            </td>
                          
                            <td class="text-right">
                                {{-- IMPLEMENTAÇÃO: Botão Imprimir Comprovante Individual --}}
                                <a href="/contaEmpresa/imprimirTransacao/{{ $m->id }}" target="_blank" 
                                   class="btn btn-sm btn-info btn-icon" title="Imprimir Comprovante">
                                    <i class="la la-print"></i>
                                </a>

                                {{-- LÓGICA DE EXCLUSÃO: Só mostra se for MANUAL --}}
                                @if($m->conta_receber_id == null && $m->conta_pagar_id == null)
                                    <button type="button" onclick="excluirLancamentoManual({{ $m->id }})" 
                                            class="btn btn-sm btn-danger btn-icon" title="Excluir lançamento manual">
                                        <i class="la la-trash"></i>
                                    </button>
                                @else
                                    <i class="la la-lock text-muted" title="Lançamento automático (Financeiro)"></i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma movimentação encontrada!</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="col-12 mt-5">
            {{ $data->appends(request()->all())->links() }}
        </div>
    </div>
</div>

<input type="hidden" id="casas_decimais" value="{{ $casasDecimais ?? 2 }}">

{{-- MOVIDO PARA DENTRO DO CONTENT PARA GARANTIR QUE FUNCIONE --}}
<script type="text/javascript">
function confirmarSincronismo(id) {
    // Teste definitivo: se isso não aparecer, o navegador não está lendo a função
    console.log("Chamando sincronismo para ID: " + id); 

    Swal.fire({
        title: 'Reconstruir Histórico?',
        text: "O extrato atual desta conta será APAGADO e refeito com base no financeiro. Confirmar?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3699FF',
        confirmButtonText: 'Sim, Sincronizar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                text: 'Isso pode levar alguns segundos.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            // Usando o helper do Laravel para não errar a URL
            window.location.href = "{{ route('contas-empresa.sincronizar', '') }}/" + id;
        }
    });
}

function excluirLancamentoManual(id) {
    Swal.fire({
        title: 'Excluir Lançamento?',
        text: "Digite a senha de segurança:",
        input: 'password',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        preConfirm: (senha) => {
            if (!senha) { Swal.showValidationMessage('Senha obrigatória'); }
            return senha;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "{{ route('contas-empresa.delete-lancamento', ['id' => ':id']) }}".replace(':id', id) + "?senha=" + result.value;
        }
    });
}
</script>
@endsection