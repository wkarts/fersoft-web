<tr class="datatable-row" style="left: 0px;">

	<td class="datatable-cell"><span class="codigo" style="width: 300px;">{{$item->servico->nome}}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->quantidade) }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->valor_unitario) }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ moeda($item->sub_total) }}</span></td>
	<td class="datatable-cell"><span class="codigo" style="width: 100px;">
		@if($item->status == true)
		<span class="label label-xl label-inline label-light-success">FINALIZADO
		</span>
		@else
		<span class="label label-xl label-inline label-light-warning">PENDENTE
		</span>
		@endif
	</span></td>

	<td class="datatable-cell"><span class="codigo" style="width: 120px;">
		@if(!$item->status)
		<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ordemServico/deleteServico/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
			<span class="la la-trash"></span>
		</a>
		@endif
		<a class="btn btn-success btn-sm" href="/ordemServico/alterarStatusServico/{{ $item->id }}">
			<span class="la la-check"></span>
		</a>

	</span></td>

</tr>