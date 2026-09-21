@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h3>Dashboard & DRE de Obras</h3><div class="text-muted">Resultado por contrato de engenharia.</div></div>
        <div>
            <a href="{{ route('contratos.dashboard-dre.excel',['status'=>$statusFiltro]) }}" class="btn btn-success">Exportar Excel</a>
            <a href="/contratos" class="btn btn-light">Contratos</a>
        </div>
    </div>

    <form method="GET" action="{{ route('contratos.dashboard-dre') }}" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    @foreach(['Ativo'=>'Ativos','Finalizado'=>'Finalizados','Suspenso'=>'Suspensos','Cancelado'=>'Cancelados','todos'=>'Todos'] as $value=>$label)
                        <option value="{{ $value }}" {{ $statusFiltro === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="row">
        @foreach([
            ['Contratos Ativos',$totalContratosAtivos,'primary'],
            ['Próximos do Fim',$totalProximosFim,'warning'],
            ['Margem Crítica',$totalMargemCritica,'danger'],
            ['Receitas',$totalReceitasGeral,'success'],
            ['Despesas',$totalDespesasGeral,'danger'],
            ['Resultado',$lucroGeral,$lucroGeral >= 0 ? 'success':'danger'],
        ] as $card)
            <div class="col-lg-2 col-md-4 mb-3"><div class="card card-custom h-100"><div class="card-body">
                <small class="text-muted">{{ $card[0] }}</small>
                <div class="h4 text-{{ $card[2] }}">
                    @if(in_array($card[0],['Receitas','Despesas','Resultado'])) R$ {{ number_format((float)$card[1],2,',','.') }} @else {{ $card[1] }} @endif
                </div>
            </div></div></div>
        @endforeach
    </div>

    <div class="card card-custom gutter-b">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead><tr><th>Contrato</th><th>Cliente</th><th>Status</th><th class="text-right">Receitas</th><th class="text-right">Despesas</th><th class="text-right">Resultado</th><th>Margem</th><th>Prazo</th></tr></thead>
                <tbody>
                @forelse($relatorio as $item)
                    <tr class="{{ $item->margem_critica ? 'table-danger' : '' }}">
                        <td><a href="{{ route('contratos.detalhes',$item->id) }}">{{ $item->numero_contrato ?: $item->id }}</a></td>
                        <td>{{ optional($item->cliente)->razao_social ?: '—' }}</td>
                        <td>{{ $item->status }}</td>
                        <td class="text-right">R$ {{ number_format((float)$item->total_receitas,2,',','.') }}</td>
                        <td class="text-right">R$ {{ number_format((float)$item->total_despesas,2,',','.') }}</td>
                        <td class="text-right">R$ {{ number_format((float)$item->lucro,2,',','.') }}</td>
                        <td>{{ number_format((float)$item->margem,1,',','.') }}%</td>
                        <td>{{ $item->dias_restantes === null ? '—' : $item->dias_restantes . ' dias' }}</td>
                    </tr>
                @empty<tr><td colspan="8" class="text-center text-muted">Nenhum contrato no filtro.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-custom"><div class="card-body"><canvas id="dreChart" height="90"></canvas></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (!window.Chart) return;
    const el = document.getElementById('dreChart');
    if (!el) return;
    new Chart(el.getContext('2d'), {
        type:'bar',
        data:{
            labels:{!! $chartLabels !!},
            datasets:[
                {label:'Receitas',data:{!! $chartReceitas !!}},
                {label:'Despesas',data:{!! $chartDespesas !!}}
            ]
        },
        options:{responsive:true,maintainAspectRatio:false}
    });
});
</script>
@endsection
