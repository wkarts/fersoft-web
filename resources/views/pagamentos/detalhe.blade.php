@extends('default.layout')
@section('content')
<div class="card card-custom"><div class="card-body">
    <a href="{{ route('pagamentos.index') }}">Voltar aos pagamentos</a>
    <h3 class="mt-3">Cobrança #{{ $p->id }} · {{ $p->conexao->ambiente === 'sandbox' ? 'Homologação' : 'Produção' }}</h3>
    <p>R$ {{ number_format($p->centavos / 100, 2, ',', '.') }} · {{ ['credit_card'=>'Cartão de crédito','pix'=>'Pix','boleto'=>'Boleto'][$p->metodo] }}</p>
    <p>Situação: {{ $p->situacao }} · Conciliação: {{ $p->conciliacao ?? 'Aguardando consulta' }}</p>
    @if($p->baixada_em)<p>Título #{{ $p->conta_receber_id }} baixado em {{ $p->baixada_em }}.</p>@endif
    @if(isset($instrucoes['imageBase64']))<img width="190" alt="QR Code Pix" src="data:image/png;base64,{{ $instrucoes['imageBase64'] }}">@endif
    @if(isset($instrucoes['copyPaste']))<label class="d-block">Pix copia e cola<textarea readonly class="form-control">{{ $instrucoes['copyPaste'] }}</textarea></label>@endif
    @if(isset($instrucoes['digitableLine']))<p>Linha digitável: <strong>{{ $instrucoes['digitableLine'] }}</strong></p>@endif
    @foreach ([$remote?->checkoutUrl => 'Abrir fatura no Asaas', $remote?->bankSlipUrl => 'Abrir boleto', $p->url_estorno => 'Abrir solicitação de estorno'] as $url => $rotulo)
        @if(str_starts_with($url, 'https://'))<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="btn btn-light-primary">{{ $rotulo }}</a>@endif
    @endforeach
    <form class="mt-3" method="post" action="{{ route('pagamentos.sincronizar', $p->referencia) }}">@csrf<button class="btn btn-primary">Consultar pagamento e conciliar</button></form>
    @if(($p->vigente && !$p->acao_pendente) && (session('user_logged.adm') || session('user_logged.super')))
        <form class="mt-3" method="post" action="{{ route('pagamentos.alterar', $p->referencia) }}" onsubmit="return confirm('Confirmar esta operação na cobrança?')">@csrf
            @if(in_array($p->situacao,['pending','overdue']))<button name="acao" value="cancelar" class="btn btn-outline-danger">Cancelar cobrança</button>@endif
            @if(in_array($p->situacao,['confirmed','settled']))<button name="acao" value="estornar" class="btn btn-outline-danger">Solicitar estorno integral</button>@endif
        </form>
    @endif
    @if($p->acao_pendente)<p>Operação solicitada: {{ $p->acao_pendente }}. Confira a situação no Asaas antes de qualquer nova operação.</p>@endif
</div></div>
@endsection
