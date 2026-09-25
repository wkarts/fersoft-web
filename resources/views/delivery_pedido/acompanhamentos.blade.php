<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - {{ $produto->nome }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 pb-32">

    @php
        $isPizza = isset($produto->pizza) && count($produto->pizza) > 0;
        $precoInicial = $isPizza ? 0 : $produto->valor;
        $permiteAdicionais = $produto->tem_adicionais == 1;
    @endphp

    <header class="bg-red-600 text-white p-6 rounded-b-3xl">
        <h1 class="text-xl font-bold">Detalhes do Pedido</h1>
    </header>

    <div class="max-w-md mx-auto mt-6 px-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-4">
            <div class="w-full h-48 bg-gray-100 rounded-xl overflow-hidden mb-4">
                @php 
                    $img = isset($produto->galeria) && count($produto->galeria) > 0 ? $produto->galeria[0]->path : $produto->imagem;
                @endphp
                @if($img)
                    <img src="{{ asset('imagens_produtos/' . rawurlencode($img)) }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="fa-solid fa-camera fa-2x"></i></div>
                @endif
            </div>
            <h2 class="text-xl font-bold text-gray-900">{{ $produto->nome }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $produto->descricao }}</p>
            
            <p class="text-lg font-bold text-red-600 mt-2">
                R$ <span id="display-price">{{ number_format($precoInicial, 2, ',', '.') }}</span>
            </p>
        </div>

        @if($isPizza)
        <div class="bg-white p-4 rounded-xl shadow-sm mb-4 border-2 border-red-100">
            <h3 class="font-bold block mb-3 text-gray-800 border-b pb-2">Escolha o Tamanho <span class="text-red-500">*</span></h3>
            <div class="space-y-2">
                @foreach($produto->pizza as $pz)
                    <label class="flex justify-between items-center p-3 border rounded-lg cursor-pointer hover:bg-red-50 transition">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="tamanho_pizza" value="{{ $pz->tamanho_id }}" 
                                   data-price="{{ $pz->valor }}" 
                                   class="w-5 h-5 text-red-600 focus:ring-red-500 size-selector"
                                   onchange="atualizarTamanho(this)">
                            <span class="font-semibold text-gray-700">{{ $pz->tamanho->nome }}</span>
                        </div>
                        <span class="text-sm font-bold text-gray-900">R$ {{ number_format($pz->valor, 2, ',', '.') }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        @if($permiteAdicionais && count($adicionais) > 0)
        <div class="bg-white p-4 rounded-xl shadow-sm mb-4 border-2 border-yellow-100">
            <h3 class="font-bold block mb-3 text-gray-800 border-b pb-2">Deseja adicionar algo?</h3>
            <div class="space-y-2">
                @foreach($adicionais as $complemento)
                    <label class="flex justify-between items-center p-3 border-b border-gray-100 last:border-0 cursor-pointer hover:bg-gray-50 transition rounded-lg">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800">{{ $complemento->nome }}</p>
                            <p class="text-xs text-red-600">+ R$ {{ number_format($complemento->valor, 2, ',', '.') }}</p>
                        </div>
                        <input type="checkbox" class="w-6 h-6 text-red-600 rounded border-gray-300 addon-checkbox" 
                               value="{{ $complemento->id }}" 
                               data-price="{{ $complemento->valor }}"
                               onchange="calcularTotal()">
                    </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="bg-white p-4 rounded-xl shadow-sm mb-4">
            <label class="font-bold block mb-2 text-gray-700">Observações:</label>
            <textarea id="observacao" class="w-full border rounded-lg p-3 text-sm focus:ring-red-500 focus:border-red-500 outline-none" rows="2" placeholder="Ex: Sem cebola, massa fina..."></textarea>
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t z-50">
            <button id="btn-add" onclick="addToCart({{ $produto->id }})" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:bg-green-700 transition">
                Adicionar ao Pedido
            </button>
        </div>
    </div>

    <script>
        let isPizza = {{ $isPizza ? 'true' : 'false' }};
        let basePrice = {{ $precoInicial }};
        let selectedSizeId = null;

        function atualizarTamanho(radio) {
            basePrice = parseFloat(radio.getAttribute('data-price'));
            selectedSizeId = radio.value;
            calcularTotal();
        }

        function calcularTotal() {
            let total = basePrice;
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                total += parseFloat(cb.getAttribute('data-price'));
            });
            
            document.getElementById('display-price').innerText = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function addToCart(produtoId) {
            if (isPizza && !selectedSizeId) {
                alert("Por favor, escolha o tamanho da pizza primeiro.");
                return;
            }

            let btn = document.getElementById('btn-add');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';

            let selectedAddons = [];
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                selectedAddons.push({ id: cb.value });
            });

            let totalParaBanco = basePrice;
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                totalParaBanco += parseFloat(cb.getAttribute('data-price'));
            });

            let endpoint = isPizza ? "{{ url('/cardapio-web/addPizza') }}" : "{{ url('/cardapio-web/addProd') }}";
            
            let payload = {
                quantidade: 1, 
                valor: totalParaBanco.toString(), 
                observacao: document.getElementById('observacao').value,
                adicionais: selectedAddons
            };

            if (isPizza) {
                payload.sabores = [produtoId];
                payload.tamanho = selectedSizeId;
            } else {
                payload.produto_id = produtoId;
            }

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(text); });
                }
                return response.json();
            })
            .then(data => {
                btn.innerHTML = '<i class="fa-solid fa-check text-xl"></i> Item Adicionado!';
                btn.classList.replace('bg-green-600', 'bg-blue-600');
                
                // Agora sim! Volta para o cardápio usando a rota segura sem apagar a sessão!
                setTimeout(() => {
                // Volta para o cardápio usando a rota segura que não apaga a sessão!
                window.location.href = "{{ url('/cardapio-web/mesa') }}/{{ session('mesa_open')['mesa_id'] }}";
            }, 1500);
            })
            .catch(err => {
                console.error("Erro:", err);
                alert('Erro ao adicionar: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = 'Adicionar ao Pedido';
            });
        }
    </script>
</body>
</html>