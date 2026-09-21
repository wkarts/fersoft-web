@extends('default.layout')
@section('content')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-primary font-weight-bold m-0">
                <i class="fa fa-chart-line"></i> DRE e Histórico - Contrato Nº {{ $contratoEng->numero_contrato ?? $contratoEng->id }}
            </h4>
            <a href="/contratos" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- Cards de DRE (Receitas x Despesas x Lucro Real) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">VALOR DO CONTRATO</span>
                    <h3 class="text-secondary font-weight-bold m-0">R$ {{ number_format($valorTotal, 2, ',', '.') }}</h3>
                    <small class="text-muted">Saldo a Faturar: R$ {{ number_format($saldoAFaturar, 2, ',', '.') }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">FATURADO (RECEITAS)</span>
                    <h3 class="text-success font-weight-bold m-0">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</h3>
                    <small class="text-success">{{ $medicoes->count() }} Medição(ões)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">CUSTOS / DESPESAS</span>
                    <h3 class="text-danger font-weight-bold m-0">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</h3>
                    <small class="text-danger">{{ $despesas->count() }} Lançamento(s)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light border-0 shadow-sm p-3">
                    <span class="text-muted small font-weight-bold">LUCRO / PREJUÍZO REAL</span>
                    <h3 class="{{ $lucroPrejuizo >= 0 ? 'text-primary' : 'text-danger' }} font-weight-bold m-0">
                        R$ {{ number_format($lucroPrejuizo, 2, ',', '.') }}
                    </h3>
                    <small class="{{ $margemLucro >= 0 ? 'text-primary' : 'text-danger' }} font-weight-bold">
                        Margem: {{ number_format($margemLucro, 1, ',', '.') }}%
                    </small>
                </div>
            </div>
        </div>

        <!-- Informações do Cliente e Contrato -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="p-3 border rounded bg-white">
                    <strong>Cliente:</strong> {{ is_object($cliente) ? ($cliente->razao_social ?? $cliente->nome ?? 'N/D') : 'N/D' }}<br>
                    <strong>CNPJ/CPF:</strong> {{ is_object($cliente) ? ($cliente->cpf_cnpj ?? $cliente->cnpj ?? $cliente->cpf ?? 'N/D') : 'N/D' }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded bg-white">
                    <strong>Data de Início:</strong> {{ !empty($contratoEng->data_inicio) ? date('d/m/Y', strtotime($contratoEng->data_inicio)) : 'N/D' }}<br>
                    <strong>Status:</strong> <span class="badge badge-success">{{ $contratoEng->status ?? 'Ativo' }}</span>
                </div>
            </div>
        </div>

        <!-- Equipe Alocada na Obra -->
        <div class="card border rounded mb-4 bg-white">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <h5 class="m-0 font-weight-bold text-dark"><i class="fa fa-users text-primary"></i> Funcionários Alocados</h5>
                <button type="button" class="btn btn-sm btn-success font-weight-bold" data-toggle="modal" data-target="#modalAlocarFuncionario">
                    <i class="fa fa-user-plus"></i> Alocar Funcionário
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Cargo/Função</th>
                                <th>Data de Alocação</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipeAlocada ?? [] as $func)
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $func->nome }}</td>
                                <td>{{ $func->cargo_nome }}</td>
                                <td>{{ date('d/m/Y', strtotime($func->data_alocacao)) }}</td>
                                <td class="text-center">
                                    <a href="/contratos/desalocar-funcionario/{{ $func->alocacao_id }}" class="btn btn-sm btn-light-danger" onclick="return confirm('Deseja remover este funcionário da obra?')"><i class="fa fa-user-minus"></i> Desalocar</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhum funcionário alocado no momento.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Histórico de Medições (Receitas) -->
        <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-file-invoice-dollar text-success"></i> Receitas (Medições Faturadas)</h5>
        <div class="table-responsive mb-4">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th>ID Medição</th>
                        <th>Data de Emissão</th>
                        <th>Status</th>
                        <th class="text-right">Valor (R$)</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicoes as $m)
                    <tr>
                        <td class="font-weight-bold">#{{ $m->id }}</td>
                        <td>{{ !empty($m->created_at) ? date('d/m/Y H:i', strtotime($m->created_at)) : 'N/D' }}</td>
                        <td><span class="badge badge-success">{{ $m->status ?? 'Finalizado' }}</span></td>
                        <td class="text-right font-weight-bold text-success">R$ {{ number_format($m->valor_total ?? 0, 2, ',', '.') }}</td>
                        <td class="text-center">
                            <a href="{{ route('contratos.medicoes.imprimir', $m->id) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-print"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma medição gerada até o momento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Histórico de Despesas (Contas a Pagar do Contrato) -->
        <h5 class="text-secondary font-weight-bold mb-3"><i class="fa fa-receipt text-danger"></i> Despesas Diretas Lançadas (Contas a Pagar)</h5>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>ID Conta</th>
                        <th>Fornecedor</th>
                        <th>Categoria / Conta</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th class="text-right">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($despesas as $d)
                    <tr>
                        <td class="font-weight-bold">#{{ $d->id }}</td>
                        <td>{{ $d->fornecedor_nome ?? 'Fornecedor Vazio' }}</td>
                        <td>{{ $d->categoria_nome ?? 'Despesa de Obra' }}</td>
                        <td>{{ !empty($d->data_vencimento) ? date('d/m/Y', strtotime($d->data_vencimento)) : 'N/D' }}</td>
                        <td>
                            <span class="badge badge-{{ ($d->status ?? '') == 'Pago' ? 'success' : 'warning' }}">
                                {{ $d->status ?? 'Pendente' }}
                            </span>
                        </td>
                        <td class="text-right font-weight-bold text-danger">R$ {{ number_format($d->valor_integral ?? $d->valor ?? 0, 2, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma despesa vinculada a este contrato até o momento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Alocar Funcionário -->
<div class="modal fade" id="modalAlocarFuncionario" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="POST" action="/contratos/alocar-funcionario/{{ $contratoEng->id }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold"><i class="fa fa-user-plus text-success"></i> Alocar Funcionário no Contrato</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Selecione o Funcionário</label>
                        <select name="funcionario_id" class="form-control" required style="width: 100%;">
                            <option value="">-- Escolha o funcionário --</option>
                            @foreach($todosFuncionarios ?? [] as $f)
                                <option value="{{ $f->id }}">{{ $f->nome }} - [{{ $f->cargo_nome }}]</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Data da Entrada/Alocação</label>
                        <input type="date" name="data_alocacao" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success font-weight-bold"><i class="fa fa-check"></i> Confirmar Alocação</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection