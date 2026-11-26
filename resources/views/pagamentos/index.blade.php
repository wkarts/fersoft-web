@extends('default.layout')
@section('content')
<div class="card card-custom"><div class="card-body">
    <h3>Pagamentos eletrônicos</h3>
    <p>{{ $site->local ? 'Filial #'.$site->local : 'Matriz' }} · Ambiente do emitente: {{ $fiscal->value === 'production' ? 'Produção' : 'Homologação' }}</p>
    @if ($site->usuario->adm || session('user_logged.super'))
        <details class="mb-4"><summary>Configurar Asaas neste estabelecimento</summary>
        @foreach (['sandbox' => 'Homologação', 'production' => 'Produção'] as $env => $nome)
            @continue($env === 'production' && $fiscal->value !== 'production')
            @php($conta = $conexoes->firstWhere('ambiente', $env))
            <form method="post" action="{{ route('pagamentos.configurar') }}" class="border rounded p-3 mt-3">
                @csrf
                <input type="hidden" name="ambiente" value="{{ $env }}">
                <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $site->local }}">
                <h5>{{ $nome }}</h5>
                <label>Chave de API Asaas <input class="form-control" type="password" autocomplete="new-password" name="credencial" @required(!$conta)></label>
                <label>Token do webhook <input class="form-control" type="password" autocomplete="new-password" minlength="32" maxlength="255" name="segredo" @required(!$conta)></label>
                @if($conta)
                    <p>Deixe as credenciais em branco para conservar as atuais. Cadastre no Asaas os eventos de cobrança, o mesmo token e esta URL:</p>
                    <code>{{ route('pagamentos.webhook', $conta->webhook_id) }}</code>
                @endif
                <div><label><input type="checkbox" name="ativar" value="1" @checked($conta?->selecionada)> Usar este ambiente nas novas cobranças</label></div>
                <button class="btn btn-primary">Salvar {{ $nome }}</button>
            </form>
        @endforeach
        </details>
        @if($ativa)<a class="btn btn-light-primary mb-3" href="{{ route('pagamentos.relatorio') }}">Extrato, recebimentos e relatórios</a>@endif
    @endif
    @if($ativa)
        <p>Ambiente selecionado: <strong>{{ $ativa->ambiente === 'sandbox' ? 'Homologação · cobranças simuladas' : 'Produção · cobranças reais' }}</strong></p>
        @if($ativa->ambiente === 'sandbox' || $fiscal->value === 'production')
        <form method="post" action="{{ route('pagamentos.emitir') }}" class="mb-4">
            @csrf
            <input type="hidden" name="estabelecimento" value="{{ $site->empresa }}-{{ $site->local }}">
            <input type="hidden" name="conexao" value="{{ $ativa->webhook_id }}">
            <label>Título a receber <input type="number" class="form-control" min="1" required name="titulo" value="{{ $titulo?->id }}"></label>
            @if($titulo) <span>{{ $titulo->referencia }} · R$ {{ number_format($titulo->valor_integral,2,',','.') }}</span> @endif
            <label>Forma de pagamento <select class="form-control" name="metodo"><option value="pix">Pix</option><option value="boleto">Boleto</option><option value="credit_card">Cartão de crédito</option></select></label>
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
