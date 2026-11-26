@extends('default.layout')
@section('content')
<div class="card card-custom"><div class="card-body">
    <h3>Pagamentos eletrônicos</h3>
    <p>{{ $site->local ? 'Filial #'.$site->local : 'Matriz' }} · Ambiente do emitente: {{ $fiscal->value === 'production' ? 'Produção' : 'Homologação' }}</p>
    @if ($temRelatorio)
        @if($ativa)<a class="btn btn-light-primary mb-3" href="{{ route('pagamentos.relatorio') }}">Extrato, recebimentos e relatórios</a>@endif
    @endif
    @if($ativa)
        <p>Ambiente selecionado: <strong>{{ $ativa->ambiente === 'sandbox' ? 'Homologação · cobranças simuladas' : 'Produção · cobranças reais' }}</strong></p>
        @if($ativa->metodosAtivos())
        <form method="post" action="{{ route('pagamentos.emitir') }}" class="mb-4">
            @csrf
            <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $site->local }}">
            <input type="hidden" name="conexao" value="{{ $ativa->webhook_id }}">
            <label>Título a receber <input type="number" class="form-control" min="1" required name="titulo" value="{{ $titulo?->id }}"></label>
            @if($titulo) <span>{{ $titulo->referencia }} · R$ {{ number_format($titulo->valor_integral,2,',','.') }}</span> @endif
            <label>Forma de pagamento <select class="form-control" name="metodo">@foreach($ativa->metodosAtivos() as $chave => $nome)<option value="{{ $chave }}">{{ $nome }}</option>@endforeach</select></label>
            @if(isset($ativa->metodosAtivos()['credit_card']) && $ativa->max_parcelas_cartao > 1)
                <label>Parcelas no cartão <input type="number" min="1" max="{{ $ativa->max_parcelas_cartao }}" name="numero_parcelas" value="1" class="form-control"></label>
            @endif
            <button class="btn btn-success">Emitir cobrança</button>
        </form>
        @endif
    @else
        <p>O Asaas está desativado neste estabelecimento.</p>
    @endif
    <h4>Cobranças do estabelecimento</h4>
    <div class="table-responsive"><table class="table"><thead><tr><th>Origem</th><th>Ambiente</th><th>Valor</th><th>Situação</th><th></th></tr></thead><tbody>
    @forelse($cobrancas as $p)
        <tr><td>{{ $p->conta_receber_id ? 'Título #'.$p->conta_receber_id : 'PDV'.($p->venda_caixa_id ? ' #'.$p->venda_caixa_id : '') }}</td><td>{{ $p->conexao->ambiente }}</td><td>R$ {{ number_format($p->centavos/100,2,',','.') }}</td><td>{{ $p->situacao }}</td><td><a href="{{ route('pagamentos.detalhe',$p->referencia) }}">Abrir cobrança</a></td></tr>
    @empty <tr><td colspan="5">Nenhuma cobrança registrada.</td></tr> @endforelse
    </tbody></table></div>{{ $cobrancas->links() }}
</div></div>
@endsection
