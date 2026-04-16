@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Estoque Consolidado - {{ $mes }}/{{ $ano }}</h3>
        <div class="card-toolbar">
            <button class="btn btn-light-primary font-weight-bold" onclick="window.print()">
                <i class="la la-print"></i> Imprimir Relatório
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-5">
            <div class="row">
                <div class="col-md-2">
                    <select name="mes" class="form-control">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{$m}}" {{$mes == $m ? 'selected' : ''}}>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center">
                <thead>
                    <tr>
                        <th style="width: 250px">Produto</th>
                        <th>S. Inicial</th>
                        <th>Entradas (+)</th>
                        <th>Saídas (-)</th>
                        <th>Saldo Atual</th>
                        <th>Vl. Compra</th>
                        <th>Total Custo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estoque as $e)
                    <tr>
                        <td class="font-weight-bolder">{{ $e->produto_nome }}</td>
                        <td>{{ number_format($e->saldo_inicial ?? 0, 2, ',', '.') }}</td>
                        <td class="text-success">+{{ number_format($e->total_entradas ?? 0, 2, ',', '.') }}</td>
                        <td class="text-danger">-{{ number_format($e->total_saidas ?? 0, 2, ',', '.') }}</td>
                        <td class="font-weight-boldest {{ $e->quantidade < 0 ? 'text-danger' : '' }}">
                            {{ number_format($e->quantidade, 2, ',', '.') }}
                        </td>
                        <td>R$ {{ number_format($e->valor_compra, 2, ',', '.') }}</td>
                        <td class="bg-light">
                            <strong>R$ {{ number_format($e->quantidade * $e->valor_compra, 2, ',', '.') }}</strong>
                        </td>
                        <td>
                            <a href="/estoque/historico/{{$e->id}}" class="btn btn-sm btn-clean btn-icon" title="Ver Extrato Detalhado">
                                <i class="la la-list-ul text-primary"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $estoque->links() }}
        </div>
    </div>
</div>
@endsection