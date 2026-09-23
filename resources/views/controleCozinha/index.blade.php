@extends('default.layout')
@section('content')
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Cozinha</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body * { visibility: hidden; }
            #area-impressao, #area-impressao * { visibility: visible; }
            #area-impressao { position: absolute; left: 0; top: 0; width: 100%; }
        }
        /* Transição suave para a piscada amarela */
        body { transition: background-color 0.5s ease; }
    </style>
</head>
<body class="bg-gray-100 p-6 min-h-screen">

    <header class="mb-8 flex justify-between items-center bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
        <div class="flex items-center gap-4">
            <div class="bg-red-100 text-red-600 p-3 rounded-xl">
                <i class="fa-solid fa-fire-burner text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Cozinha em Tempo Real</h1>
                <p class="text-sm text-gray-500 font-medium">Sincronizando pedidos automaticamente...</p>
            </div>
        </div>
    </header>
    
    <div id="painel-pedidos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div class="col-span-full flex justify-center py-10">
            <p class="text-gray-400 font-medium text-lg animate-pulse">Carregando pedidos da cozinha...</p>
        </div>
    </div>

    <div id="area-impressao" class="hidden bg-white p-4 text-black text-sm font-mono w-80"></div>

<script>
        let idsConhecidos = []; 
        let primeiraCarga = true; 
        const somAlerta = new Audio('/audio/delivery_1.mp3');

        function carregarPedidos() {
            fetch('/controleCozinha/apiPedidos') 
            .then(response => response.json())
            .then(data => {
                let painel = document.getElementById('painel-pedidos');
                painel.innerHTML = ''; 

                let temPedidoNovo = false;

                if(data.length === 0) {
                    painel.innerHTML = `
                        <div class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400">
                            <i class="fa-solid fa-mug-hot text-6xl mb-4 text-gray-300"></i>
                            <p class="text-xl font-bold">Nenhum pedido na fila.</p>
                            <p class="text-sm">A cozinha está tranquila.</p>
                        </div>`;
                    primeiraCarga = false;
                    return;
                }

                data.forEach(pedido => {
                    let idUnico = pedido.tipo + pedido.id;
                    if (!idsConhecidos.includes(idUnico)) {
                        if (!primeiraCarga) temPedidoNovo = true;
                        idsConhecidos.push(idUnico);
                    }

                    // Monta o HTML dos itens
                    let itensHtml = '<div class="space-y-4 p-4">';
                    
                    pedido.itens.forEach(item => {
                        let tamanhoHtml = item.tamanho ? `<span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full ml-1">${item.tamanho}</span>` : '';
                        
                        let adcHtml = '';
                        if (item.adicionais.length > 0) {
                            adcHtml = '<ul class="mt-2 ml-9 space-y-1">';
                            item.adicionais.forEach(add => {
                                adcHtml += `<li class="text-xs text-gray-500 font-medium flex items-center gap-1"><i class="fa-solid fa-plus text-[10px] text-green-500"></i> ${add.nome}</li>`;
                            });
                            adcHtml += '</ul>';
                        }

                        let obsHtml = item.observacao ? `<div class="mt-2 ml-9 bg-red-50 border border-red-200 p-2 rounded-lg text-red-700 text-xs font-bold shadow-sm"><i class="fa-solid fa-triangle-exclamation mt-0.5"></i> OBS: ${item.observacao}</div>` : '';

                        itensHtml += `
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                            <div class="flex items-start gap-3">
                                <div class="bg-red-600 text-white font-black text-sm px-2.5 py-1 rounded-lg shadow-sm">${item.quantidade}x</div>
                                <h4 class="text-base font-bold text-gray-800 uppercase leading-tight">${item.nome}${tamanhoHtml}</h4>
                            </div>
                            ${adcHtml} ${obsHtml}
                        </div>`;
                    });
                    itensHtml += '</div>';

                    // Monta o Card do Pedido
                    painel.innerHTML += `
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 flex flex-col overflow-hidden">
                        <div class="bg-blue-900 p-4 flex justify-between items-center text-white relative">
                            <div class="absolute top-0 left-0 w-full h-1 bg-red-500"></div>
                            <div>
                                <h3 class="text-xl font-black uppercase tracking-wider">🚚 DELIVERY</h3>
                                <p class="text-xs text-slate-300 mt-0.5">Pedido #${pedido.id} - ${pedido.nome_cliente}</p>
                            </div>
                            <div class="text-right flex flex-col items-end gap-2">
                                <span class="text-xs bg-slate-700 px-2 py-1 rounded-lg text-yellow-400 font-bold block"><i class="fa-regular fa-clock"></i> ${pedido.hora}</span>
                                <button onclick="imprimir(${pedido.id})" class="text-slate-300 hover:text-white transition" title="Imprimir Comanda">
                                    <i class="fa-solid fa-print text-xl"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 bg-white">${itensHtml}</div>
                        <div class="p-4 bg-gray-50 border-t border-gray-100">
                            <button onclick="marcarPronto('${pedido.tipo}', ${pedido.id})" class="w-full flex justify-center items-center gap-2 bg-green-500 text-white py-3 rounded-xl font-bold text-sm shadow-sm hover:bg-green-600 transition">
                                <i class="fa-solid fa-check-double"></i> Marcar Pedido como Pronto
                            </button>
                        </div>
                    </div>`;
                });

                if (temPedidoNovo) {
                    somAlerta.play().catch(() => console.log("Áudio bloqueado"));
                    document.body.classList.replace('bg-gray-100', 'bg-yellow-200');
                    setTimeout(() => document.body.classList.replace('bg-yellow-200', 'bg-gray-100'), 1500); 
                }

                primeiraCarga = false; 
            });
        }

        function marcarPronto(tipo, id) {
            fetch(`/controleCozinha/concluir/${tipo}/${id}`)
            .then(() => carregarPedidos()); // Atualiza a tela na hora, sumindo com o pedido
        }

        function imprimir(id) {
            window.open('/pedidosDelivery/print/' + id, '_blank');
        }

        setInterval(carregarPedidos, 5000); // Atualiza a cada 5 segundos
        carregarPedidos();
    </script>
</body>
</html>
@endsection