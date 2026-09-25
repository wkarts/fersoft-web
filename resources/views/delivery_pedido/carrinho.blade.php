<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800 antialiased pb-40">

    <header class="bg-red-600 text-white px-4 pt-6 pb-6 shadow-md sticky top-0 z-50 rounded-b-3xl">
        <h1 class="text-xl font-bold">Meu Pedido</h1>
        <p class="text-xs text-red-100">Mesa {{ session('mesa_open')['mesa_id'] ?? '-' }}</p>
    </header>

    <main class="max-w-md mx-auto mt-6 px-4 space-y-4">
        @php $totalGeral = 0; @endphp
        
        @if(isset($pedido) && $pedido->itens->count() > 0)
            @foreach($pedido->itens as $item)
                @php 
                    $subtotal = $item->valor * $item->quantidade; 
                    $totalGeral += $subtotal;
                @endphp
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-sm">{{ $item->quantidade }}x {{ $item->produto->nome ?? 'Item' }}</h3>
                        <p class="text-xs text-gray-400">{{ $item->observacao }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-red-600">R$ {{ number_format($subtotal, 2, ',', '.') }}</p>
                        <a href="{{ url('/cardapio-web/removerItem/' . $item->id) }}" class="text-[10px] text-gray-400 underline">Remover</a>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-center text-gray-400 mt-10">Carrinho vazio.</p>
        @endif
    </main>

  @if(isset($pedido) && $pedido->status == 0)
    <div class="bg-blue-100 text-blue-800 p-4 rounded-xl text-center font-bold mb-4">
        <i class="fa-solid fa-clock"></i> Pedido enviado, aguardando confirmação.
    </div>
@elseif(isset($pedido) && $pedido->status == 1)
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded-xl text-center font-bold mb-4">
        <i class="fa-solid fa-fire"></i> Cozinha iniciou o preparo!
    </div>
@endif
  
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex flex-col gap-3 max-w-md mx-auto">
        <div class="flex justify-between font-bold text-lg">
            <span>Total:</span>
            <span>R$ {{ number_format($totalGeral, 2, ',', '.') }}</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ url('/open/' . session('mesa_open')['mesa_id']) }}" class="w-1/3 bg-gray-200 text-gray-700 py-3 rounded-lg text-center font-bold text-sm">
                + Comprar
            </a>
            <button type="button" 
                  onclick="if(confirm('Confirmar pedido e pedir a conta?')) { window.location.href='{{ url('/cardapio-web/finalizar') }}'; }" 
                  class="w-2/3 bg-green-600 text-white py-3 rounded-lg font-bold">
              Pedir a Conta
          </button>
        </div>
    </div>
  <button id="btn-enviar" onclick="enviarParaCozinha()" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg">
    Enviar Pedido para a Cozinha
</button>

<script>
function enviarParaCozinha() {
    let btn = document.getElementById('btn-enviar');
    btn.disabled = true;
    btn.innerHTML = '<span class="fa fa-spinner fa-spin mr-2"></span> Enviando...';
    
    // O fetch aponta para a rota do seu PedidoQrCodeController
    fetch("{{ url('/cardapio-web/enviarParaCozinha') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Erro no servidor');
        return response.json();
    })
    .then(data => {
        btn.innerHTML = '<span class="fa fa-check mr-2"></span> Pedido na Cozinha!';
        btn.classList.remove('btn-info');
        btn.classList.add('btn-success');
    })
    .catch(err => {
        console.error(err);
        alert('Erro ao enviar pedido. Tente novamente.');
        btn.disabled = false;
        btn.innerHTML = '<span class="fa fa-fire mr-2"></span> ENVIAR PARA COZINHA';
    });
}
</script>
</body>
</html>