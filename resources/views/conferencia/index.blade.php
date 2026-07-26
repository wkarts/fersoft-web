@extends('default.layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Conferência Fiscal e Financeira</h3>
    <div>
        <!-- Modificado para links diretos chamando uma função JavaScript segura -->
        <button type="button" onclick="exportarRotina('/conferencia/imprimir', true)" class="btn btn-sm btn-danger">
            <i class="fa fa-file-pdf"></i> Imprimir PDF
        </button>
        <button type="button" onclick="exportarRotina('/conferencia/exportar-excel', false)" class="btn btn-sm btn-success">
            <i class="fa fa-file-excel"></i> Excel
        </button>
    </div>
</div>

    <div class="card-body">

        @if(session('sucesso'))
            <div class="alert alert-success">{{ session('sucesso') }}</div>
        @endif

        <!-- Formulário de Filtros -->
        <form id="form-filtros" method="GET" action="/conferencia" class="mb-4 bg-light p-3 border rounded shadow-sm">
            <div class="row">
                <div class="col-md-2">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial', date('Y-m-01')) }}">
                </div>
                <div class="col-md-2">
                    <label>Data Final</label>
                    <input type="date" name="data_final" class="form-control" value="{{ request('data_final', date('Y-m-t')) }}">
                </div>
                <div class="col-md-3">
                    <label>Cliente / Fornecedor</label>
                    <input type="text" name="cliente" class="form-control" placeholder="Buscar..." value="{{ request('cliente') }}">
                </div>
                <div class="col-md-2">
                    <label>Matriz / Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">TODAS</option>
                        <option value="matriz" {{ request('filial_id') === 'matriz' ? 'selected' : '' }}>Somente MATRIZ (Null)</option>
                        @foreach($listaFiliais as $filial)
                            <option value="{{ $filial->id }}" {{ request('filial_id') == $filial->id ? 'selected' : '' }}>
                                {{ $filial->descricao ?? 'Filial '.$filial->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Tipo de Nota</label>
                    <select name="tipo_nota" class="form-control">
                        <option value="">TODAS AS NOTAS</option>
                        <option value="NFe" {{ request('tipo_nota') == 'NFe' ? 'selected' : '' }}>NF-e (Vendas)</option>
                        <option value="NFCe" {{ request('tipo_nota') == 'NFCe' ? 'selected' : '' }}>NFC-e (Venda Caixa)</option>
                        <option value="DEVOLUCAO" {{ request('tipo_nota') == 'DEVOLUCAO' ? 'selected' : '' }}>Devoluções</option>
                        <option value="CTe" {{ request('tipo_nota') == 'CTe' ? 'selected' : '' }}>CT-e</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-8">
                    <label>Natureza da Operação</label>
                    <select name="natureza_id" class="form-control">
                        <option value="">TODAS AS NATUREZAS</option>
                        @foreach($listaNaturezas as $nat)
                            <option value="{{ $nat->id }}" {{ request('natureza_id') == $nat->id ? 'selected' : '' }}>
                                {{ $nat->CFOP ?? '' }} - {{ $nat->natureza }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Situação</label>
                    <select name="estado" class="form-control">
                        <option value="">TODOS OS STATUS</option>
                        <option value="APROVADO" {{ request('estado') == 'APROVADO' ? 'selected' : '' }}>AUTORIZADO</option>
                        <option value="CANCELADO" {{ request('estado') == 'CANCELADO' ? 'selected' : '' }}>CANCELADO</option>
                        <option value="REJEITADO" {{ request('estado') == 'REJEITADO' ? 'selected' : '' }}>REJEITADO</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>

        <!-- Cards de Resumo e Totalizadores por Tipo na Grade -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card p-2 border-primary">
                    <small class="text-muted font-weight-bold">Total Geral ({{ $qtdNotas }})</small>
                    <h5 class="text-primary text-right font-weight-bold">R$ {{ number_format($totalValor, 2, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2 border-dark">
                    <small class="text-muted font-weight-bold">Total NF-e</small>
                    <h5 class="text-dark text-right font-weight-bold">R$ {{ number_format($totalNFe, 2, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2 border-info">
                    <small class="text-muted font-weight-bold">Total NFC-e</small>
                    <h5 class="text-info text-right font-weight-bold">R$ {{ number_format($totalNFCe, 2, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2 border-warning">
                    <small class="text-muted font-weight-bold">Total CT-e</small>
                    <h5 class="text-warning text-right font-weight-bold">R$ {{ number_format($totalCTe, 2, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2 border-secondary">
                    <small class="text-muted font-weight-bold">Total Devoluções</small>
                    <h5 class="text-secondary text-right font-weight-bold">R$ {{ number_format($totalDevolucao, 2, ',', '.') }}</h5>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card p-2 border-danger">
                    <small class="text-muted font-weight-bold">Em Aberto</small>
                    <h5 class="text-danger text-right font-weight-bold">R$ {{ number_format($totalAberto, 2, ',', '.') }}</h5>
                </div>
            </div>
        </div>

        <!-- FORMULÁRIO DE AÇÃO EM MASSA -->
        <form method="POST" action="/conferencia/integrar-massa">
            @csrf

            <div class="mb-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Deseja gerar o contas a receber para os itens marcados?')">
                    <i class="fa fa-money-bill"></i> Lançar no Contas a Receber
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered table-striped" style="font-size: 13px;">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                            <th>Data</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-right">Número</th>
                            <th>Cliente / Fornecedor</th>
                            <th class="text-center">Itens</th>
                            <th class="text-right">Valor Nota</th>
                            <th class="text-right">Recebido</th>
                            <th class="text-right">Aberto</th>
                            <th class="text-center">Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notas as $n)
                        @php
                            $isCancelada = in_array($n['situacao'], ['CANCELADO', 'CANCELADA', 'REJEITADO', 'REJEITADA']);
                            $podeIntegrar = !$isCancelada && !$n['bloqueia_integracao'] && !$n['integrado'];
                        @endphp
                        <tr>
                            <td class="text-center align-middle">
                                @if($podeIntegrar)
                                    <input type="checkbox" name="notas_integrar[]" value="{{ $n['chave'] }}" class="checkItem">
                                @endif
                            </td>
                            <td class="align-middle">
                                <span onclick="abrirDrillDown('{{ $n['numero'] }}', '{{ $n['tipo'] }}')" style="cursor:pointer; color:#0056b3; font-weight:bold;">
                                    {{ \Carbon\Carbon::parse($n['data'])->format('d/m/Y') }}
                                </span>
                            </td>
                            <td class="text-center font-weight-bold align-middle">{{ $n['tipo'] }}</td>
                            <td class="text-right align-middle font-weight-bold">{{ $n['numero'] }}</td>
                            <td class="align-middle">{{ $n['cliente'] }}</td>
                            <td class="text-center align-middle">{{ $n['qtd_itens'] > 0 ? number_format($n['qtd_itens'], 0, ',', '.') : '--' }}</td>
                            <td class="text-right font-weight-bold align-middle">R$ {{ number_format($n['valor'], 2, ',', '.') }}</td>
                            <td class="text-right text-success align-middle">R$ {{ number_format($n['valor_recebido'], 2, ',', '.') }}</td>
                            <td class="text-right text-danger align-middle">R$ {{ number_format($n['valor_aberto'], 2, ',', '.') }}</td>

                            <td class="text-center align-middle">
                                @if(in_array($n['situacao'], ['APROVADO', 'AUTORIZADO']))
                                    <span class="badge badge-success">{{ $n['situacao'] }}</span>
                                @elseif($isCancelada)
                                    <span class="badge badge-danger">{{ $n['situacao'] }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ $n['situacao'] }}</span>
                                @endif

                                <div class="mt-1">
                                    @if($n['bloqueia_integracao'])
                                        <small class="text-muted"><i class="fa fa-ban"></i> Não Integrável</small>
                                    @elseif($n['integrado'])
                                        <small class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> Integrado</small>
                                    @elseif(!$isCancelada)
                                        <small class="text-warning font-weight-bold"><i class="fa fa-exclamation-circle"></i> À Vista Pendente</small>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>

    </div>
</div>

<!-- Modal de Drill-Down Simples -->
<div class="modal fade" id="modalDrill" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header"><h5>Detalhamento do Documento</h5></div>
            <div class="modal-body" id="modalBody">Carregando dados...</div>
        </div>
    </div>
</div>


@endsection
@section('javascript')
<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.checkItem');
        for (let checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });

    function abrirDrillDown(numero, tipo) {
        document.getElementById('modalBody').innerHTML = "Visualizando detalhes da nota número: <strong>" + numero + "</strong> do tipo <strong>" + tipo + "</strong>.";
        $('#modalDrill').modal('show');
    }
  function exportarRotina(urlBase, abrirNovaAba) {
        // Captura o formulário de filtros existente na tela
        // Certifique-se de que sua tag <form> de filtros tenha id="form-filtros"
        const form = document.getElementById('form-filtros');
        if (!form) {
            alert('Erro: Formulário de filtros não encontrado na página.');
            return;
        }

        // Transforma os inputs preenchidos em parâmetros de URL (ex: ?data_inicial=...&cliente=...)
        const formData = new FormData(form);
        const params = new URLSearchParams(formData).toString();

        const urlFinal = urlBase + '?' + params;

        if (abrirNovaAba) {
            window.open(urlFinal, '_blank'); // Abre o PDF em nova aba para impressão
        } else {
            window.location.href = urlFinal; // Dispara o download do Excel na mesma aba
        }
    }
</script>
@endsection
