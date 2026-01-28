{{-- resources/views/relatorios/pesagens/analitico_pdf.blade.php --}}
@extends('relatorios.default')

@section('content')

    <h3 class="title" style="text-align:center; margin-bottom: 10px;">
        Relatório Analítico de Pesagens
    </h3>

    @if(!empty($filtros))
        <table class="table table-borderless" style="font-size: 11px; margin-bottom: 10px;">
            <tr>
                <td>
                    @if(!empty($filtros['data_inicial']) || !empty($filtros['data_final']))
                        <strong>Período:</strong>
                        {{ $filtros['data_inicial'] ?? '...' }} até
                        {{ $filtros['data_final'] ?? '...' }}
                        &nbsp;&nbsp;&nbsp;
                    @endif

                    @if(!empty($filtros['tipo_operacao']))
                        <strong>Tipo:</strong>
                        {{ $filtros['tipo_operacao'] === 'entrada' ? 'Entrada (Compra)' : 'Saída (Venda)' }}
                        &nbsp;&nbsp;&nbsp;
                    @endif

                    @if(!empty($filtros['status']))
                        <strong>Status:</strong>
                        {{ ucfirst($filtros['status']) }}
                        &nbsp;&nbsp;&nbsp;
                    @endif

                    @if(!empty($filtros['associacao']))
                        <strong>Associação:</strong>
                        @if($filtros['associacao'] === 'com')
                            Somente com venda/compra
                        @elseif($filtros['associacao'] === 'sem')
                            Somente sem venda/compra
                        @endif
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <table class="table table-bordered" style="font-size: 10px;">
        <thead>
        <tr>
            <th>Data/Hora</th>
            <th>Tipo</th>
            <th>Peso Bruto</th>
            <th>Tara</th>
            <th>Peso Líquido</th>
            <th>Status</th>
            <th>Venda</th>
            <th>Compra</th>
            <th>Placa</th>
            <th>Veículo</th>
            <th>Motorista</th>
            <th>Doc. Motorista</th>
            <th>Filial</th>
        </tr>
        </thead>
        <tbody>
        @forelse($pesagens as $p)
            @php
                $dataHora = $p->dt_entrada ?? $p->created_at;
                $tipoOperacao = $p->tipo === 'compra'
                    ? 'Entrada'
                    : ($p->tipo === 'venda' ? 'Saída' : '');
                $pesoBruto   = (float) ($p->peso_bruto ?? 0);
                $pesoLiquido = (float) ($p->peso_liquido_real ?? 0);
                $tara        = max(0, $pesoBruto - $pesoLiquido);
            @endphp
            <tr>
                <td>{{ $dataHora ? $dataHora->format('d/m/Y H:i') : '' }}</td>
                <td>{{ $tipoOperacao }}</td>
                <td>{{ number_format($pesoBruto, 3, ',', '.') }}</td>
                <td>{{ number_format($tara, 3, ',', '.') }}</td>
                <td>{{ number_format($pesoLiquido, 3, ',', '.') }}</td>
                <td>{{ ucfirst($p->status) }}</td>
                <td>{{ optional($p->venda)->id }}</td>
                <td>{{ optional($p->compra)->id }}</td>
                <td>
                    {{ $p->placa_veiculo ?: (optional($p->veiculo)->placa ?? '') }}
                </td>
                <td>{{ optional($p->veiculo)->descricao }}</td>
                <td>{{ optional($p->motorista)->nome ?? $p->motorista_nome }}</td>
                <td>{{ optional($p->motorista)->cpf }}</td>
                <td>{{ optional($p->filial)->nome_fantasia }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="13" style="text-align:center;">
                    Nenhuma pesagem encontrada para os filtros informados.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

@endsection
