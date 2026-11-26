@php($conexaoPdv = \App\Services\Pagamentos\Estabelecimento::disponivel())
@if($conexaoPdv && $conexaoPdv->metodosAtivos() && !isset($is_troca) && !isset($preVenda))
<div class="my-2" id="asaas-pdv" data-url="{{ route('pagamentos.pdv') }}" data-metodos="{{ implode(',', array_keys($conexaoPdv->metodosAtivos())) }}" data-conexao="{{ $conexaoPdv->webhook_id }}"
     data-liberar="{{ route('pagamentos.pdv.liberar') }}" data-local="{{ $conexaoPdv->local }}" data-usuario="{{ session('user_logged.id') }}" data-ambiente="{{ $conexaoPdv->ambiente }}">
    @if(isset($conexaoPdv->metodosAtivos()['credit_card']) && $conexaoPdv->max_parcelas_cartao > 1)
        <label>Parcelas no cartão <input id="asaas-pdv-numero-parcelas" class="form-control" type="number" min="1" max="{{ $conexaoPdv->max_parcelas_cartao }}" value="1"></label>
    @endif
    <label id="asaas-pdv-parcela-label" hidden>Parcela a cobrar pelo Asaas <select class="form-control" id="asaas-pdv-parcela"></select></label>
    <button type="button" class="btn btn-outline-primary" id="asaas-pdv-emitir">Cobrar pelo Asaas · {{ $conexaoPdv->ambiente === 'sandbox' ? 'Homologação' : 'Produção' }}</button>
    <a id="asaas-pdv-link" class="btn btn-light-primary" target="_blank" rel="noopener" hidden>Abrir cobrança</a>
    <button type="button" class="btn btn-light" id="asaas-pdv-liberar" hidden>Liberar operação do caixa</button>
    <span id="asaas-pdv-status" role="status"></span>
</div>
<script src="{{ asset('js/pagamento-eletronico-pdv.js') }}" defer></script>
@endif
