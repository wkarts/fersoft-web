@extends('default.layout')
@section('css')
<style type="text/css">
	.collapsed{
		color: #333 !important;
	}
	.pedido-urgente {
		background-color: #fff3cd !important;
		border: 2px solid #ffc107 !important;
		color: #856404 !important;
		box-shadow: 0 4px 8px rgba(0,0,0,0.1);
		animation: piscar-alerta 1.5s infinite;
		transition: transform 0.2s ease;
	}
	.pedido-urgente:hover {
		transform: scale(1.02);
        text-decoration: none;
	}
	@keyframes piscar-alerta {
		0% { background-color: #fff3cd; border-color: #ffc107; box-shadow: 0 0 5px rgba(255, 193, 7, 0.5); }
		50% { background-color: #ffeeba; border-color: #dc3545; box-shadow: 0 0 15px rgba(220, 53, 69, 0.8); }
		100% { background-color: #fff3cd; border-color: #ffc107; box-shadow: 0 0 5px rgba(255, 193, 7, 0.5); }
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<form method="get" action="/pedidosDelivery/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-3 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-3 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>

			<h2 class="ml-4">{{$tipo}}</h2>

			<div class="col-lg-12 col-xl-12">
				<div class="row">

					<div class="col-lg-6 col-xl-4 col-sm-6 col-md-6 col-12">
						<span style="width: 100%; margin-top: 5px;" class="label label-xl label-inline label-light-primary">Valor de Pedidos Novos: R$ {{number_format($somaNovos, 2, ',', '.')}}</span>
					</div>
					<div class="col-lg-6 col-xl-4 col-sm-6 col-md-6 col-12">
						<span style="width: 100%; margin-top: 5px;" class="label label-xl label-inline label-light-success">Valor de Pedidos Aprovados: R$ {{number_format($somaAprovados, 2, ',', '.')}}</span>
					</div>
					<div class="col-lg-6 col-xl-4 col-sm-6 col-md-6 col-12">
						<span style="width: 100%; margin-top: 5px;" class="label label-xl label-inline label-light-danger">Valor de Pedidos Cancelados: R$ {{number_format($somaCancelados, 2, ',', '.')}}</span>
					</div>
					
					<div class="col-lg-6 col-xl-4 col-sm-6 col-md-6 col-12">
						<span style="width: 100%; margin-top: 5px;" class="label label-xl label-inline label-light-info">Valor de Pedidos Finalizados: R$ {{number_format($somaFinalizados, 2, ',', '.')}}</span>
					</div>

				</div>

			</div>
			<br>


			<div class="col-lg-12 col-xl-12">
				<div class="accordion accordion-toggle-arrow" id="accordionExample1">
					
					<div class="card">
						<div class="card-header">
							<div class="card-title {{ count($pedidosNovo) == 0 ? 'collapsed' : '' }}" data-toggle="collapse" data-target="#collapseOne1">
								Pedidos Novos 
								@if(count($pedidosNovo) > 0)
									<span class="badge badge-danger ml-2" style="font-size: 14px;">{{ count($pedidosNovo) }}</span>
								@endif
								<i class="la la-angle-double-down"></i>
							</div>
						</div>
						<div id="collapseOne1" class="collapse {{ count($pedidosNovo) > 0 ? 'show' : '' }}" data-parent="#accordionExample1">
							<div class="card-body">
								@if(count($pedidosNovo) > 0)
									@foreach($pedidosNovo as $p)
									<a href="/pedidosDelivery/verPedido/{{$p->id}}" class="btn btn-block text-left mb-3 pedido-urgente" style="border-radius: 8px; padding: 15px;">
										<div class="d-flex justify-content-between align-items-center mb-2">
											<div>
												<i class="fa fa-bell text-danger mr-2" style="font-size: 1.5rem;"></i>
												<span style="font-size: 1.2rem;">Pedido <strong>#{{$p->id}}</strong></span>
											</div>
											<span class="badge badge-danger py-2 px-3">AGUARDANDO APROVAÇÃO</span>
										</div>
										<hr style="border-top: 1px solid rgba(255,193,7, 0.5); margin: 10px 0;">
										<div class="d-flex justify-content-between flex-wrap" style="font-size: 1rem;">
											<span><i class="fa fa-user mr-1"></i> {{ $p->nome ?? ($p->cliente->nome ?? 'Cliente') }}</span>
											<span><i class="fa fa-clock mr-1"></i> {{ \Carbon\Carbon::parse($p->data_registro)->format('H:i')}}</span>
											<span><i class="fa fa-money-bill-wave mr-1"></i> <strong>R$ {{number_format($p->somaItens(), 2, ',', '.')}}</strong></span>
										</div>
									</a>
									@endforeach
								@else
									<h5>Nenhum pedido neste estado!</h5>
								@endif
							</div>
						</div>
					</div>
					<div class="card">
						<div class="card-header">
							<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseTwo1">
								Pedidos Aprovados <i class="la la-angle-double-down"></i>
							</div>
						</div>
						<div id="collapseTwo1" class="collapse" data-parent="#accordionExample1">
							<div class="card-body">
								@if(count($pedidosAprovado) > 0)
								@foreach($pedidosAprovado as $p)
								<a style="margin-top: 5px;" href="/pedidosDelivery/verPedido/{{$p->id}}" class="btn btn-light-success btn-block text-left">
									Pedido N: {{$p->id}} | Cliente: {{ $p->nome ?? ($p->cliente->nome ?? 'Cliente') }} | Valor R$ {{number_format($p->somaItens(), 2, ',', '.')}} | Horario: {{ \Carbon\Carbon::parse($p->data_registro)->format('H:i:s')}}
								</a>
								@endforeach
								@else
								<h5>Nenhum pedido neste estado!</h5>
								@endif
							</div>
						</div>
					</div>

					<div class="card">
						<div class="card-header">
							<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseThree1">
								Pedidos Cancelados <i class="la la-angle-double-down"></i>
							</div>
						</div>
						<div id="collapseThree1" class="collapse" data-parent="#accordionExample1">
							<div class="card-body">
								@if(sizeof($pedidosCancelado) > 0)
								@foreach($pedidosCancelado as $p)
								<a style="margin-top: 5px;" href="/pedidosDelivery/verPedido/{{$p->id}}" class="btn btn-light-danger btn-block text-left">
									Pedido N: {{$p->id}} | Cliente: {{ $p->nome ?? ($p->cliente->nome ?? 'Cliente') }} | Valor R$ {{number_format($p->somaItens(), 2, ',', '.')}} | Horario: {{ \Carbon\Carbon::parse($p->data_registro)->format('H:i:s')}}
								</a>
								@endforeach
								@else
								<h5>Nenhum pedido neste estado!</h5>
								@endif
							</div>
						</div>
					</div>
					
					<div class="card">
						<div class="card-header">
							<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseFive1">
								Pedidos Finalizados <i class="la la-angle-double-down"></i>
							</div>
						</div>
						<div id="collapseFive1" class="collapse" data-parent="#accordionExample1">
							<div class="card-body">
								@if(count($pedidosFinalizado) > 0)
								@foreach($pedidosFinalizado as $p)
								<a style="margin-top: 5px;" href="/pedidosDelivery/verPedido/{{$p->id}}" class="btn btn-light-info btn-block text-left">
									Pedido N: {{$p->id}} | Cliente: {{ $p->nome ?? ($p->cliente->nome ?? 'Cliente') }} | Valor R$ {{number_format($p->somaItens(), 2, ',', '.')}} | Horario: {{ \Carbon\Carbon::parse($p->data_registro)->format('H:i:s')}}
								</a>
								@endforeach
								@else
								<h5>Nenhum pedido neste estado!</h5>
								@endif
							</div>
						</div>
					</div>

				</div>
			</div>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-alerta-novo-pedido" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div class="modal-header bg-danger text-white" style="border-radius: 12px 12px 0 0;">
                <h4 class="modal-title font-weight-bold" style="color: white !important;">
                    <i class="fa fa-bell mr-2"></i> 🚨 NOVO PEDIDO DE DELIVERY 🚨
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <i aria-hidden="true" class="ki ki-close"></i>
                </button>
            </div>
            <div class="modal-body p-6">
                <div class="text-center mb-5">
                    <h2 class="text-dark font-weight-bold">Pedido <strong class="text-danger">#<span id="modal-alerta-id"></span></strong></h2>
                    <p class="text-muted" style="font-size: 16px;">Aguardando a sua confirmação para ir para a cozinha!</p>
                </div>
                
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center">Cliente</th>
                            <th class="text-center">Horário</th>
                            <th class="text-center">Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="modal-alerta-cliente" class="font-weight-bold text-center" style="font-size: 18px;"></td>
                            <td id="modal-alerta-hora" class="text-center" style="font-size: 18px;"></td>
                            <td id="modal-alerta-valor" class="font-weight-bold text-success text-center" style="font-size: 18px;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer d-flex justify-content-between bg-light">
                <a href="#" id="btn-ver-detalhes" class="btn btn-dark font-weight-bold">
                    <i class="fa fa-eye"></i> Ver Detalhes
                </a>
                
                <div>
                    <a href="#" id="btn-rejeitar-pedido" class="btn btn-outline-danger font-weight-bold mr-2">
                        <i class="fa fa-times"></i> Rejeitar
                    </a>
                    <a href="#" id="btn-aceitar-pedido" class="btn btn-success font-weight-bold px-6">
                        <i class="fa fa-check"></i> Aceitar Pedido
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // O layout padrão já possui o monitor global corrigido. Evita dois popups
    // concorrentes consultando o mesmo pedido nesta tela.
    if (document.getElementById('modalNovoPedidoAlerta')) {
        return;
    }

    const somAlerta = new Audio('/audio/delivery_1.mp3'); 
    somAlerta.loop = true; 
    
    // Pega o ID do pedido mais recente que já está na tela para não apitar repetido
    let ultimoPedidoNotificado = {{ count($pedidosNovo) > 0 ? $pedidosNovo[0]->id : 0 }};

    setInterval(function() {
        $.get('/pedidosDelivery/ultimoPedidoNovo', function(pedido) {
            
            // LOG DE DEPURAÇÃO: Isso vai aparecer no F12 para sabermos se está vivo!
            console.log("Verificando pedidos...", pedido); 

            if (pedido && pedido.id) {
                if (pedido.id != ultimoPedidoNotificado) {
                    ultimoPedidoNotificado = pedido.id;
                    
                    $('#modal-alerta-id').text(pedido.id);
                    $('#modal-alerta-cliente').text(
                        pedido.cliente && pedido.cliente.nome
                            ? pedido.cliente.nome
                            : (pedido.cliente || 'Cliente')
                    );
                    $('#modal-alerta-valor').text(pedido.valor_total || pedido.valor || '0,00');
                    $('#modal-alerta-hora').text(pedido.hora);

                    $('#btn-ver-detalhes').attr('href', '/pedidosDelivery/verPedido/' + pedido.id);
                    $('#btn-aceitar-pedido').attr('href', '/pedidosDelivery/alterarPedido?tipo=aprovado&id=' + pedido.id);
                    $('#btn-rejeitar-pedido').attr('href', '/pedidosDelivery/alterarPedido?tipo=cancelado&id=' + pedido.id);

                    somAlerta.play().catch(e => console.log("Áudio bloqueado", e));
                    $('#modal-alerta-novo-pedido').modal('show');
                }
            }
        });
    }, 6000); 

    $('#modal-alerta-novo-pedido').on('hidden.bs.modal', function () {
        somAlerta.pause();
        somAlerta.currentTime = 0;
        location.reload(); 
    });
    
    $('#modal-alerta-novo-pedido .btn').click(function() {
        somAlerta.pause();
    });
});
</script>
@endsection