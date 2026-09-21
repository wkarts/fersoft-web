@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Contrato #{{ $contratoEng->numero_contrato ?: $contratoEng->id }}</h3>
            <div class="text-muted">{{ optional($cliente)->razao_social }}</div>
        </div>
        <div>
            <a href="/contratos/medicoes/create/{{ $contratoEng->id }}" class="btn btn-success">Nova Medição</a>
            <a href="/contratos/edit/{{ $contratoEng->id }}" class="btn btn-warning">Editar</a>
            <a href="/contratos" class="btn btn-light">Voltar</a>
        </div>
    </div>

    @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
    @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

    <div class="row">
        @foreach([
            ['Valor do Contrato', $valorTotal, 'primary'],
            ['Receitas / Medições', $totalReceitas, 'success'],
            ['Despesas', $totalDespesas, 'danger'],
            ['Saldo a Faturar', $saldoAFaturar, 'info'],
            ['Lucro / Prejuízo', $lucroPrejuizo, $lucroPrejuizo >= 0 ? 'success' : 'danger'],
        ] as $card)
            <div class="col-lg col-md-4 mb-3">
                <div class="card card-custom h-100"><div class="card-body">
                    <div class="text-muted">{{ $card[0] }}</div>
                    <div class="h4 text-{{ $card[2] }}">R$ {{ number_format((float)$card[1],2,',','.') }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card card-custom gutter-b">
                <div class="card-header"><h4 class="card-title">Medições / Faturamentos</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>#</th><th>Data</th><th>Status</th><th class="text-right">Valor</th><th></th></tr></thead>
                        <tbody>
                        @forelse($medicoes as $m)
                            <tr>
                                <td>{{ $m->id }}</td>
                                <td>{{ $m->data_faturamento ? CarbonCarbon::parse($m->data_faturamento)->format('d/m/Y') : '—' }}</td>
                                <td>{{ $m->status }}</td>
                                <td class="text-right">R$ {{ number_format((float)$m->valor_total,2,',','.') }}</td>
                                <td><a target="_blank" href="{{ route('contratos.medicoes.imprimir',$m->id) }}" class="btn btn-sm btn-primary">Imprimir</a></td>
                            </tr>
                        @empty<tr><td colspan="5" class="text-center text-muted">Nenhuma medição.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-custom gutter-b">
                <div class="card-header"><h4 class="card-title">Despesas vinculadas</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Referência</th><th>Fornecedor</th><th>Categoria</th><th class="text-right">Valor</th></tr></thead>
                        <tbody>
                        @forelse($despesas as $d)
                            <tr>
                                <td>{{ $d->referencia }}</td>
                                <td>{{ $d->fornecedor_nome ?: '—' }}</td>
                                <td>{{ $d->categoria_nome ?: '—' }}</td>
                                <td class="text-right">R$ {{ number_format((float)$d->valor_integral,2,',','.') }}</td>
                            </tr>
                        @empty<tr><td colspan="4" class="text-center text-muted">Nenhuma despesa vinculada.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card card-custom gutter-b">
                <div class="card-header"><h4 class="card-title">Equipe da Obra</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('contratos.alocar-funcionario',$contratoEng->id) }}" class="mb-4">
                        @csrf
                        <div class="row">
                            <div class="col-md-7 form-group">
                                <select name="funcionario_id" class="form-control select2" required>
                                    <option value="">Funcionário</option>
                                    @foreach($todosFuncionarios as $func)
                                        <option value="{{ $func->id }}">{{ $func->nome }} - {{ $func->cargo_nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 form-group"><input type="date" name="data_alocacao" class="form-control" value="{{ date('Y-m-d') }}"></div>
                        </div>
                        <button class="btn btn-primary btn-sm">Alocar funcionário</button>
                    </form>
                    <table class="table table-sm">
                        @forelse($equipeAlocada as $e)
                            <tr>
                                <td><strong>{{ $e->nome }}</strong><br><small>{{ $e->cargo_nome }}</small></td>
                                <td>{{ $e->data_alocacao ? CarbonCarbon::parse($e->data_alocacao)->format('d/m/Y') : '—' }}</td>
                                <td><a href="{{ route('contratos.desalocar-funcionario',$e->alocacao_id) }}" class="btn btn-xs btn-danger" onclick="return confirm('Desalocar funcionário?')">Remover</a></td>
                            </tr>
                        @empty<tr><td class="text-muted">Nenhum funcionário alocado.</td></tr>@endforelse
                    </table>
                </div>
            </div>

            <div class="card card-custom"><div class="card-body">
                <div class="text-muted">Margem atual</div>
                <div class="display-4 {{ $margemLucro < 15 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$margemLucro,1,',','.') }}%</div>
                <hr>
                <div><strong>Obra:</strong> {{ $contratoEng->endereco_obra }} {{ $contratoEng->numero_obra }}</div>
                <div><strong>Status:</strong> {{ $contratoEng->status }}</div>
                <div><strong>Período:</strong> {{ optional($contratoEng->data_inicio)->format('d/m/Y') }} a {{ optional($contratoEng->data_fim)->format('d/m/Y') ?: 'indeterminado' }}</div>
            </div></div>
        </div>
    </div>
</div>
@endsection
