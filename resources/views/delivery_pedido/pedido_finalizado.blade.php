<div class="border-t border-gray-100 pt-4 mt-4 text-left">
    <p class="text-xs text-gray-400 uppercase font-bold">Resumo do Pedido</p>
    <p class="text-sm font-semibold text-gray-700 mt-1">
        Comanda: {{ $pedido->comanda }}
    </p>
    <p class="text-sm text-gray-700">
        Data/Hora: {{ date('d/m/Y H:i') }}
    </p>
</div>