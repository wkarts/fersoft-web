@extends('delivery_pedido.default')
@section('content')

<style>
    /* Estilos para o Kanban ficar bonito e fácil de arrastar */
    .kanban-board { display: flex; overflow-x: auto; gap: 15px; padding-bottom: 20px; align-items: flex-start; }
    .kanban-col { background: #f4f6f9; border-radius: 8px; min-width: 320px; max-width: 320px; padding: 10px; border: 1px solid #ddd; }
    .kanban-col h5 { text-align: center; font-weight: bold; padding-bottom: 10px; border-bottom: 2px solid #ccc; margin-bottom: 15px; }
    .kanban-cards { min-height: 400px; } /* Altura para conseguir soltar itens mesmo se a coluna estiver vazia */
    .kanban-card { cursor: grab; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid #17a2b8; }
    .kanban-card:active { cursor: grabbing; }
    .kanban-actions { display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 8px; margin-top: 8px; }
</style>

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="container-fluid pt-4">
        
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2>📦 Painel Kanban - Delivery</h2>
                <a href="/pedidosDelivery" class="btn btn-primary">
                    <i class="fa fa-list"></i> Ver em Tabela
                </a>
            </div>
        </div>

        <div class="kanban-board">
            
            <div class="kanban-col">
                <h5 style="color: #d39e00;">🟡 Novos Pedidos</h5>
                <div class="kanban-cards" id="col-novo" data-status="novo">
                    @foreach($pedidosNovo as $pedido)
                        <div class="card kanban-card mb-3" data-id="{{ $pedido->id }}" style="border-left-color: #ffc107;">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between">
                                    <strong>#{{ $pedido->id }}</strong>
                                    <span class="badge badge-secondary">{{ date('H:i', strtotime($pedido->created_at)) }}</span>
                                </div>
                                <p class="mb-1 mt-1 text-truncate" title="{{ $pedido->cliente->nome ?? 'Cliente' }}">
                                    👤 {{ $pedido->cliente->nome ?? 'Cliente não info.' }}
                                </p>
                                <p class="mb-2 text-success font-weight-bold">💰 R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</p>
                                
                                <div class="kanban-actions">
                                    <a href="/pedidosDelivery/verPedido/{{ $pedido->id }}" class="btn btn-sm btn-info" title="Ver Detalhes">👁️ Ver</a>
                                    <a href="/pedidosDelivery/print/{{ $pedido->id }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">🖨️</a>
                                    <a href="/pedidosDelivery/irParaFrenteCaixa/{{ $pedido->id }}" class="btn btn-sm btn-success" title="Finalizar no PDV">💲 PDV</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kanban-col">
                <h5 style="color: #e67e22;">🟠 Em Preparo</h5>
                <div class="kanban-cards" id="col-aprovado" data-status="aprovado">
                    @foreach($pedidosAprovado as $pedido)
                        <div class="card kanban-card mb-3" data-id="{{ $pedido->id }}" style="border-left-color: #fd7e14;">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between">
                                    <strong>#{{ $pedido->id }}</strong>
                                    <span class="badge badge-secondary">{{ date('H:i', strtotime($pedido->created_at)) }}</span>
                                </div>
                                <p class="mb-1 mt-1 text-truncate">👤 {{ $pedido->cliente->nome ?? 'Cliente' }}</p>
                                <p class="mb-2 text-success font-weight-bold">💰 R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</p>
                                
                                <div class="kanban-actions">
                                    <a href="/pedidosDelivery/verPedido/{{ $pedido->id }}" class="btn btn-sm btn-info" title="Ver Detalhes">👁️ Ver</a>
                                    <a href="/pedidosDelivery/print/{{ $pedido->id }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">🖨️</a>
                                    <a href="/pedidosDelivery/irParaFrenteCaixa/{{ $pedido->id }}" class="btn btn-sm btn-success" title="Finalizar no PDV">💲 PDV</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="kanban-col">
                <h5 style="color: #17a2b8;">🛵 Saiu p/ Entrega</h5>
                <div class="kanban-cards" id="col-finalizado" data-status="finalizado">
                    @foreach($pedidosFinalizado as $pedido)
                        <div class="card kanban-card mb-3" data-id="{{ $pedido->id }}" style="border-left-color: #17a2b8;">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between">
                                    <strong>#{{ $pedido->id }}</strong>
                                    <span class="badge badge-secondary">{{ date('H:i', strtotime($pedido->created_at)) }}</span>
                                </div>
                                <p class="mb-1 mt-1 text-truncate">👤 {{ $pedido->cliente->nome ?? 'Cliente' }}</p>
                                <p class="mb-2 text-success font-weight-bold">💰 R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</p>
                                
                                <div class="kanban-actions">
                                    <a href="/pedidosDelivery/verPedido/{{ $pedido->id }}" class="btn btn-sm btn-info" title="Ver Detalhes">👁️ Ver</a>
                                    <a href="/pedidosDelivery/print/{{ $pedido->id }}" target="_blank" class="btn btn-sm btn-secondary" title="Imprimir">🖨️</a>
                                    <a href="/pedidosDelivery/irParaFrenteCaixa/{{ $pedido->id }}" class="btn btn-sm btn-success" title="Finalizar no PDV">💲 PDV</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div> </div>
</div>

@endsection