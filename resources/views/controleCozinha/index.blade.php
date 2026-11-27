@extends('default.layout')
@section('css')
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-controleCozinha ui-commerce-kitchen">
    <header class="ui-kitchen-header">
        <span class="ui-kitchen-header-icon"><i class="fa-solid fa-fire-burner" aria-hidden="true"></i></span>
        <div>
            <h1>Cozinha em Tempo Real</h1>
            <p>Sincronizando pedidos automaticamente...</p>
        </div>
    </header>
    <div id="painel-pedidos" class="ui-kitchen-grid" aria-live="polite">
        <div class="ui-kitchen-empty"><p>Carregando pedidos da cozinha...</p></div>
    </div>
    <div id="area-impressao" hidden></div>
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
                        <div class="ui-kitchen-empty">
                            <i class="fa-solid fa-mug-hot ui-kitchen-empty-icon"></i>
                            <p class="ui-kitchen-empty-title">Nenhum pedido na fila.</p>
                            <p class="ui-kitchen-empty-subtitle">A cozinha está tranquila.</p>
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
                    let itensHtml = '<div class="ui-kitchen-items">';
                    
                    pedido.itens.forEach(item => {
                        let tamanhoHtml = item.tamanho ? `<span class="ui-kitchen-size">${item.tamanho}</span>` : '';
                        
                        let adcHtml = '';
                        if (item.adicionais.length > 0) {
                            adcHtml = '<ul class="ui-kitchen-addons">';
                            item.adicionais.forEach(add => {
                                adcHtml += `<li class="ui-kitchen-addon"><i class="fa-solid fa-plus"></i> ${add.nome}</li>`;
                            });
                            adcHtml += '</ul>';
                        }

                        let obsHtml = item.observacao ? `<div class="ui-kitchen-observation"><i class="fa-solid fa-triangle-exclamation"></i> OBS: ${item.observacao}</div>` : '';

                        itensHtml += `
                        <div class="ui-kitchen-item">
                            <div class="ui-kitchen-item-line">
                                <div class="ui-kitchen-quantity">${item.quantidade}x</div>
                                <h4 class="ui-kitchen-item-title">${item.nome}${tamanhoHtml}</h4>
                            </div>
                            ${adcHtml} ${obsHtml}
                        </div>`;
                    });
                    itensHtml += '</div>';

                    // Monta o Card do Pedido
                    painel.innerHTML += `
                    <div class="ui-kitchen-card">
                        <div class="ui-kitchen-card-head">
                            <div class="ui-kitchen-card-accent"></div>
                            <div>
                                <h3 class="ui-kitchen-card-title">🚚 DELIVERY</h3>
                                <p class="ui-kitchen-card-subtitle">Pedido #${pedido.id} - ${pedido.nome_cliente}</p>
                            </div>
                            <div class="ui-kitchen-card-meta">
                                <span class="ui-kitchen-time"><i class="fa-regular fa-clock"></i> ${pedido.hora}</span>
                                <button onclick="imprimir(${pedido.id})" class="ui-kitchen-print-button" title="Imprimir Comanda">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </div>
                        </div>
                        <div class="ui-kitchen-card-body">${itensHtml}</div>
                        <div class="ui-kitchen-card-foot">
                            <button onclick="marcarPronto('${pedido.tipo}', ${pedido.id})" class="ui-kitchen-done">
                                <i class="fa-solid fa-check-double"></i> Marcar Pedido como Pronto
                            </button>
                        </div>
                    </div>`;
                });

                if (temPedidoNovo) {
                    somAlerta.play().catch(() => console.log("Áudio bloqueado"));
                    document.body.classList.add('ui-kitchen-flash');
                    setTimeout(() => document.body.classList.remove('ui-kitchen-flash'), 1500); 
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
</div>
@endsection
