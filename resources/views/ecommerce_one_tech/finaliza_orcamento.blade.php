@extends('ecommerce_one_tech.default')
@section('content')

<style type="text/css">
	.pays:hover{
		cursor: pointer;
	}
	.pays{
		font-size: 20px;
	}
	.active{
		border-bottom: 4px solid red;
	}
	.form-control{
		color: #000;
		margin-top: 5px;
		margin-bottom: 15px;
	}
	@keyframes spinner-border {
		to { transform: rotate(360deg); }
	} 
	.spinner-border{
		display: inline-block;
		width: 2rem;
		height: 2rem;
		vertical-align: text-bottom;
		border: .25em solid currentColor;
		border-right-color: transparent;
		border-radius: 50%;
		-webkit-animation: spinner-border .75s linear infinite;
		animation: spinner-border .75s linear infinite;
		margin-top: 5px;
		/*position: flex;*/
	}

</style>

<div class="cart_section">
	<div class="container">
		<div class="row">
			<div class="col-lg-10 offset-lg-1">
				<div class="cart_container">
					<div class="cart_title"><center>Finalizando orçamento</center></div>
					<div class="cart_title">
						<input type="hidden" id="totais" value="{{json_encode($totais)}}" name="">

					</div>

					<div class="col-12 mix pix">
						<br>
						<form action="/ecommercePay/finalizaOrcamento" method="post">
							@csrf
							<div class="row">
								<div class="col-12">

									<div class="checkout__input">

										<span>Observação</span>
										<textarea class="form-control" value="" name="observacao" id="observacao" rows="4"></textarea>
										
									</div>
								</div>

								<input style="visibility: hidden" type="" name="transactionAmount" id="transactionAmount" value="{{$total}}" />
								<input type="hidden" name="total_pag" value="{{$totais->total_pix}}">

								<input style="visibility: hidden" value="{{$descricao}}" name="description">
								<input style="visibility: hidden" name="paymentMethodId" id="paymentMethodId" />
								<input type="hidden" value="{{$carrinho->id}}" name="carrinho_id">

							</div>

							<div class="row">
								<div class="col-lg-12">
									<button type="submit" class="button cart_button_checkout">
										FINALIZAR
									</button>
								</div>
							</div> 
						</form>
						

					</div>

				</div>
			</div>
		</div>
	</div>
</div>

@endsection