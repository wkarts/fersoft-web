@extends('delivery.default')
@section('content')

<div class="container" style="margin-top: 50px; margin-bottom: 50px;">
    <div class="title-section text-center mb-md-5 mb-4">
        <h3 class="w3ls-title mb-3">Meus Pedidos</h3>
    </div>

    @if(count($pedidos) > 0)
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Nº do Pedido</th>
                        <th>Data e Hora</th>
                        <th>Valor Total</th>
                        <th>Status Atual</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $p)
                    <tr>
                        <td><strong>#{{ $p->id }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($p->data_registro)->format('d/m/Y H:i') }}</td>
                        <td>R$ {{ number_format($p->valor_total, 2, ',', '.') }}</td>
                        <td>
                            @if($p->estado == 'novo')
                                <span class="badge badge-warning" style="padding: 10px; font-size: 14px; color: #000;">Aguardando Confirmação</span>
                            @elseif($p->estado == 'aprovado')
                                <span class="badge badge-primary" style="padding: 10px; font-size: 14px; background-color: #17a2b8;">Sendo Preparado</span>
                            @elseif($p->estado == 'finalizado')
                                @if($p->entregue == 1)
                                    <span class="badge badge-success" style="padding: 10px; font-size: 14px;">Entregue</span>
                                @else
                                    <span class="badge badge-info" style="padding: 10px; font-size: 14px;">Saiu para Entrega</span>
                                @endif
                            @elseif($p->estado == 'cancelado')
                                <span class="badge badge-danger" style="padding: 10px; font-size: 14px;">Cancelado</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="/carrinho/finalizado/{{ $p->id }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-motorcycle"></i>
                                {{ $p->estado == 'cancelado' || ($p->estado == 'finalizado' && $p->entregue == 1) ? 'Ver pedido' : 'Acompanhar' }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-info text-center">
            <h4>Você ainda não possui pedidos registrados.</h4>
            <br>
            <a href="/cardapio" class="btn btn-primary">Ir para o Cardápio</a>
        </div>
    @endif
</div>

@endsection