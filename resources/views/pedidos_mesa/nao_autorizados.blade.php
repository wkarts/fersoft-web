<div class="col-12">
	<div class="row">

		<h5 class="col-12">Pedido <strong class="text-success">#{{$pedido->id}}</strong></h5>
		<h5 class="col-12 text-success">#{{$pedido->uid}}</h5>

		<div class="col-12">
			<h6>Cliente: <strong>{{$pedido->nome_cliente}}</strong></h6>
			<h6>Celular: <strong>{{$pedido->telefone_cliente}}</strong></h6>
			<h6><strong class="text-info">{{$pedido->mesa->nome}}</strong></h6>
			
		</div>
		

		<input type="hidden" name="pedido_id" value="{{$pedido->id}}" id="pedido_id">
		<input type="hidden" name="estado" value="" id="estado_mesa">
		
	</div>
</div>