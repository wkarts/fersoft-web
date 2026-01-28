@extends('ecommerce.default')
@section('content')
<style type="text/css">
	.input-group-append:hover{
		cursor: pointer;
	}
</style>

<section class="shoping-cart" style="margin-top: -70px;">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">

				<input type="hidden" value="{{$pedido->transacao_id}}" id="transacao_id" name="">
				<input type="hidden" value="{{$pedido->status_pagamento}}" id="status" name="">
				<div class="">
					<div class="alert alert-custom alert-success fade show" role="alert">

						<div class="alert-text"><i class="fa fa-check"></i> 
							Obrigado por realizar o orçamento 😊
						</div>
					</div>
				</div>

				<br>
				<h2>Detalhes do seu pedido</h2>
				<div class="shoping__cart__table">
					<table>
						<thead>
							<tr>
								<th class="shoping__product">Produto</th>
								<th>Quantidade</th>
							</tr>
						</thead>
						<tbody>
							@foreach($pedido->itens as $i)
							<tr>
								<td class="shoping__cart__item">
									<img height="40" src="/ecommerce/produtos/{{$i->produto->galeria[0]->img}}" alt="">
									<h5>{{$i->produto->produto->nome}}</h5>
								</td>
								<td class="shoping__cart__quantity">
									<strong>{{$i->quantidade}}</strong>
								</td>
							</tr>
							@endforeach

						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<div class="shoping__cart__btns">

				</div>
			</div>
			<input type="hidden" value="{{csrf_token()}}" id="token">


			<div class="col-lg-6">
				<div class="shoping__checkout">
					<h5>Endereço</h5>
					<ul>
						<li>

							<h6>Rua: 
								<strong>
									{{ $pedido->endereco->rua }}, {{ $pedido->endereco->numero }}
								</strong>
							</h6>
							<h6>Complemento: 
								<strong>
									{{ $pedido->endereco->complemento }}
								</strong>
							</h6>
							<h6>Bairro: 
								<strong>
									{{ $pedido->endereco->bairro }}
								</strong>
							</h6>
							<h6>CEP: 
								<strong>
									{{ $pedido->endereco->cep }}
								</strong>
							</h6>

							<h6>Cidade: 
								<strong>
									{{ $pedido->endereco->cidade }} ({{ $pedido->endereco->uf }})
								</strong>
							</h6>
						</li>

					</ul>

				</div>
			</div>

			<div class="col-lg-6">
				<div class="shoping__checkout">
					<h5>TOTAL</h5>
					<ul>
						<li>Frete 
							<span>
								R$ {{ number_format($pedido->valor_frete, 2, ',', '.') }}
							</span>
						</li>
					</ul>

				</div>
			</div>
		</div>
	</div>
</section>

@section('javascript')
<script type="text/javascript">
	@if($pedido->link_boleto != "")
	window.open('{{$pedido->link_boleto}}')
	@endif


	function copy(){

		const inputTest = document.querySelector("#qrcode_input");

		inputTest.select();
		document.execCommand('copy');

		swal("", "Código pix copado!!", "success")
	}

	var prot = window.location.protocol;
	var host = window.location.host;
	var pathname = window.location.pathname;
	let path = prot + "//" + host;

	if($('#status').val() != "approved"){
		setInterval(() => {
			let transacao_id = $('#transacao_id').val();

			$.get(path+'/ecommercePay/consulta/'+transacao_id)
			.done((success) => {

				if(success == "approved"){
					location.reload()
				}
			})
			.fail((err) => {
				console.log(err)
			})
		}, 1500)
	}
</script>

@endsection

@endsection 
