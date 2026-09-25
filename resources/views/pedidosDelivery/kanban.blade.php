@extends('default.layout')
@section('content')

<style>
    .kanban-board { display: flex; overflow-x: auto; gap: 15px; padding-bottom: 20px; align-items: flex-start; }
    .kanban-col { background: #f4f6f9; border-radius: 8px; min-width: 300px; max-width: 300px; padding: 10px; border: 1px solid #ddd; }
    .kanban-col h5 { text-align: center; font-weight: bold; padding-bottom: 10px; border-bottom: 2px solid #ccc; margin-bottom: 15px; }
    .kanban-cards { min-height: 400px; }
    .kanban-card { cursor: grab; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid #17a2b8; margin-bottom: 10px; }
    .kanban-actions { display: flex; justify-content: space-between; border-top: 1px solid #eee; padding-top: 8px; margin-top: 8px; }
</style>

<div class="content">
    <div class="container-fluid pt-4">
      <form action="/pedidosDelivery/kanban" method="GET" class="row mb-4">
              <div class="col-md-3">
                  <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial', date('Y-m-d')) }}">
              </div>
              <div class="col-md-3">
                  <input type="date" name="data_final" class="form-control" value="{{ request('data_final', date('Y-m-d')) }}">
              </div>
              <div class="col-md-2">
                  <button type="submit" class="btn btn-primary">Filtrar</button>
              </div>
          </form>
        <h2>📦 Painel Kanban - Delivery</h2>
        <div class="kanban-board">
          
    @foreach([
        'novo' => ['🟡 Novos', '#ffc107', $pedidosNovo], 
        'aprovado' => ['🟠 Em Preparo', '#fd7e14', $pedidosAprovado], 
        'finalizado' => ['🛵 Saiu p/ Entrega', '#17a2b8', $pedidosFinalizado],
        'entregue' => ['✅ Entregue', '#28a745', $pedidosEntregue], 
        'cancelado' => ['🔴 Cancelados', '#dc3545', $pedidosCancelado]
    ] as $status => $col)
    
    <div class="kanban-col">
        <h5 style="color: {{ $col[1] }}">{{ $col[0] }}</h5>
        <div class="kanban-cards" id="col-{{$status}}" data-status="{{$status}}">
            @foreach($col[2] as $p)
                <div class="card kanban-card" data-id="{{ $p->id }}" style="border-left-color: {{ $col[1] }}">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>#{{ $p->id }} - {{ $p->cliente->nome ?? 'Cliente' }}</strong>
                            <span class="badge badge-warning">R$ {{ number_format($p->valor_total, 2, ',', '.') }}</span>
                        </div>
                        <ul class="list-unstyled mb-1" style="font-size: 0.85em;">
                            @foreach($p->itens as $item)
                                <li>• {{ $item->quantidade }}x {{ $item->produto->produto->nome ?? $item->produto->nome }}</li>
                            @endforeach
                        </ul>
                        <div class="small text-muted mb-2">📍 {{ $p->endereco->rua ?? 'Sem end.' }}</div>
                        <div class="kanban-actions">
                            <a href="/pedidosDelivery/verPedido/{{ $p->id }}" class="btn btn-sm btn-info">👁️</a>
                            <a href="/pedidosDelivery/print/{{ $p->id }}" target="_blank" class="btn btn-sm btn-secondary">🖨️</a>
                            <a href="/pedidosDelivery/irParaFrenteCaixa/{{ $p->id }}" class="btn btn-sm btn-success">💲</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.querySelectorAll('.kanban-cards').forEach(el => {
        new Sortable(el, {
            group: 'shared',
            animation: 150,
            
            // Bloqueio: Impede o arraste se o card já estiver na coluna "Entregue"
            onStart: function (evt) {
                if (evt.from.getAttribute('data-status') === 'entregue') {
                    alert("Atenção: Este pedido já foi entregue e não pode ser alterado!");
                    location.reload(); 
                }
            },

            onEnd: function (evt) {
                const newStatus = evt.to.getAttribute('data-status');
                const oldStatus = evt.from.getAttribute('data-status');
                const pedidoId = evt.item.getAttribute('data-id');

                // Se soltar na mesma coluna, não faz nada
                if (newStatus === oldStatus) return;

                // Fluxo: Abrir PDV em nova aba
                if (newStatus === 'finalizar_caixa') {
                    let win = window.open('/pedidosDelivery/irParaFrenteCaixa/' + pedidoId, '_blank');
                    if (!win) alert("Pop-up bloqueado! Libere as permissões do navegador.");
                    location.reload();
                    return;
                }

                let motivo = (newStatus === 'cancelado') ? prompt("Motivo?") : '';
                
                // Envia para o servidor
                fetch('/pedidosDelivery/actualizarStatusKanban', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ id: pedidoId, estado: newStatus, motivo: motivo })
                })
                .then(res => res.json())
                .then(data => {
                    if(!data.sucesso) {
                        alert('Erro ao salvar status: ' + data.mensagem);
                        location.reload();
                    }
                });
            }
        });
    });

    const audioAlerta = new Audio('/assets/alerta.mp3'); 

// Loop de verificação
setInterval(() => {
    fetch('/pedidosDelivery/verificar-novos-pedidos')
    .then(res => res.json())
    .then(data => {
        let contagemAtual = document.querySelectorAll('#col-novo .kanban-card').length;
        if(data.novos > contagemAtual) {
            // Toca o som
            audioAlerta.play().catch(e => console.log("Som bloqueado pelo navegador, clique na página para habilitar."));
            
            // Mantém o alerta visual
            alert('Novo pedido chegou!');
            location.reload(); 
        }
    });
}, 10000);
</script>
@endsection