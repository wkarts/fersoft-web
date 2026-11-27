@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush

@section('content')
    <div class="fer-page fer-operational fer-list card card-custom gutter-b">
        <div class="card-body">
            <div class="row align-items-center mb-4">
                <div class="col-lg-8">
                    <h3><i class="la la-sitemap text-primary"></i> {{ $title }}</h3>
                    <span class="text-muted">Gestão de lotes brutos, progresso de beneficiamento e balanço de massa (quebras/perdas).</span>
                </div>
                <div class="col-lg-4 text-right">
                    <form method="GET" action="{{ route('producao.gerenciamento') }}" class="form-inline float-right">
                        <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Pesquisar Lote, NF, MTR..." value="{{ request('search') }}">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="la la-search"></i> Buscar</button>
                    </form>
                </div>
            </div>

            <!-- FILTRAGEM RÁPIDA DE STATUS -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <a href="{{ route('producao.gerenciamento') }}" class="btn btn-sm btn-light-primary font-weight-bold mr-2">
                        <i class="la la-list"></i> Todos os Lotes
                    </a>
                    <a href="{{ route('producao.gerenciamento', ['status' => 'em_corte']) }}" class="btn btn-sm btn-light-warning font-weight-bold mr-2">
                        <i class="la la-clock"></i> Em Andamento
                    </a>
                    <a href="{{ route('producao.gerenciamento', ['status' => 'transformado']) }}" class="btn btn-sm btn-light-success font-weight-bold">
                        <i class="la la-check-circle"></i> Finalizados / Concluídos
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="thead-light">
                    <tr>
                        <th>Código do Lote (Dossiê)</th>
                        <th>Origem (Fornecedor / Matriz)</th>
                        <th>Documentos (NF-e / MTR)</th>
                        <th>Balanço de Massa (Progresso)</th>
                        <th>Status</th>
                        <th class="text-center">Ações / Fiscal (CFOP 5927)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($lotes as $lote)
                        @php
                            $produzido = $lote->itens ? $lote->itens->sum('peso_liquido') : 0;
                            $entrada   = (float) $lote->peso_entrada;
                            $excedente = $produzido - $entrada;
                            $porcentagem = $entrada > 0 ? round(($produzido / $entrada) * 100, 1) : 0;
                            $estourou  = $excedente > 0;
                        @endphp
                        <tr>
                            <!-- Clicar no código abre o Dossiê/Histórico Completo -->
                            <td>
                                <a href="{{ route('producao.gerenciamento.dossie', $lote->id) }}" class="text-primary font-weight-bolder" title="Ver Dossiê e Histórico Completo">
                                    <i class="fa fa-search-dollar"></i> {{ $lote->codigo_lote }}
                                </a><br>
                                <small class="text-muted">{{ date('d/m/Y H:i', strtotime($lote->created_at)) }}</small>
                            </td>
                            <td>
                                {{ optional($lote->fornecedor)->razao_social ?? 'Não informado' }}<br>
                                <small class="text-muted">CNPJ: {{ $lote->cnpj_origem ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-secondary">NF: {{ $lote->nfe_entrada ?? '---' }}</span>
                                <span class="badge badge-info">MTR: {{ $lote->mtr_entrada ?? '---' }}</span>
                            </td>
                            <td style="width: 25%;">
                                <div class="d-flex justify-content-between text-dark font-weight-bold mb-1">
                                    <span class="{{ $estourou ? 'text-danger font-weight-bolder' : '' }}">
                                        Prod: {{ number_format($produzido, 2, ',', '.') }} kg
                                    </span>
                                    <span>Entrada: {{ number_format($entrada, 2, ',', '.') }} kg</span>
                                </div>

                                @if($estourou)
                                    <!-- Alerta Vermelho de Sobrecarga / Inconsistência de Peso -->
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-danger font-weight-bolder mt-1 d-block">
                                        <i class="fa fa-exclamation-triangle"></i> Atenção: Excesso de {{ number_format($excedente, 2, ',', '.') }} kg (+{{ number_format($porcentagem - 100, 1, ',', '.') }}%)
                                    </small>
                                @else
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, $porcentagem) }}%;" aria-valuenow="{{ min(100, $porcentagem) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Aproveitamento: {{ $porcentagem }}%</small>
                                @endif
                            </td>
                            <td>
                                @if($lote->status === 'novo')
                                    <span class="badge badge-warning">Aguardando Corte</span>
                                @elseif($lote->status === 'em_corte')
                                    <span class="badge badge-primary">Em Corte / Produção</span>
                                @else
                                    <span class="badge badge-success">Encerrado / Transformado</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($lote->status !== 'transformado' && $lote->status !== 'concluido')
                                    <!-- Botão de Concluir (Lote Aberto) -->
                                    <button type="button" class="btn btn-sm btn-success btn-concluir mb-1" data-id="{{ $lote->id }}">
                                        <i class="la la-check-circle"></i> Concluir Lote
                                    </button>
                                @else
                                    <!-- Botão de Reabrir com Senha 1982 (Lote Fechado) -->
                                    <button type="button" class="btn btn-sm btn-secondary btn-reabrir mb-1" data-id="{{ $lote->id }}">
                                        <i class="la la-undo"></i> Reabrir Lote
                                    </button>
                                @endif

                                @if($lote->nfe_perda_status === 'pendente')
                                    <button type="button" class="btn btn-sm btn-warning btn-nfe-perda d-block mx-auto mt-1" data-id="{{ $lote->id }}">
                                        <i class="la la-file-invoice"></i> Emitir NF-e Perda (5927)
                                    </button>
                                @elseif($lote->nfe_perda_status === 'emitida')
                                    <span class="badge badge-dark d-block mt-1">NF-e 5927 Emitida</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Nenhum lote de rastreabilidade encontrado.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $lotes->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <script>
        // Script AJAX para concluir o lote
        document.querySelectorAll('.btn-concluir').forEach(button => {
            button.addEventListener('click', function() {
                let id = this.getAttribute('data-id');
                if (confirm('Deseja encerrar este lote e apurar a quebra/perda industrial automaticamente?')) {
                    fetch(`/producao/gerenciamento/concluir/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            alert(data.success);
                            location.reload();
                        } else {
                            alert(data.error || 'Erro ao concluir lote.');
                        }
                    });
                }
            });
        });

        // Script AJAX para reabrir lote com senha protegida (1982)
        document.querySelectorAll('.btn-reabrir').forEach(button => {
            button.addEventListener('click', function() {
                let id = this.getAttribute('data-id');

                swal({
                    title: "ACESSO RESTRITO (SENHA 1982)",
                    text: "Digite a senha de gestor para reabrir este lote e estornar o estoque de perda:",
                    content: {
                        element: "input",
                        attributes: {
                            placeholder: "Digite a senha",
                            type: "password",
                        },
                    },
                    buttons: ["Cancelar", "Confirmar Reabertura"],
                    icon: "warning",
                }).then((senha) => {
                    if (senha === null) return;

                    if (senha === "1982") {
                        fetch(`/producao/gerenciamento/reabrir/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ senha: senha })
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                swal("Sucesso!", data.message, "success").then(() => {
                                    location.reload();
                                });
                            } else {
                                swal("Erro", data.error || 'Erro ao reabrir lote.', "error");
                            }
                        });
                    } else {
                        swal("Acesso Negado", "Senha incorreta!", "error");
                    }
                });
            });
        });

        // Script AJAX para gerar NF-e de Perda (CFOP 5927)
        document.querySelectorAll('.btn-nfe-perda').forEach(button => {
            button.addEventListener('click', function() {
                let id = this.getAttribute('data-id');
                if (confirm('Deseja gerar a NF-e de baixa por perda (CFOP 5927) destinada à própria empresa?')) {
                    fetch(`/producao/gerenciamento/nfe-perda/${id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.error || 'Erro ao emitir NF-e de perda.');
                        }
                    });
                }
            });
        });
    </script>
@endsection
